<?php
// routes/engineer/job_parts.php
// GET  ?job_id=  → list parts used on a job (with names from spare_parts)
// POST {job_id, part_id, qty, unit_price?} → add part to job (deducts from engineer inventory)
// POST {action:'remove', entry_id} → remove a specific job_parts_used row
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();
$engineerId = (int)$engineer['id'];

// ── DELETE via POST {action:'remove'} ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($input['action'] ?? '') === 'remove') {
    $entryId = (int)($input['entry_id'] ?? 0);
    if (!$entryId) jsonResponse(false, null, 'entry_id required', 422);

    // Verify this entry belongs to this engineer
    $entry = $db->prepare("SELECT * FROM job_parts_used WHERE id=? AND engineer_id=?");
    $entry->execute([$entryId, $engineerId]);
    $row = $entry->fetch();
    if (!$row) jsonResponse(false, null, 'Part entry not found', 404);

    // Restore qty to engineer inventory
    $db->prepare("UPDATE engineer_inventory SET qty = qty + ? WHERE engineer_id=? AND part_id=?")
       ->execute([$row['qty'], $engineerId, $row['part_id']]);

    $db->prepare("DELETE FROM job_parts_used WHERE id=?")->execute([$entryId]);

    jsonResponse(true, null, 'Part removed');
}

// ── POST: add part to job ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId  = (int)($input['job_id']  ?? 0);
    $partId = (int)($input['part_id'] ?? 0);
    $qty    = (int)($input['qty']     ?? 1);

    if (!$jobId || !$partId) jsonResponse(false, null, 'job_id and part_id required', 422);
    if ($qty < 1)            jsonResponse(false, null, 'qty must be at least 1', 422);

    // Verify job belongs to this engineer
    $jobCheck = $db->prepare("SELECT id FROM jobs WHERE id=? AND engineer_id=?");
    $jobCheck->execute([$jobId, $engineerId]);
    if (!$jobCheck->fetch()) jsonResponse(false, null, 'Job not found', 404);

    // Check engineer has enough stock
    $invStmt = $db->prepare("SELECT ei.qty, sp.sell_price, sp.name FROM engineer_inventory ei JOIN spare_parts sp ON sp.id=ei.part_id WHERE ei.engineer_id=? AND ei.part_id=?");
    $invStmt->execute([$engineerId, $partId]);
    $inv = $invStmt->fetch();

    if (!$inv) jsonResponse(false, null, 'Part not in your inventory', 404);
    if ($inv['qty'] < $qty) jsonResponse(false, null, 'Insufficient stock. You have ' . $inv['qty'] . ' unit(s).', 422);

    // Use custom price if provided, else sell_price
    $unitPrice = isset($input['unit_price']) && $input['unit_price'] > 0
        ? (float)$input['unit_price']
        : (float)$inv['sell_price'];

    // Deduct from engineer inventory
    $db->prepare("UPDATE engineer_inventory SET qty = qty - ? WHERE engineer_id=? AND part_id=?")
       ->execute([$qty, $engineerId, $partId]);

    // Record usage
    $db->prepare("INSERT INTO job_parts_used (job_id, part_id, engineer_id, qty, unit_price) VALUES (?,?,?,?,?)")
       ->execute([$jobId, $partId, $engineerId, $qty, $unitPrice]);
    $entryId = $db->lastInsertId();

    jsonResponse(true, ['entry_id' => $entryId], 'Part added to job');
}

// ── GET: list parts used on a job ─────────────────────────────────────────────
$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Verify job belongs to this engineer
$jobCheck = $db->prepare("SELECT id FROM jobs WHERE id=? AND engineer_id=?");
$jobCheck->execute([$jobId, $engineerId]);
if (!$jobCheck->fetch()) jsonResponse(false, null, 'Job not found', 404);

$stmt = $db->prepare("
    SELECT jp.id, jp.qty, jp.unit_price, jp.created_at,
           sp.name, sp.sku,
           jp.qty * jp.unit_price AS subtotal
    FROM job_parts_used jp
    JOIN spare_parts sp ON jp.part_id = sp.id
    WHERE jp.job_id = ? AND jp.engineer_id = ?
    ORDER BY jp.created_at ASC
");
$stmt->execute([$jobId, $engineerId]);
$parts = $stmt->fetchAll();

$partsTotal = array_sum(array_column($parts, 'subtotal'));

jsonResponse(true, [
    'parts'       => $parts,
    'parts_total' => round($partsTotal, 2),
    'count'       => count($parts),
]);

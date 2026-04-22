<?php
// api/routes/admin/zone_engineers.php
// Assign / remove engineers from zones
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $input['action'] ?? '';

// ── LIST ENGINEERS FOR A ZONE ─────────────────────────────────────────────
if ($method === 'GET') {
    $zoneId = (int)($input['zone_id'] ?? 0);
    if (!$zoneId) jsonResponse(false, null, 'zone_id required', 422);

    $stmt = $db->prepare("
        SELECT e.id, e.name, e.phone, e.email, e.status, e.city,
               e.profile_photo, e.kyc_status,
               COALESCE(AVG(r.rating), 0) AS avg_rating,
               COUNT(DISTINCT j.id) AS completed_jobs,
               ze.is_available, ze.created_at AS assigned_at
        FROM zone_engineers ze
        JOIN engineers e ON e.id = ze.engineer_id
        LEFT JOIN ratings r ON r.engineer_id = e.id
        LEFT JOIN jobs j ON j.engineer_id = e.id AND j.status = 'completed'
        WHERE ze.zone_id = ?
        GROUP BY e.id, ze.is_available, ze.created_at
        ORDER BY e.name ASC
    ");
    $stmt->execute([$zoneId]);
    jsonResponse(true, $stmt->fetchAll(), 'Engineers in zone');
}

// ── ASSIGN ENGINEER TO ZONE ───────────────────────────────────────────────
if ($method === 'POST' && $action === 'assign') {
    $zoneId     = (int)($input['zone_id']     ?? 0);
    $engineerId = (int)($input['engineer_id'] ?? 0);
    if (!$zoneId || !$engineerId) jsonResponse(false, null, 'zone_id and engineer_id required', 422);

    // Verify zone and engineer exist
    $zone = $db->prepare("SELECT id, name FROM zones WHERE id=? AND is_active=1");
    $zone->execute([$zoneId]);
    if (!$zone->fetch()) jsonResponse(false, null, 'Zone not found or inactive', 404);

    $eng = $db->prepare("SELECT id, name FROM engineers WHERE id=? AND is_active=1");
    $eng->execute([$engineerId]);
    $engineer = $eng->fetch();
    if (!$engineer) jsonResponse(false, null, 'Engineer not found', 404);

    // INSERT IGNORE — safe if already assigned
    $db->prepare("INSERT IGNORE INTO zone_engineers (zone_id, engineer_id, is_available) VALUES (?,?,1)")
       ->execute([$zoneId, $engineerId]);

    jsonResponse(true, null, $engineer['name'] . ' assigned to zone successfully');
}

// ── REMOVE ENGINEER FROM ZONE ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'remove') {
    $zoneId     = (int)($input['zone_id']     ?? 0);
    $engineerId = (int)($input['engineer_id'] ?? 0);
    if (!$zoneId || !$engineerId) jsonResponse(false, null, 'zone_id and engineer_id required', 422);

    $db->prepare("DELETE FROM zone_engineers WHERE zone_id=? AND engineer_id=?")
       ->execute([$zoneId, $engineerId]);
    jsonResponse(true, null, 'Engineer removed from zone');
}

// ── TOGGLE ENGINEER AVAILABILITY IN ZONE ─────────────────────────────────
if ($method === 'POST' && $action === 'toggle_availability') {
    $zoneId     = (int)($input['zone_id']     ?? 0);
    $engineerId = (int)($input['engineer_id'] ?? 0);
    if (!$zoneId || !$engineerId) jsonResponse(false, null, 'zone_id and engineer_id required', 422);

    $db->prepare("UPDATE zone_engineers SET is_available = NOT is_available WHERE zone_id=? AND engineer_id=?")
       ->execute([$zoneId, $engineerId]);
    jsonResponse(true, null, 'Availability updated');
}

// ── BULK ASSIGN (multiple engineers at once) ──────────────────────────────
if ($method === 'POST' && $action === 'bulk_assign') {
    $zoneId      = (int)($input['zone_id'] ?? 0);
    $engineerIds = $input['engineer_ids'] ?? [];
    if (!$zoneId || empty($engineerIds)) jsonResponse(false, null, 'zone_id and engineer_ids required', 422);

    $stmt = $db->prepare("INSERT IGNORE INTO zone_engineers (zone_id, engineer_id, is_available) VALUES (?,?,1)");
    $count = 0;
    foreach ((array)$engineerIds as $eid) {
        $eid = (int)$eid;
        if ($eid > 0) { $stmt->execute([$zoneId, $eid]); $count++; }
    }
    jsonResponse(true, ['assigned' => $count], $count . ' engineer(s) assigned to zone');
}

// ── LIST ALL ENGINEERS NOT IN ZONE (for add dropdown) ────────────────────
if ($method === 'GET' && ($input['action'] ?? '') === 'available') {
    $zoneId = (int)($input['zone_id'] ?? 0);
    if (!$zoneId) jsonResponse(false, null, 'zone_id required', 422);

    $stmt = $db->prepare("
        SELECT e.id, e.name, e.phone, e.city, e.status, e.kyc_status
        FROM engineers e
        WHERE e.is_active = 1
          AND e.id NOT IN (SELECT engineer_id FROM zone_engineers WHERE zone_id = ?)
        ORDER BY e.name ASC
    ");
    $stmt->execute([$zoneId]);
    jsonResponse(true, $stmt->fetchAll(), 'Available engineers');
}

jsonResponse(false, null, 'Invalid request', 400);

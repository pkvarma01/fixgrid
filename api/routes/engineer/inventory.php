<?php
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId  = (int)($input['job_id']  ?? 0);
    $partId = (int)($input['part_id'] ?? 0);
    $qty    = (int)($input['qty']     ?? 1);
    if (!$jobId || !$partId) jsonResponse(false, null, 'job_id and part_id required', 422);
    $part = $db->prepare('SELECT * FROM spare_parts WHERE id=?');
    $part->execute([$partId]);
    $p = $part->fetch();
    if (!$p) jsonResponse(false, null, 'Part not found', 404);
    $db->prepare('INSERT INTO job_parts_used (job_id,part_id,engineer_id,qty,unit_price) VALUES (?,?,?,?,?)')->execute([$jobId, $partId, $engineer['id'], $qty, $p['sell_price']]);
    jsonResponse(true, null, 'Part logged');
}
$stmt = $db->prepare('SELECT ei.*, sp.name, sp.sku, sp.unit FROM engineer_inventory ei JOIN spare_parts sp ON ei.part_id=sp.id WHERE ei.engineer_id=?');
$stmt->execute([$engineer['id']]);
jsonResponse(true, $stmt->fetchAll());

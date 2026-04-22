<?php
// api/routes/engineer/leave_zone.php
// Engineer leaves a zone (removes assignment)
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db       = getDB();

$zoneId = (int)($input['zone_id'] ?? 0);
if (!$zoneId) jsonResponse(false, null, 'zone_id is required', 422);

$stmt = $db->prepare("DELETE FROM zone_engineers WHERE zone_id = ? AND engineer_id = ?");
$stmt->execute([$zoneId, $engineer['id']]);

if ($stmt->rowCount() === 0) {
    jsonResponse(false, null, 'You are not in this zone', 404);
}

jsonResponse(true, null, 'You have left this zone successfully');

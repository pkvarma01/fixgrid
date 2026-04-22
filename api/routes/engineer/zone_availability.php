<?php
// api/routes/engineer/zone_availability.php
// Toggle engineer availability in a specific zone (active/paused)
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db       = getDB();

$zoneId = (int)($input['zone_id'] ?? 0);
if (!$zoneId) jsonResponse(false, null, 'zone_id is required', 422);

// Verify engineer is in this zone
$chk = $db->prepare("SELECT id, is_available FROM zone_engineers WHERE zone_id = ? AND engineer_id = ?");
$chk->execute([$zoneId, $engineer['id']]);
$row = $chk->fetch();
if (!$row) jsonResponse(false, null, 'You are not in this zone', 404);

// Toggle
$db->prepare("UPDATE zone_engineers SET is_available = NOT is_available WHERE zone_id = ? AND engineer_id = ?")
   ->execute([$zoneId, $engineer['id']]);

$newStatus = $row['is_available'] ? 0 : 1;
$msg = $newStatus ? 'You are now active in this zone and will receive job notifications.' : 'Zone paused — you will not receive job notifications from this zone.';

jsonResponse(true, ['is_available' => (bool)$newStatus], $msg);

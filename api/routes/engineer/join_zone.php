<?php
// api/routes/engineer/join_zone.php
// Engineer self-joins a zone
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db       = getDB();

$zoneId = (int)($input['zone_id'] ?? 0);
if (!$zoneId) jsonResponse(false, null, 'zone_id is required', 422);

// Verify zone exists and is active
$stmt = $db->prepare("SELECT id, name, city FROM zones WHERE id = ? AND is_active = 1");
$stmt->execute([$zoneId]);
$zone = $stmt->fetch();
if (!$zone) jsonResponse(false, null, 'Zone not found or inactive', 404);

// Check already joined
$chk = $db->prepare("SELECT id FROM zone_engineers WHERE zone_id = ? AND engineer_id = ?");
$chk->execute([$zoneId, $engineer['id']]);
if ($chk->fetch()) jsonResponse(false, null, 'You have already joined this zone', 409);

// Join zone — active by default
$db->prepare("INSERT INTO zone_engineers (zone_id, engineer_id, is_available) VALUES (?, ?, 1)")
   ->execute([$zoneId, $engineer['id']]);

jsonResponse(true, [
    'zone_id'   => $zoneId,
    'zone_name' => $zone['name'],
    'city'      => $zone['city'],
], 'Successfully joined ' . $zone['name'] . ' — ' . $zone['city']);

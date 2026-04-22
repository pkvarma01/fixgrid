<?php
// api/routes/engineer/available_zones.php
// Returns all active zones the engineer has NOT yet joined
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db       = getDB();

$stmt = $db->prepare("
    SELECT z.id, z.name, z.city, z.state, z.description,
           z.latitude, z.longitude, z.radius_km
    FROM zones z
    WHERE z.is_active = 1
      AND z.id NOT IN (
          SELECT zone_id FROM zone_engineers WHERE engineer_id = ?
      )
    ORDER BY z.city ASC, z.name ASC
");
$stmt->execute([$engineer['id']]);
jsonResponse(true, $stmt->fetchAll(), 'Available zones loaded');

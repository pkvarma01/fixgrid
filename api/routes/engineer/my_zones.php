<?php
// api/routes/engineer/my_zones.php
// Engineer views their assigned zones
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db       = getDB();

$stmt = $db->prepare("
    SELECT z.id, z.name, z.city, z.state, z.description,
           z.latitude, z.longitude, z.radius_km, z.is_active,
           ze.is_available, ze.created_at AS assigned_at,
           (SELECT COUNT(*) FROM zone_engineers ze2 WHERE ze2.zone_id = z.id) AS total_engineers
    FROM zone_engineers ze
    JOIN zones z ON z.id = ze.zone_id
    WHERE ze.engineer_id = ? AND z.is_active = 1
    ORDER BY z.city ASC, z.name ASC
");
$stmt->execute([$engineer['id']]);
$zones = $stmt->fetchAll();

jsonResponse(true, $zones, count($zones) . ' zone(s) assigned');

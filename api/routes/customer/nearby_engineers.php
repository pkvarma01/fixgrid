<?php
// routes/customer/nearby_engineers.php — List nearby available engineers filtered by skill
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$lat       = (float)($input['latitude']   ?? 0);
$lng       = (float)($input['longitude']  ?? 0);
$serviceId = (int)($input['service_id']   ?? 0);
$radiusKm  = (float)($input['radius_km']  ?? 20);

if (!$lat || !$lng) jsonResponse(false, null, 'latitude and longitude required', 422);

// Get required skill for this service
$skillId = null;
if ($serviceId) {
    $svc = $db->prepare("SELECT required_skill_id FROM service_types WHERE id=? AND is_active=1");
    $svc->execute([$serviceId]);
    $svcRow = $svc->fetch();
    if (!$svcRow) jsonResponse(false, null, 'Invalid service', 404);
    $skillId = $svcRow['required_skill_id'];
}

// Haversine distance formula in SQL
$sql = "SELECT e.id, e.name, e.phone, e.profile_photo, e.status, e.service_area,
    e.latitude, e.longitude,
    COALESCE(ROUND(AVG(r.rating),1), 0) AS avg_rating,
    COUNT(DISTINCT r.id) AS total_reviews,
    (SELECT COUNT(*) FROM jobs WHERE engineer_id=e.id AND status='completed') AS completed_jobs,
    (6371 * ACOS(
        COS(RADIANS(?)) * COS(RADIANS(e.latitude)) *
        COS(RADIANS(e.longitude) - RADIANS(?)) +
        SIN(RADIANS(?)) * SIN(RADIANS(e.latitude))
    )) AS distance_km
    FROM engineers e
    LEFT JOIN ratings r ON r.engineer_id = e.id";

$params = [$lat, $lng, $lat];

// Filter by skill if service requires one
if ($skillId) {
    $sql .= " INNER JOIN engineer_skills es ON es.engineer_id = e.id AND es.skill_id = ?";
    $params[] = $skillId;
}

$sql .= " WHERE e.is_active=1 AND e.status='available'
    AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL
    GROUP BY e.id
    HAVING distance_km <= ?
    ORDER BY distance_km ASC
    LIMIT 20";

$params[] = $radiusKm;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$engineers = $stmt->fetchAll();

// Get skills for each engineer
foreach ($engineers as &$eng) {
    $skills = $db->prepare("SELECT s.id, s.name FROM engineer_skills es JOIN skills s ON es.skill_id=s.id WHERE es.engineer_id=?");
    $skills->execute([$eng['id']]);
    $eng['skills'] = $skills->fetchAll();
    $eng['distance_km'] = round($eng['distance_km'], 1);
}

jsonResponse(true, $engineers);

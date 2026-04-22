<?php
// routes/customer/engineer_profile.php — Public engineer profile for customers
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$engineerId = (int)($input['engineer_id'] ?? 0);
if (!$engineerId) jsonResponse(false, null, 'engineer_id required', 422);

$stmt = $db->prepare("SELECT e.id, e.name, e.phone, e.profile_photo, e.service_area,
    e.status, e.created_at,
    COALESCE(ROUND(AVG(r.rating),1), 0) AS avg_rating,
    COUNT(DISTINCT r.id) AS total_reviews,
    (SELECT COUNT(*) FROM jobs WHERE engineer_id=e.id AND status='completed') AS completed_jobs
    FROM engineers e
    LEFT JOIN ratings r ON r.engineer_id = e.id
    WHERE e.id=? AND e.is_active=1
    GROUP BY e.id");
$stmt->execute([$engineerId]);
$engineer = $stmt->fetch();
if (!$engineer) jsonResponse(false, null, 'Engineer not found', 404);

// Skills
$skills = $db->prepare("SELECT s.id, s.name FROM engineer_skills es JOIN skills s ON es.skill_id=s.id WHERE es.engineer_id=?");
$skills->execute([$engineerId]);

// Recent reviews (last 10)
$reviews = $db->prepare("SELECT r.rating, r.feedback, r.created_at, c.name AS customer_name
    FROM ratings r JOIN customers c ON r.customer_id=c.id
    WHERE r.engineer_id=? ORDER BY r.created_at DESC LIMIT 10");
$reviews->execute([$engineerId]);

jsonResponse(true, array_merge($engineer, [
    'skills'  => $skills->fetchAll(),
    'reviews' => $reviews->fetchAll(),
]));

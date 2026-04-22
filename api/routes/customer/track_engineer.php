<?php
// routes/customer/track_engineer.php
// FIX: Added job amounts (visit_charge, final_amount), distance calculation,
//      service charge breakdown for customer display
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$stmt = $db->prepare("
    SELECT j.id, j.job_number, j.status, j.address, j.description,
           j.latitude AS job_lat, j.longitude AS job_lng,
           j.engineer_id,
           COALESCE(j.final_amount, j.amount, 0) AS final_amount,
           COALESCE(j.amount, 0)         AS base_amount,
           COALESCE(j.visit_charge, 0)   AS visit_charge,
           COALESCE(j.emergency_fee, 0)  AS emergency_fee,
           COALESCE(j.discount_amount, 0) AS discount_amount,
           j.is_emergency,
           e.name AS engineer_name, e.phone AS engineer_phone,
           e.profile_photo AS engineer_photo,
           e.latitude  AS eng_lat,
           e.longitude AS eng_lng,
           e.last_online, e.status AS engineer_status
    FROM jobs j
    LEFT JOIN engineers e ON j.engineer_id = e.id
    WHERE j.id = ? AND j.customer_id = ?
");
$stmt->execute([$jobId, $customer['id']]);
$data = $stmt->fetch();

if (!$data) jsonResponse(false, null, 'Job not found', 404);

// Calculate distance between engineer and job location (haversine)
$distanceKm = null;
if ($data['eng_lat'] && $data['eng_lng'] && $data['job_lat'] && $data['job_lng']) {
    $lat1 = deg2rad((float)$data['eng_lat']);
    $lat2 = deg2rad((float)$data['job_lat']);
    $dLat = $lat2 - $lat1;
    $dLng = deg2rad((float)$data['job_lng'] - (float)$data['eng_lng']);
    $a    = sin($dLat/2)**2 + cos($lat1)*cos($lat2)*sin($dLng/2)**2;
    $distanceKm = round(6371 * 2 * asin(sqrt($a)), 1);
}

$trackable = in_array($data['status'], ['assigned','accepted','on_the_way','arrived','working']);

jsonResponse(true, array_merge($data, [
    'is_trackable'  => $trackable,
    'distance_km'   => $distanceKm,
]));

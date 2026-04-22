<?php
// routes/customer/job_detail.php — Full detail for a single job
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$stmt = $db->prepare("SELECT j.*,
    COALESCE(j.final_amount, j.amount, 0) AS amount,
    COALESCE(s.name, j.service_type) AS service_name, s.icon AS service_icon,
    COALESCE(e.name, 'Unassigned') AS engineer_name,
    e.phone AS engineer_phone, e.profile_photo AS engineer_photo,
    e.latitude AS engineer_lat, e.longitude AS engineer_lng,
    e.status AS engineer_status,
    c.name AS customer_name, c.phone AS customer_phone,
    COALESCE(r.rating, NULL) AS rating, COALESCE(r.feedback, NULL) AS review
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN service_types s ON j.service_id = s.id
    LEFT JOIN engineers e ON j.engineer_id = e.id
    LEFT JOIN ratings r ON r.job_id = j.id
    WHERE j.id = ? AND j.customer_id = ?");
$stmt->execute([$jobId, $customer['id']]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, null, 'Job not found', 404);

// Invoice if exists
$invoice = $db->prepare("SELECT * FROM invoices WHERE job_id = ? LIMIT 1");
$invoice->execute([$jobId]);

// Chat room
$room = $db->prepare("SELECT id FROM chat_rooms WHERE job_id = ?");
$room->execute([$jobId]);
$roomRow = $room->fetch();

jsonResponse(true, array_merge($job, [
    'invoice'      => $invoice->fetch() ?: null,
    'chat_room_id' => $roomRow ? $roomRow['id'] : null,
]));

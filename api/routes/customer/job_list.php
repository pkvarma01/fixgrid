<?php
// routes/customer/job_list.php — List all jobs for the authenticated customer
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$status = $input['status'] ?? null;

$sql = "SELECT j.id, j.job_number, j.status, j.priority, j.address,
    j.created_at, j.scheduled_date, j.start_time, j.end_time,
    COALESCE(j.final_amount, j.amount, 0) AS amount,
    COALESCE(s.name, j.service_type) AS service, s.icon AS service_icon,
    COALESCE(e.name, 'Unassigned') AS engineer,
    e.phone AS engineer_phone, e.profile_photo AS engineer_photo,
    COALESCE(r.rating, NULL) AS rating
    FROM jobs j
    LEFT JOIN service_types s ON j.service_id = s.id
    LEFT JOIN engineers e ON j.engineer_id = e.id
    LEFT JOIN ratings r ON r.job_id = j.id
    WHERE j.customer_id = ?";

$params = [$customer['id']];
if ($status) { $sql .= ' AND j.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY j.created_at DESC LIMIT 50';

$stmt = $db->prepare($sql);
$stmt->execute($params);
jsonResponse(true, $stmt->fetchAll());

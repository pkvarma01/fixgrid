<?php
// routes/engineer/update_status.php — Engineer updates job status (on_the_way, arrived, working)
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId  = (int)($input['job_id'] ?? 0);
$status = trim($input['status'] ?? '');

if (!$jobId || !$status) jsonResponse(false, null, 'job_id and status required', 422);

$allowed = ['on_the_way', 'arrived', 'working'];
if (!in_array($status, $allowed)) {
    jsonResponse(false, null, 'Invalid status. Use: on_the_way, arrived, or working. To complete a job use /engineer/complete-job.', 422);
}

$stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=?");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);

// Enforce a logical status progression
$progressionMap = [
    'on_the_way' => ['accepted', 'assigned', 'revisit_scheduled'],  // revisit_scheduled allowed
    'arrived'    => ['on_the_way'],
    'working'    => ['arrived'],
];
if (!in_array($job['status'], $progressionMap[$status])) {
    jsonResponse(false, null, "Cannot set status to '$status' from current status '{$job['status']}'", 422);
}

$sets = ["status=?", "updated_at=NOW()"];
$params = [$status];

// Set start_time when engineer starts working
if ($status === 'working' && empty($job['start_time'])) {
    $sets[] = 'start_time=NOW()';
}

$params[] = $jobId;
$db->prepare('UPDATE jobs SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);

// Notify customer
$statusLabels = [
    'on_the_way' => 'is on the way',
    'arrived'    => 'has arrived',
    'working'    => 'has started working',
];
$custStmt = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$custStmt->execute([$job['customer_id']]);
$cust = $custStmt->fetch();
sendPushNotification(
    $cust['device_token'] ?? '',
    'Job Update',
    $engineer['name'] . ' ' . $statusLabels[$status] . '.',
    ['type' => 'job_update', 'job_id' => $jobId, 'status' => $status]
);
logNotification($job['customer_id'], 'customer', 'Job Update', 'Engineer ' . $statusLabels[$status] . '.', ['job_id' => $jobId]);

jsonResponse(true, ['job_id' => $jobId, 'status' => $status], 'Status updated');

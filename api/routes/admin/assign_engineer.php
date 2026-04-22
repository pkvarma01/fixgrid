<?php
// routes/admin/assign_engineer.php
// FIX: When reassigning a job, the previously assigned engineer is now
//      set back to 'available' before assigning the new one.
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$jobId = (int)($input['job_id']      ?? 0);
$engId = (int)($input['engineer_id'] ?? 0);
if (!$jobId || !$engId) jsonResponse(false, null, 'job_id and engineer_id required', 422);

$jobStmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
$jobStmt->execute([$jobId]);
$j = $jobStmt->fetch();

$engStmt = $db->prepare('SELECT * FROM engineers WHERE id = ? AND is_active = 1');
$engStmt->execute([$engId]);
$e = $engStmt->fetch();

if (!$j) jsonResponse(false, null, 'Job not found', 404);
if (!$e) jsonResponse(false, null, 'Engineer not found', 404);

// FIX: If the job already has a different engineer assigned, free them first
if (!empty($j['engineer_id']) && (int)$j['engineer_id'] !== $engId) {
    $db->prepare("UPDATE engineers SET status='available' WHERE id = ? AND status = 'busy'")
       ->execute([$j['engineer_id']]);
}

$db->prepare("UPDATE jobs SET engineer_id=?, status='assigned' WHERE id=?")
   ->execute([$engId, $jobId]);

// Only mark engineer busy if job is for today or ASAP (not a future scheduled job)
$scheduledDate = $j['scheduled_date'] ?? null;
$isToday = !$scheduledDate || $scheduledDate <= date('Y-m-d');
if ($isToday) {
    $db->prepare("UPDATE engineers SET status='busy' WHERE id=?")->execute([$engId]);
}

$dateLabel = $scheduledDate && $scheduledDate > date('Y-m-d')
    ? 'Scheduled for ' . $scheduledDate
    : 'Now';

sendPushNotification(
    $e['device_token'] ?? '',
    'Job Assigned — ' . $dateLabel,
    'Admin assigned you job #' . $j['job_number'] . '. ' . $dateLabel . '.',
    ['job_id' => $jobId, 'type' => 'job_assigned']
);
logNotification($engId, 'engineer', 'Job Assigned', 'Job #' . $j['job_number'] . ' assigned by admin.', ['job_id' => $jobId]);

jsonResponse(true, null, 'Engineer assigned successfully');

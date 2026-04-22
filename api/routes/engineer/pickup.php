<?php
// routes/engineer/pickup.php
// Engineer requests device pickup to repair center
// Flow: engineer submits → admin schedules pickup → device picked → repaired → delivered back
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();
$engineerId = (int)$engineer['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $jobId = (int)($input['job_id'] ?? 0);
    if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
    $stmt = $db->prepare("SELECT * FROM device_pickups WHERE job_id=? AND engineer_id=? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$jobId, $engineerId]);
    jsonResponse(true, $stmt->fetch() ?: null);
}

$jobId       = (int)($input['job_id']        ?? 0);
$deviceDesc  = trim($input['device_desc']    ?? '');
$pickupNotes = trim($input['pickup_notes']   ?? '');
$pickupAddr  = trim($input['pickup_address'] ?? '');

if (!$jobId || !$deviceDesc) jsonResponse(false, null, 'job_id and device_desc required', 422);

$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=?");
$jobStmt->execute([$jobId, $engineerId]);
$job = $jobStmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);
if (!in_array($job['status'], ['working','arrived','on_the_way','accepted','assigned','awaiting_quotation'])) {
    jsonResponse(false, null, "Cannot request pickup for job with status '{$job['status']}'", 422);
}

// Use job address if none provided
if (!$pickupAddr) $pickupAddr = $job['address'];

$db->prepare("INSERT INTO device_pickups (job_id, engineer_id, device_desc, pickup_notes, pickup_address, status)
    VALUES (?,?,?,?,?,'requested')")
   ->execute([$jobId, $engineerId, $deviceDesc, $pickupNotes, $pickupAddr]);
$pickupId = $db->lastInsertId();

// Update job status
$db->prepare("UPDATE jobs SET status='pickup_requested', updated_at=NOW() WHERE id=?")->execute([$jobId]);

// Notify admin
logNotification(0, 'admin', 'Device Pickup Requested',
    'Engineer ' . $engineer['name'] . ' requested pickup for job #' . $job['job_number'] . ': ' . $deviceDesc,
    ['job_id' => $jobId, 'pickup_id' => $pickupId, 'type' => 'pickup_requested']);

// Notify customer
$cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$cust->execute([$job['customer_id']]);
sendPushNotification($cust->fetchColumn() ?? '', 'Device Pickup Requested',
    'Engineer has requested to take your ' . $deviceDesc . ' to our service center.',
    ['type' => 'pickup_requested', 'job_id' => $jobId]);
logNotification($job['customer_id'], 'customer', 'Device Pickup',
    'Your ' . $deviceDesc . ' will be picked up for repair. We will contact you to schedule.',
    ['job_id' => $jobId]);

jsonResponse(true, ['pickup_id' => $pickupId],
    'Pickup request submitted. Our team will schedule the pickup and contact the customer.');

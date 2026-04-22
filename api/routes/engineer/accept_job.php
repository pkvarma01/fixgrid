<?php
// routes/engineer/accept_job.php
// FIX: Broadcast offer accept now sets status='accepted' directly (not 'assigned')
// Status flow:
//   Broadcast: pending → accepted (engineer taps Accept) → on_the_way → arrived → working → completed
//   Admin-assign: assigned (admin sets) → accepted (engineer confirms) → on_the_way → ...
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
$engineerId = (int)$engineer['id'];

// Check broadcast offer (job still pending, engineer has an offer row)
$offerStmt = $db->prepare("SELECT * FROM job_offers WHERE job_id=? AND engineer_id=? AND status='pending'");
$offerStmt->execute([$jobId, $engineerId]);
$offer = $offerStmt->fetch();

// Check direct admin-assignment (engineer already set on job, status='assigned')
$directStmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=? AND status='assigned'");
$directStmt->execute([$jobId, $engineerId]);
$directJob = $directStmt->fetch();

if (!$offer && !$directJob) {
    jsonResponse(false, null, 'No pending job offer found', 404);
}

$db->beginTransaction();
try {
    $checkStmt = $db->prepare("SELECT status FROM jobs WHERE id=? FOR UPDATE");
    $checkStmt->execute([$jobId]);
    $currentStatus = $checkStmt->fetchColumn();

    if (!in_array($currentStatus, ['pending', 'assigned'])) {
        $db->rollBack();
        if ($offer) {
            $db->prepare("UPDATE job_offers SET status='expired', responded_at=NOW() WHERE job_id=? AND engineer_id=?")
               ->execute([$jobId, $engineerId]);
        }
        jsonResponse(false, null, 'Job is no longer available', 409);
    }

    // FIX: set status='accepted' directly — one tap takes job from offer → accepted
    // For admin-assigned jobs (status was 'assigned'), engineer confirming also moves to 'accepted'
    // For future scheduled jobs keep status='assigned' so engineer stays available today
    $jobRowStmt = $db->prepare("SELECT scheduled_date FROM jobs WHERE id=?");
    $jobRowStmt->execute([$jobId]);
    $jobRow = $jobRowStmt->fetch();
    $scheduledDate = $jobRow['scheduled_date'] ?? null;
    $isFuture = $scheduledDate && $scheduledDate > date('Y-m-d');

    $newStatus = $isFuture ? 'assigned' : 'accepted';
    $db->prepare("UPDATE jobs SET engineer_id=?, status=?, updated_at=NOW() WHERE id=?")
       ->execute([$engineerId, $newStatus, $jobId]);

    // Only mark engineer busy if job is for today
    if (!$isFuture) {
        $db->prepare("UPDATE engineers SET status='busy' WHERE id=?")->execute([$engineerId]);
    }

    // Update chat room
    $db->prepare("UPDATE chat_rooms SET engineer_id=? WHERE job_id=?")->execute([$engineerId, $jobId]);

    if ($offer) {
        // Mark this offer accepted, expire all competing offers
        $db->prepare("UPDATE job_offers SET status='accepted', responded_at=NOW() WHERE job_id=? AND engineer_id=?")
           ->execute([$jobId, $engineerId]);
        $db->prepare("UPDATE job_offers SET status='expired', responded_at=NOW() WHERE job_id=? AND engineer_id!=? AND status='pending'")
           ->execute([$jobId, $engineerId]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Could not accept job, please try again', 500);
}

$jobFullStmt = $db->prepare("SELECT * FROM jobs WHERE id=?");
$jobFullStmt->execute([$jobId]);
$job = $jobFullStmt->fetch();
$custStmt = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$custStmt->execute([$job['customer_id']]);
$cust = $custStmt->fetch();

sendPushNotification(
    $cust['device_token'] ?? '',
    'Engineer Accepted ✅',
    $engineer['name'] . ' accepted your job and is on the way!',
    ['type' => 'job_update', 'job_id' => $jobId, 'status' => 'accepted']
);
logNotification(
    $job['customer_id'], 'customer',
    'Engineer Accepted',
    $engineer['name'] . ' accepted job #' . $job['job_number'] . '.',
    ['job_id' => $jobId]
);

jsonResponse(true, ['job_id' => $jobId, 'status' => 'accepted'], 'Job accepted! Customer has been notified.');

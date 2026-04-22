<?php
// routes/engineer/revisit_start.php
// Engineer starts a revisit — moves job from revisit_scheduled → accepted
// so normal status progression (on_the_way → arrived → working → complete) can proceed
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=? AND status='revisit_scheduled'");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();
if (!$job) jsonResponse(false, null, 'No revisit job found', 404);

// Move to on_the_way
$db->prepare("UPDATE jobs SET status='on_the_way', updated_at=NOW() WHERE id=?")->execute([$jobId]);
$db->prepare("UPDATE engineers SET status='busy' WHERE id=?")->execute([$engineer['id']]);

// Notify customer
$cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$cust->execute([$job['customer_id']]);
sendPushNotification($cust->fetchColumn() ?? '', 'Engineer On the Way 📅',
    $engineer['name'] . ' is on the way for your revisit appointment.',
    ['type' => 'revisit_started', 'job_id' => $jobId]);
logNotification($job['customer_id'], 'customer', 'Revisit Started',
    $engineer['name'] . ' is heading to you for the revisit.', ['job_id' => $jobId]);

jsonResponse(true, ['status' => 'on_the_way'], '📅 Revisit started! Navigate to customer location.');

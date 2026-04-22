<?php
// routes/customer/rate_job.php — Customer rates a completed job
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$jobId   = (int)($input['job_id']  ?? 0);
$rating  = (int)($input['rating']  ?? 0);
$comment = trim($input['feedback']  ?? '');

if (!$jobId)              jsonResponse(false, null, 'job_id required', 422);
if ($rating < 1 || $rating > 5) jsonResponse(false, null, 'rating must be 1-5', 422);

// Job must be completed and belong to this customer
$stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND customer_id=? AND status='completed'");
$stmt->execute([$jobId, $customer['id']]);
$job = $stmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found or not completed', 404);

// Prevent duplicate rating
$existing = $db->prepare("SELECT id FROM ratings WHERE job_id=? AND customer_id=?");
$existing->execute([$jobId, $customer['id']]);
if ($existing->fetch()) jsonResponse(false, null, 'You have already rated this job', 409);

$db->prepare("INSERT INTO ratings (job_id, engineer_id, customer_id, rating, feedback) VALUES (?,?,?,?,?)")
   ->execute([$jobId, $job['engineer_id'], $customer['id'], $rating, $comment ?: null]);

// Notify engineer — wrapped so a push failure never breaks the response
try {
    if ($job['engineer_id']) {
        $eng = $db->prepare("SELECT device_token FROM engineers WHERE id=?");
        $eng->execute([$job['engineer_id']]);
        $engRow = $eng->fetch();
        if (!empty($engRow['device_token'])) {
            sendPushNotification(
                $engRow['device_token'],
                'New Rating ⭐',
                $customer['name'] . ' rated job #' . $job['job_number'] . ': ' . $rating . '/5',
                ['type' => 'rating', 'job_id' => $jobId]
            );
        }
    }
} catch (Exception $e) {
    error_log('[rate_job] Push notification failed: ' . $e->getMessage());
}

jsonResponse(true, ['rating' => $rating], 'Thank you for your feedback!');

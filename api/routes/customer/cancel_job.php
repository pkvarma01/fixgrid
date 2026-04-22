<?php
// routes/customer/cancel_job.php
// Customer cancels a pending job (before engineer is working)
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$jobId  = (int)($input['job_id']  ?? 0);
$reason = trim($input['reason']   ?? 'Cancelled by customer');
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Fetch job — must belong to this customer
$stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND customer_id=?");
$stmt->execute([$jobId, $customer['id']]);
$job = $stmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);

// Only allow cancel on pending/assigned/accepted — not once engineer is on the way
$cancellable = ['pending','assigned','accepted'];
if (!in_array($job['status'], $cancellable)) {
    jsonResponse(false, null, 'Cannot cancel — engineer is already on the way or job is ' . $job['status'], 422);
}

// Cancel the job
$db->prepare("UPDATE jobs SET status='cancelled', notes=CONCAT(COALESCE(notes,''),?), updated_at=NOW() WHERE id=?")
   ->execute([' | Customer cancellation: ' . $reason, $jobId]);

// Free engineer if assigned
if ($job['engineer_id']) {
    $db->prepare("UPDATE engineers SET status='available' WHERE id=? AND status='busy'")->execute([$job['engineer_id']]);
    // Notify engineer
    $eng = $db->prepare("SELECT device_token FROM engineers WHERE id=?");
    $eng->execute([$job['engineer_id']]);
    $engRow = $eng->fetch();
    sendPushNotification(
        $engRow['device_token'] ?? '',
        'Job Cancelled',
        'Customer cancelled job #' . $job['job_number'] . '. Reason: ' . $reason,
        ['type' => 'job_cancelled', 'job_id' => $jobId]
    );
}

// Expire any pending offers
$db->prepare("UPDATE job_offers SET status='expired', responded_at=NOW() WHERE job_id=? AND status='pending'")->execute([$jobId]);

// Refund wallet if customer paid from wallet
$walletTxn = $db->prepare("SELECT * FROM wallet_transactions WHERE ref_id=? AND customer_id=? AND type='debit' LIMIT 1");
$walletTxn->execute([$jobId, $customer['id']]);
$txn = $walletTxn->fetch();
$refunded = 0;
if ($txn && (float)$txn['amount'] > 0) {
    $refundAmt = (float)$txn['amount'];
    $db->prepare("UPDATE customer_wallet SET balance=balance+? WHERE customer_id=?")->execute([$refundAmt, $customer['id']]);
    $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description,ref_id) VALUES (?,?,?,?,?)")
       ->execute([$customer['id'], 'credit', $refundAmt, 'Refund for cancelled job #' . $job['job_number'], $jobId]);
    $refunded = $refundAmt;
}

logNotification($customer['id'], 'customer', 'Job Cancelled', 'Your job #' . $job['job_number'] . ' has been cancelled.', ['job_id' => $jobId]);

jsonResponse(true, [
    'job_id'   => $jobId,
    'status'   => 'cancelled',
    'refunded' => $refunded,
], 'Job cancelled' . ($refunded > 0 ? '. ₹' . number_format($refunded, 2) . ' refunded to wallet.' : '.'));

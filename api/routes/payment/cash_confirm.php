<?php
// routes/payment/cash_confirm.php — Engineer confirms cash/UPI payment received
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId  = (int)($input['job_id']  ?? 0);
$amount = (float)($input['amount'] ?? 0);

if (!$jobId)      jsonResponse(false, null, 'job_id required', 422);
if ($amount <= 0) jsonResponse(false, null, 'amount must be greater than 0', 422);

// Job must be completed and belong to this engineer
$stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=? AND status='completed'");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found or not completed', 404);

// Prevent duplicate cash invoices
$existing = $db->prepare("SELECT id FROM invoices WHERE job_id=? AND payment_method='cash'");
$existing->execute([$jobId]);
if ($existing->fetch()) jsonResponse(false, null, 'Cash payment already recorded for this job', 409);

// Sanity check: if job has a recorded final_amount and the difference is > ₹1, use the job amount
$jobAmount        = (float)($job['final_amount'] ?? $job['amount'] ?? 0);
$confirmedAmount  = ($jobAmount > 0 && abs($amount - $jobAmount) > 1) ? $jobAmount : $amount;

$invoiceNumber = 'INV-' . date('ymd') . '-' . rand(1000, 9999);
$db->prepare("INSERT INTO invoices (invoice_number,job_id,customer_id,total,status,payment_method,paid_at) VALUES (?,?,?,?,'paid','cash',NOW())")
   ->execute([$invoiceNumber, $jobId, $job['customer_id'], $confirmedAmount]);
$invoiceId = $db->lastInsertId();

// Engineer earnings based on platform_charge_pct setting
$platformPct = (float)getSettingValue('platform_charge_pct', 20);
$earnings = round($confirmedAmount * (1 - $platformPct/100), 2);
// Credit engineer wallet
$db->prepare("INSERT IGNORE INTO engineer_wallet (engineer_id, balance) VALUES (?,0)")->execute([$engineer['id']]);
$db->prepare("UPDATE engineer_wallet SET balance=balance+? WHERE engineer_id=?")->execute([$earnings, $engineer['id']]);
$db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id,job_id,type,amount,description) VALUES (?,?,'credit',?,?)")
   ->execute([$engineer['id'], $jobId, $earnings, 'Cash payment for job #'.$job['job_number']]);
$db->prepare("INSERT INTO engineer_earnings (engineer_id,job_id,amount,status) VALUES (?,?,?,'pending') ON DUPLICATE KEY UPDATE amount=VALUES(amount)")
   ->execute([$engineer['id'], $jobId, $earnings]);

// Update final_amount on job if it was 0
if ($jobAmount == 0) {
    $db->prepare("UPDATE jobs SET final_amount=? WHERE id=?")->execute([$confirmedAmount, $jobId]);
}

// Notify customer
$cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$cust->execute([$job['customer_id']]);
$custRow = $cust->fetch();
sendPushNotification(
    $custRow['device_token'] ?? '',
    'Payment Confirmed',
    'Cash payment of ₹' . number_format($confirmedAmount, 2) . ' received. Invoice: ' . $invoiceNumber,
    ['type' => 'payment', 'job_id' => $jobId]
);

jsonResponse(true, [
    'invoice_number' => $invoiceNumber,
    'invoice_id'     => $invoiceId,
    'amount'         => $confirmedAmount,
    'earnings'       => $earnings,
], 'Cash payment confirmed');

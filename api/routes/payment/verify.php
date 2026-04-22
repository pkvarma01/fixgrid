<?php
// routes/payment/verify.php — Razorpay payment verification + wallet/job credit
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$orderId   = trim($input['razorpay_order_id']   ?? '');
$paymentId = trim($input['razorpay_payment_id'] ?? '');
$signature = trim($input['razorpay_signature']  ?? '');
$purpose   = trim($input['purpose']             ?? 'wallet');
$jobId     = (int)($input['job_id']             ?? 0);

if (!$orderId || !$paymentId || !$signature) {
    jsonResponse(false, null, 'Missing payment details', 422);
}

$keySecret = getSettingValue('razorpay_key_secret', '');
if (!$keySecret) jsonResponse(false, null, 'Payment not configured', 503);

// Verify HMAC signature
$expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
if (!hash_equals($expected, $signature)) {
    jsonResponse(false, null, 'Payment verification failed — signature mismatch', 400);
}

// Fetch pending transaction
$txn = $db->prepare("SELECT * FROM payment_transactions WHERE razorpay_order_id=? AND customer_id=? AND status='pending'");
$txn->execute([$orderId, $customer['id']]);
$txn = $txn->fetch();
if (!$txn) jsonResponse(false, null, 'Transaction not found', 404);

$amount = (float)$txn['amount'];

// Mark transaction paid
$db->prepare("UPDATE payment_transactions SET razorpay_payment_id=?, status='paid', paid_at=NOW() WHERE razorpay_order_id=?")
   ->execute([$paymentId, $orderId]);

if ($purpose === 'wallet' || $txn['purpose'] === 'wallet') {
    // Credit wallet
    $db->prepare("INSERT IGNORE INTO customer_wallet (customer_id, balance) VALUES (?,0)")->execute([$customer['id']]);
    $db->prepare("UPDATE customer_wallet SET balance=balance+? WHERE customer_id=?")->execute([$amount, $customer['id']]);
    $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description) VALUES (?,?,?,?)")
       ->execute([$customer['id'], 'credit', $amount, 'Wallet top-up via Razorpay (' . $paymentId . ')']);

    // Push notification
    $cust = $db->query("SELECT device_token FROM customers WHERE id=" . (int)$customer['id'])->fetch();
    sendPushNotification($cust['device_token'] ?? '', 'Payment Successful', '₹' . number_format($amount, 2) . ' added to your wallet!', ['type' => 'wallet']);

    jsonResponse(true, ['amount' => $amount, 'purpose' => 'wallet'], '₹' . number_format($amount, 2) . ' added to wallet successfully!');
}

if ($purpose === 'job' || $txn['purpose'] === 'job') {
    $jId = $jobId ?: (int)$txn['job_id'];
    if ($jId) {
        $db->prepare("UPDATE jobs SET payment_status='paid', payment_method='online', razorpay_payment_id=? WHERE id=? AND customer_id=?")
           ->execute([$paymentId, $jId, $customer['id']]);
        // Mark invoice paid
        $db->prepare("UPDATE invoices SET status='paid', payment_method='online', paid_at=NOW() WHERE job_id=?")
           ->execute([$jId]);
    }
    jsonResponse(true, ['amount' => $amount, 'purpose' => 'job'], 'Payment of ₹' . number_format($amount, 2) . ' confirmed!');
}

jsonResponse(true, ['amount' => $amount], 'Payment verified');

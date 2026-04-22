<?php
// routes/payment/webhook.php — Razorpay server-to-server webhook
// Handles payment.captured event — credits wallet/job even if customer closed browser
// Set webhook URL in Razorpay Dashboard: https://www.fixgrid.in/api/payment/webhook
require_once dirname(__DIR__, 2) . '/config.php';
$db = getDB();

// Read raw body (Razorpay sends JSON)
$rawBody  = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

$webhookSecret = getSettingValue('razorpay_webhook_secret', '');

// Verify webhook signature if secret is configured
if ($webhookSecret) {
    $expectedSig = hash_hmac('sha256', $rawBody, $webhookSecret);
    if (!hash_equals($expectedSig, $sigHeader)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

$event = json_decode($rawBody, true);
if (!$event) { http_response_code(400); exit; }

$eventName = $event['event'] ?? '';

// Only handle payment captured
if ($eventName !== 'payment.captured') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

$payment   = $event['payload']['payment']['entity'] ?? [];
$paymentId = $payment['id']    ?? '';
$orderId   = $payment['order_id'] ?? '';
$amount    = (float)(($payment['amount'] ?? 0) / 100); // paise → rupees

if (!$paymentId || !$orderId) { http_response_code(200); exit; }

// Find pending transaction for this order
$txn = $db->prepare("SELECT * FROM payment_transactions WHERE razorpay_order_id=? AND status='pending' LIMIT 1");
$txn->execute([$orderId]);
$txn = $txn->fetch();

if (!$txn) {
    // Already processed or not found — idempotent, return 200
    error_log("[Webhook] Order $orderId not found or already processed");
    http_response_code(200);
    echo json_encode(['status' => 'already_processed']);
    exit;
}

$customerId = (int)$txn['customer_id'];
$purpose    = $txn['purpose'] ?? 'wallet';
$jobId      = (int)($txn['job_id'] ?? 0);

// Mark paid
$db->prepare("UPDATE payment_transactions SET status='paid', razorpay_payment_id=?, paid_at=NOW() WHERE razorpay_order_id=?")
   ->execute([$paymentId, $orderId]);

if ($purpose === 'wallet') {
    // Credit customer wallet
    $db->prepare("INSERT IGNORE INTO customer_wallet (customer_id, balance) VALUES (?,0)")->execute([$customerId]);
    $db->prepare("UPDATE customer_wallet SET balance=balance+? WHERE customer_id=?")->execute([$amount, $customerId]);
    $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description) VALUES (?,?,?,?)")
       ->execute([$customerId, 'credit', $amount, 'Wallet top-up via Razorpay (' . $paymentId . ')']);

    // Notify customer
    $cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
    $cust->execute([$customerId]);
    $custRow = $cust->fetch();
    sendPushNotification($custRow['device_token'] ?? '', 'Payment Successful',
        '₹' . number_format($amount,2) . ' added to your wallet!', ['type' => 'wallet']);

    error_log("[Webhook] Wallet credited ₹$amount for customer $customerId");
}

if ($purpose === 'job' && $jobId) {
    $db->prepare("UPDATE jobs SET payment_status='paid', payment_method='online', razorpay_payment_id=? WHERE id=?")
       ->execute([$paymentId, $jobId]);
    $db->prepare("UPDATE invoices SET status='paid', payment_method='online', paid_at=NOW() WHERE job_id=?")
       ->execute([$jobId]);
    error_log("[Webhook] Job $jobId payment confirmed");
}

http_response_code(200);
echo json_encode(['status' => 'ok']);

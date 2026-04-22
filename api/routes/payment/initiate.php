<?php
// routes/payment/initiate.php — Razorpay order creation
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$amount  = (float)($input['amount']  ?? 0);
$purpose = trim($input['purpose']    ?? 'wallet'); // wallet | job
$jobId   = (int)($input['job_id']    ?? 0);

if ($amount <= 0) jsonResponse(false, null, 'Invalid amount', 422);

$keyId     = getSettingValue('razorpay_key_id', '');
$keySecret = getSettingValue('razorpay_key_secret', '');

if (!$keyId || !$keySecret) {
    jsonResponse(false, null, 'Online payment not configured. Please ask admin to add Razorpay keys in Settings.', 503);
}

// Create Razorpay order via REST API
$orderData = [
    'amount'   => (int)round($amount * 100), // paise
    'currency' => 'INR',
    'receipt'  => 'rcpt_' . time() . '_' . $customer['id'],
    'notes'    => ['customer_id' => $customer['id'], 'purpose' => $purpose, 'job_id' => $jobId],
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($orderData),
    CURLOPT_USERPWD        => "$keyId:$keySecret",
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$resp   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$resp || $status !== 200) {
    jsonResponse(false, null, 'Razorpay order creation failed. Check API keys.', 502);
}
$order = json_decode($resp, true);
if (empty($order['id'])) {
    jsonResponse(false, null, $order['error']['description'] ?? 'Payment gateway error', 502);
}

// Log pending transaction
$db->prepare("INSERT INTO payment_transactions (customer_id, razorpay_order_id, amount, purpose, job_id, status)
    VALUES (?,?,?,?,?,'pending')")
   ->execute([$customer['id'], $order['id'], $amount, $purpose, $jobId ?: null]);

jsonResponse(true, [
    'order_id'   => $order['id'],
    'amount'     => $amount,
    'currency'   => 'INR',
    'key_id'     => $keyId,
    'name'       => getSettingValue('company_name', 'Hridya Tech'),
    'customer'   => ['name' => $customer['name'], 'email' => $customer['email'] ?? '', 'contact' => $customer['phone'] ?? ''],
]);

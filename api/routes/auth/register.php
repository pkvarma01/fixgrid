<?php
// routes/auth/register.php — Customer phone registration with OTP
// Sends OTP via WhatsApp (Twilio/Meta) and/or Email based on admin settings
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/otp_helper.php';

$phone = trim($input['phone'] ?? '');
$name  = trim($input['name']  ?? '');

if (!$phone) jsonResponse(false, null, 'Phone number is required', 422);
if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) jsonResponse(false, null, 'Invalid phone number', 422);

$db  = getDB();
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$exp = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Upsert customer
$existing = $db->prepare('SELECT id, name FROM customers WHERE phone = ?');
$existing->execute([$phone]);
$row = $existing->fetch();

if ($row) {
    $db->prepare('UPDATE customers SET otp=?, otp_expires_at=?, otp_attempts=0 WHERE phone=?')
       ->execute([$otp, $exp, $phone]);
    $userId    = $row['id'];
    $custName  = $row['name'];
} else {
    if (!$name) jsonResponse(false, null, 'Name is required for new registration', 422);
    $db->prepare('INSERT INTO customers (name,phone,otp,otp_expires_at,otp_attempts) VALUES (?,?,?,?,0)')
       ->execute([$name, $phone, $otp, $exp]);
    $userId   = $db->lastInsertId();
    $custName = $name;
}

// Send OTP via WhatsApp and/or Email
$results = sendOtp($phone, $otp, $custName, $db);

// Build response message
$channels = [];
if (!empty($results['whatsapp']) && str_starts_with($results['whatsapp'], 'sent')) $channels[] = 'WhatsApp';
if (!empty($results['email'])    && $results['email'] === 'sent')                   $channels[] = 'Email';

$msg = count($channels)
    ? 'OTP sent via ' . implode(' & ', $channels)
    : 'OTP generated. Check WhatsApp or Email.';

jsonResponse(true, ['user_id' => $userId, 'phone' => $phone], $msg);

<?php
// routes/auth/verify_otp.php — Verify OTP and return token
// FIX 1: Column name corrected from otp_expires → otp_expires_at
// FIX 2: Added brute-force protection (max 5 attempts, then lock)
require_once dirname(__DIR__, 2) . '/config.php';

$phone = trim($input['phone'] ?? '');
$otp   = trim($input['otp']   ?? '');

if (!$phone || !$otp) jsonResponse(false, null, 'Phone and OTP are required', 422);

$db = getDB();

// FIX: Load customer first to check attempt count
$customerStmt = $db->prepare('SELECT * FROM customers WHERE phone = ? AND is_active = 1');
$customerStmt->execute([$phone]);
$customer = $customerStmt->fetch();

if (!$customer) {
    jsonResponse(false, null, 'Invalid or expired OTP', 401);
}

// FIX: Brute-force lockout — block after 5 failed attempts
if ((int)$customer['otp_attempts'] >= 5) {
    jsonResponse(false, null, 'Too many failed attempts. Please request a new OTP.', 429);
}

// FIX: column is otp_expires_at
if ($customer['otp'] !== $otp || strtotime($customer['otp_expires_at']) < time()) {
    // Increment attempt counter
    $db->prepare('UPDATE customers SET otp_attempts = otp_attempts + 1 WHERE id = ?')
       ->execute([$customer['id']]);
    jsonResponse(false, null, 'Invalid or expired OTP', 401);
}

// OTP is valid — clear it and reset attempt counter
$db->prepare('UPDATE customers SET otp=NULL, otp_expires_at=NULL, otp_attempts=0 WHERE id=?')
   ->execute([$customer['id']]);

// Generate token
$token = generateToken();
$exp   = date('Y-m-d H:i:s', time() + TOKEN_TTL);
$db->prepare('INSERT INTO auth_tokens (user_id,user_type,token,expires_at) VALUES (?,?,?,?)')
   ->execute([$customer['id'], 'customer', $token, $exp]);

unset($customer['otp'], $customer['otp_expires_at'], $customer['otp_attempts'], $customer['device_token']);
jsonResponse(true, ['token' => $token, 'customer' => $customer], 'Login successful');

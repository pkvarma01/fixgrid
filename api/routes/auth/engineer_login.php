<?php
// routes/auth/engineer_login.php — Engineer email/password login
require_once dirname(__DIR__, 2) . '/config.php';

$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) jsonResponse(false, null, 'Email and password are required', 422);

$db   = getDB();
// First check if engineer exists at all (even if inactive)
$stmt = $db->prepare('SELECT * FROM engineers WHERE email = ?');
$stmt->execute([$email]);
$engineer = $stmt->fetch();

if (!$engineer || !password_verify($password, $engineer['password'])) {
    jsonResponse(false, null, 'Invalid email or password', 401);
}

// Account exists but not active — check why
if (!$engineer['is_active']) {
    $kycStatus = $engineer['kyc_status'] ?? 'pending';
    if ($kycStatus === 'submitted') {
        jsonResponse(false, null, '⏳ Your KYC documents are under review. You will be notified within 24 hours once approved.', 403);
    }
    if ($kycStatus === 'rejected') {
        $reason = $engineer['kyc_rejection_reason'] ?? 'Documents could not be verified';
        jsonResponse(false, null, '❌ KYC rejected: ' . $reason . '. Please re-register with correct documents.', 403);
    }
    if ($kycStatus === 'pending') {
        // They registered but did not complete KYC — give them engineer_id to resume
        jsonResponse(false, [
            'resume_kyc'   => true,
            'engineer_id'  => $engineer['id'],
            'next_step'    => $engineer['kyc_aadhaar_verified'] ? ($engineer['kyc_pan_verified'] ? 'selfie' : 'pan') : 'aadhaar_init',
        ], '📋 Please complete your KYC verification to activate your account.', 403);
    }
    jsonResponse(false, null, 'Account is inactive. Contact admin.', 403);
}

// Update status to available & last_online
$db->prepare('UPDATE engineers SET status=?, last_online=NOW() WHERE id=?')
   ->execute(['available', $engineer['id']]);

// Update device token if provided
if (!empty($input['device_token'])) {
    $db->prepare('UPDATE engineers SET device_token=? WHERE id=?')
       ->execute([$input['device_token'], $engineer['id']]);
}

// Issue token
$token = generateToken();
$exp   = date('Y-m-d H:i:s', time() + TOKEN_TTL);
$db->prepare('INSERT INTO auth_tokens (user_id,user_type,token,expires_at) VALUES (?,?,?,?)')
   ->execute([$engineer['id'], 'engineer', $token, $exp]);

unset($engineer['password'], $engineer['device_token']);
jsonResponse(true, ['token' => $token, 'engineer' => $engineer], 'Login successful');

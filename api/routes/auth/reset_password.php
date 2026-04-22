<?php
// routes/auth/reset_password.php
// Step 2: Verify token and set new password
require_once dirname(__DIR__, 2) . '/config.php';

$token    = trim($input['token']    ?? '');
$password = trim($input['password'] ?? '');

if (!$token)    jsonResponse(false, null, 'Token is required', 422);
if (!$password) jsonResponse(false, null, 'New password is required', 422);
if (strlen($password) < 6) jsonResponse(false, null, 'Password must be at least 6 characters', 422);

$db = getDB();

// Validate token
$stmt = $db->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    jsonResponse(false, null, 'Invalid or expired reset link. Please request a new one.', 401);
}

// Update password
$table   = $reset['user_type'] === 'admin' ? 'admins' : 'engineers';
$hashed  = password_hash($password, PASSWORD_DEFAULT);
$db->prepare("UPDATE $table SET password=? WHERE email=?")->execute([$hashed, $reset['email']]);

// Mark token used
$db->prepare("UPDATE password_resets SET used=1 WHERE id=?")->execute([$reset['id']]);

// Invalidate all auth tokens for this user (force re-login)
$userStmt = $db->prepare("SELECT id FROM $table WHERE email=?");
$userStmt->execute([$reset['email']]);
$user = $userStmt->fetch();
if ($user) {
    $db->prepare("DELETE FROM auth_tokens WHERE user_id=? AND user_type=?")->execute([$user['id'], $reset['user_type']]);
}

jsonResponse(true, null, 'Password reset successfully! You can now log in with your new password.');

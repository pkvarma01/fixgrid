<?php
// routes/auth/forgot_password.php
// Step 1: Request password reset — generates token and sends email
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/otp_helper.php';

$email    = trim(strtolower($input['email']    ?? ''));
$userType = trim($input['user_type'] ?? ''); // 'admin' or 'engineer'

if (!$email)    jsonResponse(false, null, 'Email is required', 422);
if (!in_array($userType, ['admin','engineer'])) jsonResponse(false, null, 'user_type must be admin or engineer', 422);

$db = getDB();

// Check user exists
$table = $userType === 'admin' ? 'admins' : 'engineers';
$stmt  = $db->prepare("SELECT id, name, email FROM $table WHERE email=? AND is_active=1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Always return success to avoid email enumeration
if (!$user) {
    jsonResponse(true, null, 'If that email exists, a reset link has been sent.');
}

// Invalidate old tokens
$db->prepare("UPDATE password_resets SET used=1 WHERE email=? AND user_type=?")->execute([$email, $userType]);

// Generate secure token
$token   = bin2hex(random_bytes(32)); // 64 char hex
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$db->prepare("INSERT INTO password_resets (email, user_type, token, expires_at) VALUES (?,?,?,?)")
   ->execute([$email, $userType, $token, $expires]);

// Get app URL from settings
$settings = $db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('smtp_enabled','smtp_host','smtp_user','smtp_pass','smtp_port','smtp_from_email','smtp_from_name')")->fetchAll(PDO::FETCH_KEY_PAIR);

$appUrl = 'https://www.fixgrid.in';
$resetUrl = ($userType === 'admin')
    ? "$appUrl/admin/?reset_token=$token"
    : "$appUrl/engineer-app/engineer.php?reset_token=$token";

$userName = $user['name'];
$subject  = 'Reset Your Password — Hridya Tech';
$body = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:20px">
  <div style="background:#4F6EF7;padding:20px;border-radius:12px 12px 0 0;text-align:center">
    <div style="color:#fff;font-size:22px;font-weight:800">🔧 Hridya Tech</div>
  </div>
  <div style="background:#f9f9f9;padding:24px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb">
    <p style="color:#374151;font-size:15px">Hello <b>$userName</b>,</p>
    <p style="color:#374151;font-size:15px">We received a request to reset your password. Click the button below:</p>
    <div style="text-align:center;margin:24px 0">
      <a href="$resetUrl" style="background:#4F6EF7;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block">
        🔑 Reset My Password
      </a>
    </div>
    <p style="color:#6b7280;font-size:13px">This link expires in <b>30 minutes</b>.</p>
    <p style="color:#6b7280;font-size:13px">If you didn't request this, ignore this email. Your password won't change.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
    <p style="color:#9ca3af;font-size:11px">Or copy this link: $resetUrl</p>
  </div>
</body>
</html>
HTML;

// Send reset email via SMTP
if (!empty($settings['smtp_host']) && !empty($settings['smtp_user'])) {
    $result = sendSmtp(
        $settings['smtp_host'],
        (int)($settings['smtp_port'] ?? 465),
        $settings['smtp_user'],
        $settings['smtp_pass'] ?? '',
        $settings['smtp_from_email'] ?: $settings['smtp_user'],
        $settings['smtp_from_name'] ?: 'Hridya Tech',
        $email,
        $subject,
        $body
    );
} else {
    $result = 'smtp_not_configured';
    error_log("[Password Reset] SMTP not configured in Admin Settings. Reset URL: $resetUrl");
}

error_log("[Password Reset] $userType $email → token generated, email: $result, url: $resetUrl");

jsonResponse(true, null, 'If that email exists, a reset link has been sent.');
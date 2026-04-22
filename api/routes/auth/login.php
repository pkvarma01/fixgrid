<?php
// routes/auth/login.php — Admin login
require_once dirname(__DIR__, 2) . '/config.php';

$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) jsonResponse(false, null, 'Email and password required', 422);

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM admins WHERE email = ? AND is_active = 1');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    jsonResponse(false, null, 'Invalid credentials', 401);
}

$token = generateToken();
$exp   = date('Y-m-d H:i:s', time() + TOKEN_TTL);
$db->prepare('INSERT INTO auth_tokens (user_id,user_type,token,expires_at) VALUES (?,?,?,?)')
   ->execute([$admin['id'], 'admin', $token, $exp]);

unset($admin['password']);
jsonResponse(true, ['token' => $token, 'admin' => $admin], 'Login successful');

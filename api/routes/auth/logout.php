<?php
// routes/auth/logout.php
require_once dirname(__DIR__, 2) . '/config.php';

$headers = getallheaders();
$auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
    getDB()->prepare('DELETE FROM auth_tokens WHERE token=?')->execute([$m[1]]);
}
jsonResponse(true, null, 'Logged out successfully');

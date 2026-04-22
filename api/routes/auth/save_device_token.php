<?php
// routes/auth/save_device_token.php
// Called by app/PWA when FCM token is obtained
require_once dirname(__DIR__, 2) . '/config.php';

$token    = trim($input['device_token'] ?? '');
$userType = trim($input['user_type']    ?? ''); // 'customer' or 'engineer'
$userId   = (int)($input['user_id']    ?? 0);

if (!$token || !$userType || !$userId) {
    jsonResponse(false, null, 'device_token, user_type, user_id required', 422);
}

$db = getDB();
$table = $userType === 'engineer' ? 'engineers' : 'customers';

$db->prepare("UPDATE $table SET device_token=? WHERE id=?")->execute([$token, $userId]);

jsonResponse(true, null, 'Device token saved');

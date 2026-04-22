<?php
// routes/admin/save_fcm.php
// Saves Firebase service account JSON + handles test push
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$json          = trim($input['service_account_json'] ?? '');
$projectId     = trim($input['project_id']           ?? '');
$enabled       = $input['enabled']              ?? '0';
$testToken     = trim($input['test_token']            ?? '');
$broadcastType = trim($input['send_test_broadcast']   ?? '');

// ── Save service account JSON ─────────────────────────────
if ($json && $json !== '***configured***') {
    $sa = json_decode($json, true);
    if (!$sa) jsonResponse(false, null, 'Invalid JSON — paste the entire file contents', 422);

    foreach (['type','project_id','private_key','client_email'] as $k) {
        if (empty($sa[$k])) jsonResponse(false, null, "Missing required field in JSON: $k", 422);
    }
    if ($sa['type'] !== 'service_account') {
        jsonResponse(false, null, 'This must be a service_account type JSON from Firebase', 422);
    }

    if (!$projectId) $projectId = $sa['project_id'];

    $db->prepare("INSERT INTO app_settings (setting_key, setting_value)
        VALUES ('fcm_service_account_json', ?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()")
       ->execute([$json]);
}

// ── Save project ID ───────────────────────────────────────
if ($projectId) {
    $db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('fcm_project_id',?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()")
       ->execute([$projectId]);
}

// ── Save enabled flag ─────────────────────────────────────
$db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('fcm_enabled',?)
    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()")
   ->execute([$enabled]);

// ── Broadcast test to all engineers or customers ──────────
if ($broadcastType) {
    if (!function_exists('sendPushNotification')) {
        $helper = dirname(__DIR__, 2) . '/fcm_helper.php';
        if (file_exists($helper)) require_once $helper;
        else jsonResponse(false, null, 'fcm_helper.php not found on server', 500);
    }
    $table  = $broadcastType === 'engineer' ? 'engineers' : 'customers';
    $tokens = $db->query("SELECT device_token FROM $table
        WHERE device_token IS NOT NULL AND device_token != '' AND is_active=1")
        ->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tokens)) {
        jsonResponse(true, ['sent' => 0, 'total' => 0],
            'No device tokens found — users must open the app first to register their device');
    }

    $sent = $failed = 0;
    foreach ($tokens as $tok) {
        $ok = sendPushNotification(
            $tok,
            '🔔 Test Notification — Hridya Tech',
            'Push notifications are working!',
            ['type' => 'test']
        );
        $ok ? $sent++ : $failed++;
    }
    $total = count($tokens);
    jsonResponse(true, ['sent' => $sent, 'failed' => $failed, 'total' => $total],
        "Sent $sent / $total {$broadcastType}s" . ($failed > 0 ? " ($failed failed — tokens may be expired)" : ''));
}

// ── Single token test ─────────────────────────────────────
if ($testToken && $enabled === '1') {
    if (!function_exists('sendPushNotification')) {
        $helper = dirname(__DIR__, 2) . '/fcm_helper.php';
        if (file_exists($helper)) require_once $helper;
    }
    $ok = sendPushNotification(
        $testToken,
        '🔔 Hridya Tech',
        'Firebase push notifications are working!',
        ['type' => 'test']
    );
    jsonResponse(true, ['test_sent' => $ok],
        'Firebase settings saved' . ($ok ? ' — Test notification sent! ✅' : ' — Test failed ❌ (check token and credentials)'));
}

jsonResponse(true, null, 'Firebase settings saved successfully');

<?php
// api/fcm_helper.php
// Firebase Cloud Messaging v1 API (replaces deprecated legacy /fcm/send)
// Uses OAuth2 Service Account credentials from Firebase Console
// 
// HOW TO GET CREDENTIALS:
//   Firebase Console → Project Settings → Service Accounts
//   → Generate new private key → Download JSON
//   → Paste contents into Admin Panel → Settings → Firebase section

function sendPushNotification(string $token, string $title, string $body, array $data = []): bool {
    if (!$token) return false;

    $db = getDB();
    $rows = $db->query("SELECT setting_key, setting_value FROM app_settings
        WHERE setting_key IN ('fcm_service_account_json','fcm_project_id','fcm_enabled')")
        ->fetchAll(PDO::FETCH_KEY_PAIR);

    $enabled = ($rows['fcm_enabled'] ?? '0') === '1';
    if (!$enabled) {
        error_log("[FCM] Disabled in settings. Token: $token, Title: $title");
        return false;
    }

    $serviceAccountJson = $rows['fcm_service_account_json'] ?? '';
    $projectId          = $rows['fcm_project_id']           ?? '';

    if (!$serviceAccountJson || !$projectId) {
        error_log("[FCM] Not configured. Set service account JSON and project ID in admin settings.");
        return false;
    }

    $serviceAccount = json_decode($serviceAccountJson, true);
    if (!$serviceAccount) {
        error_log("[FCM] Invalid service account JSON");
        return false;
    }

    // Get OAuth2 access token
    $accessToken = getFcmAccessToken($serviceAccount);
    if (!$accessToken) {
        error_log("[FCM] Failed to get access token");
        return false;
    }

    // Send via FCM v1 API
    return sendFcmV1($projectId, $accessToken, $token, $title, $body, $data);
}

// ── Get OAuth2 Bearer token from service account ─────────────────────────
function getFcmAccessToken(array $sa): ?string {
    $now = time();
    $exp = $now + 3600;

    $header  = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $exp,
    ]));

    $sig_input = "$header.$payload";
    $private_key = openssl_pkey_get_private($sa['private_key']);
    if (!$private_key) {
        error_log("[FCM] Invalid private key in service account");
        return null;
    }

    $signature = '';
    openssl_sign($sig_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
    $jwt = "$sig_input." . base64url_encode($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response['access_token'] ?? null;
}

// ── Send message via FCM v1 ───────────────────────────────────────────────
function sendFcmV1(string $projectId, string $accessToken, string $token,
                   string $title, string $body, array $data): bool {
    // Convert all data values to strings (FCM requirement)
    $strData = array_map('strval', $data);

    $payload = json_encode([
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => $strData,
            'android' => [
                'notification' => [
                    'sound'        => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'priority' => 'high',
            ],
        ],
    ]);

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT    => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return true;
    }

    $err = json_decode($response, true);
    $errMsg = $err['error']['message'] ?? $response;
    error_log("[FCM v1] Error $httpCode: $errMsg | Token: " . substr($token, 0, 20) . '...');

    // If token is invalid/expired, clear it from DB
    if (in_array($httpCode, [400, 404]) &&
        str_contains($errMsg, 'registration-token-not-registered')) {
        $db = getDB();
        $db->prepare("UPDATE engineers  SET device_token=NULL WHERE device_token=?")->execute([$token]);
        $db->prepare("UPDATE customers  SET device_token=NULL WHERE device_token=?")->execute([$token]);
    }

    return false;
}

// ── Send to multiple tokens (topic broadcast) ────────────────────────────
function sendPushToMultiple(array $tokens, string $title, string $body, array $data = []): int {
    $sent = 0;
    foreach (array_filter($tokens) as $token) {
        if (sendPushNotification($token, $title, $body, $data)) $sent++;
    }
    return $sent;
}

// ── Helper ────────────────────────────────────────────────────────────────
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

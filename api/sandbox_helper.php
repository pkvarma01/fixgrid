<?php
// api/sandbox_helper.php — Sandbox.co.in KYC API helper
// Handles: auto token refresh, Aadhaar OTP generate/verify, PAN verify

// ── Get a valid access token (auto-refresh if expired) ───────────────────────
function sandboxGetToken(PDO $db): string {
    $apiKey    = getSettingValue('sandbox_api_key', '');
    $apiSecret = getSettingValue('sandbox_api_secret', '');
    $baseUrl   = rtrim(getSettingValue('sandbox_base_url', 'https://test-api.sandbox.co.in'), '/');

    if (!$apiKey) throw new Exception('Sandbox API key not configured in Admin → API Settings');

    // Check cached token
    $cached = getSettingValue('sandbox_auth_token', '');
    if ($cached) {
        // Decode JWT payload to check expiry
        $parts = explode('.', $cached);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_pad(
                strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 === 0 ? 0 : 4 - strlen($parts[1]) % 4, '=', STR_PAD_RIGHT
            )), true);
            if (!empty($payload['exp']) && $payload['exp'] > time() + 300) {
                return $cached; // Still valid with 5-min buffer
            }
        }
    }

    // Token expired or missing — refresh
    error_log("[Sandbox] Refreshing access token...");
    $ch = curl_init($baseUrl . '/authenticate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
            'x-api-secret: ' . $apiSecret,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_TIMEOUT    => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception('Sandbox connect error: ' . $err);

    $data = json_decode($resp, true);
    error_log("[Sandbox] /authenticate HTTP $status: " . substr($resp, 0, 300));

    if ($status !== 200) {
        throw new Exception('Sandbox token refresh failed (HTTP ' . $status . '): ' . ($data['message'] ?? substr($resp,0,200)));
    }

    // Sandbox wraps token under data.access_token
    $token = $data['data']['access_token'] ?? $data['access_token'] ?? '';
    if (!$token) throw new Exception('Sandbox token not found in response: ' . substr($resp, 0, 300));

    // Save new token to settings
    $db->prepare("UPDATE app_settings SET setting_value=? WHERE setting_key='sandbox_auth_token'")
       ->execute([$token]);

    return $token;
}

// ── Helper: make authenticated Sandbox API call ───────────────────────────────
function sandboxCall(PDO $db, string $method, string $endpoint, array $body = []): array {
    $baseUrl = rtrim(getSettingValue('sandbox_base_url', 'https://test-api.sandbox.co.in'), '/');
    $token   = sandboxGetToken($db);

    $ch = curl_init($baseUrl . $endpoint);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'x-api-key: ' . getSettingValue('sandbox_api_key', ''),
            
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr   = curl_error($ch);
    curl_close($ch);

    error_log("[Sandbox] $method $endpoint → HTTP $status: " . substr($resp, 0, 500));

    if ($cerr) throw new Exception('Sandbox request failed: ' . $cerr);

    $data = json_decode($resp, true);
    if (!$data) throw new Exception('Sandbox returned invalid JSON (HTTP ' . $status . ')');

    return ['status' => $status, 'data' => $data];
}

// ── Generate Aadhaar OTP ──────────────────────────────────────────────────────
function sandboxAadhaarGenerateOtp(PDO $db, string $aadhaarNumber): array {
    // Sandbox endpoint: POST /kyc/aadhaar/okyc/otp
    // Required fields per Sandbox docs + confirmed from working error_log entries
    $result = sandboxCall($db, 'POST', '/kyc/aadhaar/okyc/otp', [
        '@entity'        => 'in.co.sandbox.kyc.aadhaar.okyc.otp.request',
        'aadhaar_number' => $aadhaarNumber,
        'consent'        => 'Y',
        'reason'         => 'For KYC verification',
    ]);
    $d = $result['data'];
    // Sandbox returns: {code:200, data:{reference_id:73686661 (int), message:"OTP sent"}}
    // NOTE: field is "reference_id" (integer), NOT "request_id"
    if ($result['status'] !== 200 || empty($d['data']['reference_id'])) {
        $msg = $d['data']['message'] ?? $d['message'] ?? 'Failed to send OTP';
        throw new Exception($msg);
    }
    return [
        'request_id' => (string)$d['data']['reference_id'], // stored as string in DB, cast to int on verify
        'message'    => $d['data']['message'] ?? 'OTP sent to Aadhaar-linked mobile number',
    ];
}

// ── Verify Aadhaar OTP ────────────────────────────────────────────────────────
function sandboxAadhaarVerifyOtp(PDO $db, string $requestId, string $otp): array {
    // Sandbox endpoint: POST /kyc/aadhaar/okyc/otp/verify
    // CRITICAL: field must be "reference_id" (integer), NOT "request_id" (string)
    // Sandbox returns HTTP 400 "Invalid request body" if wrong field name or type is used
    $result = sandboxCall($db, 'POST', '/kyc/aadhaar/okyc/otp/verify', [
        '@entity'      => 'in.co.sandbox.kyc.aadhaar.okyc.otp.verify.request',
        'reference_id' => (int)$requestId,   // must be integer
        'otp'          => $otp,
    ]);
    $d = $result['data'];
    // Returns: {code:200, data:{status:"SUCCESS", data:{name, dob, gender, address, zip, ...}}}
    if ($result['status'] !== 200) {
        $msg = $d['data']['message'] ?? $d['message'] ?? 'OTP verification failed';
        throw new Exception($msg);
    }
    $inner = $d['data'] ?? [];
    // Sandbox returns status VALID (not SUCCESS) for successful verification
    $status = strtoupper($inner['status'] ?? '');
    if (!in_array($status, ['VALID', 'SUCCESS'])) {
        throw new Exception($inner['message'] ?? 'OTP verification failed. Please try again.');
    }
    // Parse address — can be array or string
    $addrRaw = $inner['address'] ?? '';
    if (is_array($addrRaw)) {
        $addrParts = array_filter([
            $addrRaw['house']        ?? '',
            $addrRaw['street']       ?? '',
            $addrRaw['landmark']     ?? '',
            $addrRaw['vtc']          ?? '',
            $addrRaw['post_office']  ?? '',
            $addrRaw['subdistrict']  ?? '',
            $addrRaw['district']     ?? '',
            $addrRaw['state']        ?? '',
            $addrRaw['pincode']      ?? '',
            $addrRaw['country']      ?? '',
        ]);
        $address = implode(', ', $addrParts);
    } else {
        $address = $inner['full_address'] ?? $addrRaw ?? '';
    }
    return [
        'name'    => $inner['name']           ?? '',
        'dob'     => $inner['date_of_birth']  ?? $inner['dob'] ?? '',
        'gender'  => $inner['gender']         ?? '',
        'address' => $address,
        'masked'  => $inner['maskedAadhaarNumber'] ?? $inner['masked_aadhaar'] ?? '',
        'ref_id'  => (string)($inner['reference_id'] ?? $requestId),
    ];
}

// ── Verify PAN ────────────────────────────────────────────────────────────────
function sandboxVerifyPan(PDO $db, string $panNumber): array {
    // Sandbox endpoint: POST /kyc/pan
    $result = sandboxCall($db, 'POST', '/kyc/pan', [
        'pan' => strtoupper($panNumber),
    ]);
    $d = $result['data'];
    if ($result['status'] !== 200) {
        $msg = $d['data']['message'] ?? $d['message'] ?? 'PAN verification failed';
        throw new Exception($msg);
    }
    $inner = $d['data'] ?? [];
    return [
        'name'    => $inner['name']          ?? $inner['full_name'] ?? '',
        'status'  => $inner['status']        ?? 'VALID',
        'type'    => $inner['pan_type']      ?? '',
    ];
}

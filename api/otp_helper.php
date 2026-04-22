<?php
// api/otp_helper.php — Send OTP via WhatsApp and/or Email
// Called by register.php after generating OTP

function sendOtp(string $phone, string $otp, string $name = '', $db = null): array {
    $results = [];
    $message = "Your Hridya Tech OTP is: *$otp*\nValid for 10 minutes. Do not share with anyone.";

    // Load settings from DB
    $settings = [];
    if ($db) {
        $rows = $db->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $rows;
    }

    // ── 1. WhatsApp ─────────────────────────────────────────
    if (!empty($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] === '1') {
        $provider = $settings['whatsapp_provider'] ?? 'twilio';
        if ($provider === 'twilio') {
            $results['whatsapp'] = sendWhatsAppTwilio($phone, $message, $settings);
        } elseif ($provider === 'meta') {
            $results['whatsapp'] = sendWhatsAppMeta($phone, $otp, $name, $settings);
        }
    }

    // ── 2. Email ─────────────────────────────────────────────
    if (!empty($settings['smtp_enabled']) && $settings['smtp_enabled'] === '1') {
        // Try to find customer email from DB
        $email = '';
        if ($db) {
            $row = $db->prepare("SELECT email FROM customers WHERE phone=? LIMIT 1");
            $row->execute([$phone]);
            $email = $row->fetchColumn() ?: '';
        }
        if ($email) {
            $results['email'] = sendOtpEmail($email, $otp, $name, $settings);
        }
    }

    // ── 3. Fallback: log OTP for dev/testing ──────────────────
    if (empty($results)) {
        error_log("[OTP] Phone: $phone | OTP: $otp | Time: " . date('Y-m-d H:i:s'));
        $results['fallback'] = 'OTP logged to server error_log (configure WhatsApp/Email in admin settings)';
    }

    return $results;
}

// ── Twilio WhatsApp ──────────────────────────────────────────
function sendWhatsAppTwilio(string $phone, string $message, array $s): string {
    $sid    = $s['twilio_account_sid']   ?? '';
    $token  = $s['twilio_auth_token']    ?? '';
    $from   = $s['twilio_whatsapp_from'] ?? 'whatsapp:+14155238886';

    if (!$sid || !$token) return 'Twilio not configured';

    // Normalize phone to E.164
    $to = 'whatsapp:' . normalizePhone($phone);

    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => "$sid:$token",
        CURLOPT_POSTFIELDS     => http_build_query([
            'From' => $from,
            'To'   => $to,
            'Body' => $message,
        ]),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return $data['sid'] ? 'sent:' . $data['sid'] : 'sent';
    }
    $err = json_decode($response, true);
    error_log("[WhatsApp Twilio] Error $httpCode: " . ($err['message'] ?? $response));
    return "error:$httpCode";
}

// ── Meta WhatsApp Cloud API ──────────────────────────────────
function sendWhatsAppMeta(string $phone, string $otp, string $name, array $s): string {
    $token    = $s['meta_wa_token']    ?? '';
    $phoneId  = $s['meta_wa_phone_id'] ?? '';
    $template = $s['meta_wa_template'] ?? 'otp_message';

    if (!$token || !$phoneId) return 'Meta not configured';

    $to = normalizePhone($phone);

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'template',
        'template'          => [
            'name'       => $template,
            'language'   => ['code' => 'en'],
            'components' => [[
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $otp],
                ],
            ]],
        ],
    ]);

    $ch = curl_init("https://graph.facebook.com/v18.0/$phoneId/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) return 'sent';
    error_log("[WhatsApp Meta] Error $httpCode: $response");
    return "error:$httpCode";
}

// ── SMTP Email ───────────────────────────────────────────────
function sendOtpEmail(string $email, string $otp, string $name, array $s): string {
    $host     = $s['smtp_host']       ?? '';
    $port     = (int)($s['smtp_port'] ?? 587);
    $user     = $s['smtp_user']       ?? '';
    $pass     = $s['smtp_pass']       ?? '';
    $fromMail = $s['smtp_from_email'] ?? $user;
    $fromName = $s['smtp_from_name']  ?? 'Hridya Tech';

    if (!$host || !$user || !$pass) return 'SMTP not configured';

    $greeting = $name ? "Hello $name," : "Hello,";
    $subject  = "Your OTP: $otp — Hridya Tech";
    $body     = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:20px">
  <div style="background:#4F6EF7;padding:20px;border-radius:12px 12px 0 0;text-align:center">
    <div style="color:#fff;font-size:22px;font-weight:800">🔧 Hridya Tech</div>
  </div>
  <div style="background:#f9f9f9;padding:24px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb">
    <p style="color:#374151;font-size:15px">$greeting</p>
    <p style="color:#374151;font-size:15px">Your OTP for Hridya Tech is:</p>
    <div style="background:#fff;border:2px solid #4F6EF7;border-radius:10px;padding:16px;text-align:center;margin:20px 0">
      <div style="font-size:36px;font-weight:800;letter-spacing:10px;color:#4F6EF7">$otp</div>
      <div style="font-size:12px;color:#9ca3af;margin-top:6px">Valid for 10 minutes</div>
    </div>
    <p style="color:#6b7280;font-size:13px">Do not share this OTP with anyone.</p>
  </div>
</body>
</html>
HTML;

    // Use PHP SMTP with stream_socket_client (no library needed)
    return sendSmtp($host, $port, $user, $pass, $fromMail, $fromName, $email, $subject, $body);
}

function sendSmtp(string $host, int $port, string $user, string $pass,
                  string $from, string $fromName, string $to,
                  string $subject, string $htmlBody): string {
    $timeout = 15;
    $useSSL  = ($port === 465);   // SSL/TLS direct
    $useTLS  = ($port === 587);   // STARTTLS upgrade

    // Port 465: connect with ssl:// directly
    // Port 587: connect plain then STARTTLS
    // Port 25:  plain (usually blocked on shared hosting)
    $remote = ($useSSL ? "ssl://$host:$port" : "tcp://$host:$port");

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    $sock = @stream_socket_client($remote, $errno, $errstr, $timeout,
        STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) {
        error_log("[SMTP] Connect failed to $remote: $errstr ($errno)");
        return "connect_error: $errstr";
    }

    stream_set_timeout($sock, $timeout);

    $read = function() use ($sock) {
        $resp = '';
        while ($line = fgets($sock, 515)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $resp;
    };

    $cmd = function(string $c) use ($sock, $read) {
        fwrite($sock, $c . "\r\n");
        return $read();
    };

    $read(); // banner

    $domain = gethostname() ?: 'localhost';
    $ehlo = $cmd("EHLO $domain");

    if ($useTLS && strpos($ehlo, 'STARTTLS') !== false) {
        $cmd('STARTTLS');
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd("EHLO $domain");
    }

    $cmd("AUTH LOGIN");
    $cmd(base64_encode($user));
    $resp = $cmd(base64_encode($pass));
    if (substr(trim($resp), 0, 3) !== '235') {
        fclose($sock);
        error_log("[SMTP] Auth failed: $resp");
        return 'auth_failed';
    }

    $cmd("MAIL FROM:<$from>");
    $cmd("RCPT TO:<$to>");
    $cmd('DATA');

    $boundary = md5(uniqid());
    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n"
              . "To: $to\r\n"
              . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
              . "MIME-Version: 1.0\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n";

    $resp = $cmd($headers . "\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.");
    $cmd('QUIT');
    fclose($sock);

    if (substr(trim($resp), 0, 3) === '250') return 'sent';
    error_log("[SMTP] Send failed: $resp");
    return 'smtp_error';
}

function normalizePhone(string $phone): string {
    // Remove spaces, dashes, parens
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    // Add +91 for India if no country code
    if (!str_starts_with($phone, '+')) {
        if (strlen($phone) === 10) $phone = '+91' . $phone;
        else $phone = '+' . $phone;
    }
    return $phone;
}

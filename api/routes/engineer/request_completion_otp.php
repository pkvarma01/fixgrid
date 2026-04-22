<?php
// routes/engineer/request_completion_otp.php
// Send a 4-digit OTP to the customer to confirm job completion
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Verify job belongs to this engineer and is in a completable state
$stmt = $db->prepare("
    SELECT j.*, c.phone AS customer_phone, c.name AS customer_name,
           c.email AS customer_email, c.device_token AS customer_device_token
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    WHERE j.id = ? AND j.engineer_id = ?
");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, null, 'Job not found or not assigned to you', 404);

$allowedStatuses = ['working', 'arrived', 'accepted', 'on_the_way', 'assigned'];
if (!in_array($job['status'], $allowedStatuses)) {
    jsonResponse(false, null, 'Job must be in progress to request completion OTP (current: ' . $job['status'] . ')', 422);
}

// Generate 4-digit OTP
$otp    = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP — table already created by migration SQL
// Delete any existing OTP for this job first
$db->prepare("DELETE FROM completion_otps WHERE job_id = ?")->execute([$jobId]);
$db->prepare("INSERT INTO completion_otps (job_id, otp, expires_at, used) VALUES (?, ?, ?, 0)")
   ->execute([$jobId, $otp, $expiry]);

// ── Send via push notification (always works) ─────────────────────────────────
$pushSent = sendPushNotification(
    $job['customer_device_token'] ?? '',
    'Job Completion OTP 🔐',
    'Your OTP to confirm job completion: ' . $otp . '. Valid 10 minutes. Share only with your engineer.',
    ['type' => 'completion_otp', 'job_id' => $jobId, 'otp' => $otp]
);

// ── Log in customer notifications table (visible in app) ──────────────────────
logNotification(
    (int)$job['customer_id'],
    'customer',
    'Job Completion OTP 🔐',
    'Your OTP: ' . $otp . ' (valid 10 min). Share this with the engineer to confirm work is done.',
    ['job_id' => $jobId, 'otp' => $otp]
);

// ── Send via email if SMTP is configured ─────────────────────────────────────
$emailSent = false;
$smtpEnabled = getSettingValue('smtp_enabled', '0');
if ($smtpEnabled === '1' && !empty($job['customer_email'])) {
    if (file_exists(dirname(__DIR__, 2) . '/otp_helper.php')) {
        require_once dirname(__DIR__, 2) . '/otp_helper.php';
        // Correct signature: sendOtpEmail(email, otp, name, settingsArray)
        $smtpSettings = [
            'smtp_host'       => getSettingValue('smtp_host', ''),
            'smtp_port'       => getSettingValue('smtp_port', '465'),
            'smtp_user'       => getSettingValue('smtp_user', ''),
            'smtp_pass'       => getSettingValue('smtp_pass', ''),
            'smtp_from_email' => getSettingValue('smtp_from_email', ''),
            'smtp_from_name'  => getSettingValue('smtp_from_name', 'Hridya Tech'),
        ];
        $result    = sendOtpEmail($job['customer_email'], $otp, $job['customer_name'], $smtpSettings);
        $emailSent = ($result === 'sent' || substr($result,0,3) === '250');
    }
}

// ── Send via WhatsApp if configured ──────────────────────────────────────────
$whatsappSent = false;
$waEnabled = getSettingValue('whatsapp_enabled', '0');
if ($waEnabled === '1' && !empty($job['customer_phone'])) {
    if (file_exists(dirname(__DIR__, 2) . '/otp_helper.php')) {
        if (!function_exists('sendOtp')) require_once dirname(__DIR__, 2) . '/otp_helper.php';
        $waResult     = sendOtp($job['customer_phone'], $otp, $job['customer_name'], $db);
        $whatsappSent = ($waResult['status'] ?? '') === 'sent';
    }
}

// Mask phone for response
$phone    = $job['customer_phone'] ?? '';
$masked   = strlen($phone) > 6
    ? substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3)
    : '***';

jsonResponse(true, [
    'otp_sent'       => true,
    'customer_phone' => $masked,
    'push_sent'      => $pushSent,
    'email_sent'     => $emailSent,
    'whatsapp_sent'  => $whatsappSent,
    'expires_minutes'=> 10,
    'channels'       => array_filter([
        $pushSent      ? 'push notification' : null,
        $emailSent     ? 'email'             : null,
        $whatsappSent  ? 'whatsapp'          : null,
    ]),
], 'OTP sent to customer successfully');

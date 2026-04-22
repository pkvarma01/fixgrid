<?php
// routes/auth/engineer_register.php
// Simple engineer registration — no KYC, instant account activation
require_once dirname(__DIR__, 2) . '/config.php';

set_exception_handler(function($e) {
    error_log('[engineer_register] Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    jsonResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
});

$step = trim($input['step'] ?? '');
$db   = getDB();

// ════════════════════════════════════════════════════
// STEP 1 — Basic info + password → instant activation
// ════════════════════════════════════════════════════
if ($step === 'basic') {
    $name     = trim($input['name']     ?? '');
    $phone    = trim($input['phone']    ?? '');
    $email    = trim($input['email']    ?? '');
    $password = trim($input['password'] ?? '');
    $city     = trim($input['city']     ?? '');

    if (!$name || !$phone || !$email || !$password)
        jsonResponse(false, null, 'Name, phone, email and password are required', 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(false, null, 'Invalid email address', 422);
    if (strlen($password) < 6)
        jsonResponse(false, null, 'Password must be at least 6 characters', 422);

    // Check duplicate
    $dup = $db->prepare("SELECT id, is_active FROM engineers WHERE email=? OR phone=?");
    $dup->execute([$email, $phone]);
    $existing = $dup->fetch();

    if ($existing) {
        if ($existing['is_active'] == 1)
            jsonResponse(false, null, 'Email or phone already registered. Please login.', 409);
        // Resume — update basic info and activate
        $db->prepare("UPDATE engineers SET name=?,phone=?,email=?,password=?,city=?,
            is_active=1, kyc_status='approved', status='offline', updated_at=NOW() WHERE id=?")
           ->execute([$name, $phone, $email, password_hash($password, PASSWORD_BCRYPT), $city, $existing['id']]);
        jsonResponse(true, ['engineer_id' => $existing['id'], 'next_step' => 'selfie'],
            'Account updated. Please upload your selfie to complete registration.');
    }

    $db->prepare("INSERT INTO engineers
        (name, phone, email, password, city, is_active, kyc_status, status, created_at)
        VALUES (?, ?, ?, ?, ?, 0, 'pending', 'offline', NOW())")
       ->execute([$name, $phone, $email, password_hash($password, PASSWORD_BCRYPT), $city]);

    $engId = $db->lastInsertId();

    jsonResponse(true,
        ['engineer_id' => $engId, 'next_step' => 'selfie'],
        'Basic info saved. Please upload your selfie.');
}

// ════════════════════════════════════════════════════
// STEP 2 — Selfie photo → activate account
// ════════════════════════════════════════════════════
if ($step === 'selfie') {
    $engId = (int)($input['engineer_id'] ?? 0);
    if (!$engId) jsonResponse(false, null, 'engineer_id required', 422);
    if (empty($_FILES['selfie']['tmp_name']))
        jsonResponse(false, null, 'Please take a selfie photo', 422);

    $err = $_FILES['selfie']['error'] ?? 0;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
        jsonResponse(false, null, 'Selfie too large. Please take a lower-resolution photo.', 413);
    if ($err !== UPLOAD_ERR_OK)
        jsonResponse(false, null, 'Selfie upload error (code '.$err.'). Please try again.', 500);

    $dir = UPLOAD_DIR . 'kyc/selfies/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $selfieUrl = uploadFile($_FILES['selfie'], 'kyc/selfies');
    if (!$selfieUrl) jsonResponse(false, null, 'Could not save selfie. Please try again.', 500);

    // Activate account immediately
    $db->prepare("UPDATE engineers SET
        kyc_selfie_url=?,
        kyc_status='approved',
        is_active=1,
        kyc_reviewed_at=NOW(),
        updated_at=NOW()
        WHERE id=?")
       ->execute([$selfieUrl, $engId]);

    // Notify admins (best-effort)
    try {
        $adminCols = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins'")->fetchAll(PDO::FETCH_COLUMN);
        $selectCol = in_array('device_token', $adminCols) ? 'id, name, device_token' : 'id, name';
        $admins = $db->query("SELECT $selectCol FROM admins WHERE is_active=1")->fetchAll();
        $eng = $db->query("SELECT name FROM engineers WHERE id=$engId")->fetch();
        foreach ($admins as $a) {
            $token = $a['device_token'] ?? '';
            if ($token) sendPushNotification($token,
                '🆕 New Engineer Registered',
                ($eng['name'] ?? 'Engineer') . ' has registered and is now active.',
                ['type' => 'new_engineer', 'engineer_id' => $engId]);
        }
    } catch(Exception $e) {}

    jsonResponse(true, ['status' => 'approved', 'selfie_url' => $selfieUrl],
        '🎉 Registration complete! Your account is now active. You can login and start accepting jobs.');
}

// ════════════════════════════════════════════════════
// STEP — submit (kept for compatibility, redirects to selfie logic)
// ════════════════════════════════════════════════════
if ($step === 'submit') {
    $engId = (int)($input['engineer_id'] ?? 0);
    if (!$engId) jsonResponse(false, null, 'engineer_id required', 422);

    $db->prepare("UPDATE engineers SET
        kyc_status='approved', is_active=1, kyc_reviewed_at=NOW(), updated_at=NOW()
        WHERE id=?")
       ->execute([$engId]);

    jsonResponse(true, ['status' => 'approved'],
        '🎉 Account is now active. You can login and start accepting jobs.');
}

jsonResponse(false, null, 'Invalid step: ' . $step, 400);

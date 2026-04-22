<?php
// routes/engineer/verify_completion_otp.php — Verify customer OTP then complete job
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
$otp   = trim($input['otp']    ?? '');
$notes = trim($input['notes']  ?? '');

if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
if (!$otp)   jsonResponse(false, null, 'otp required', 422);

// ── 1. Verify OTP ──────────────────────────────────────────────────────────────
$otpStmt = $db->prepare("
    SELECT * FROM completion_otps
    WHERE job_id = ? AND otp = ? AND expires_at > NOW() AND used = 0
");
$otpStmt->execute([$jobId, $otp]);
$otpRow = $otpStmt->fetch();

if (!$otpRow) jsonResponse(false, null, 'Invalid or expired OTP. Ask customer to check their notifications.', 400);

// Mark OTP used immediately to prevent replay
$db->prepare("UPDATE completion_otps SET used = 1 WHERE id = ?")->execute([$otpRow['id']]);

// ── 2. Fetch job ───────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT j.*, COALESCE(s.platform_charge_pct, 20) AS svc_platform_pct
    FROM jobs j
    LEFT JOIN service_types s ON j.service_id = s.id
    WHERE j.id = ? AND j.engineer_id = ?
");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, null, 'Job not found or not assigned to you', 404);
if ($job['status'] === 'completed') jsonResponse(false, null, 'Job already completed', 409);

// ── 3. Calculate earnings ─────────────────────────────────────────────────────
$totalAmount     = (float)($job['final_amount'] ?? $job['amount'] ?? 0);
$visitCharge     = (float)($job['visit_charge'] ?? 0);
$fullAmount      = $totalAmount + $visitCharge;
$platformPct     = (float)(getSettingValue('platform_charge_pct', $job['svc_platform_pct']));
$platformCharge  = round($fullAmount * $platformPct / 100, 2);
$engineerEarning = round($fullAmount - $platformCharge, 2);

// Parts total
$partsStmt = $db->prepare("SELECT COALESCE(SUM(jp.qty * jp.unit_price), 0) AS parts_total FROM job_parts_used jp WHERE jp.job_id = ?");
$partsStmt->execute([$jobId]);
$partsTotal = (float)$partsStmt->fetchColumn();

// ── 4. Complete the job ───────────────────────────────────────────────────────
$db->prepare("
    UPDATE jobs
    SET status = 'completed', end_time = NOW(), platform_charge = ?,
        notes = CASE WHEN ? != '' THEN ? ELSE notes END,
        updated_at = NOW()
    WHERE id = ?
")->execute([$platformCharge, $notes, $notes, $jobId]);

// Free engineer
$db->prepare("UPDATE engineers SET status = 'available' WHERE id = ?")->execute([$engineer['id']]);

// Create / update invoice
$existing = $db->query("SELECT id FROM invoices WHERE job_id = $jobId LIMIT 1")->fetchColumn();
if (!$existing) {
    $invoiceNumber = 'INV-' . date('ymd') . '-' . rand(1000, 9999);
    $db->prepare("INSERT INTO invoices (invoice_number, job_id, customer_id, total, status, payment_method)
        VALUES (?, ?, ?, ?, 'draft', 'cash')")
       ->execute([$invoiceNumber, $jobId, $job['customer_id'], $fullAmount]);
}

// Credit engineer wallet — guard against double-credit
$alreadyCredited = $db->prepare("SELECT COUNT(*) FROM engineer_wallet_transactions WHERE engineer_id=? AND job_id=? AND type='credit'");
$alreadyCredited->execute([$engineer['id'], $jobId]);
if ((int)$alreadyCredited->fetchColumn() === 0) {
    $db->prepare("INSERT IGNORE INTO engineer_wallet (engineer_id, balance) VALUES (?, 0)")->execute([$engineer['id']]);
    $db->prepare("UPDATE engineer_wallet SET balance = balance + ? WHERE engineer_id = ?")->execute([$engineerEarning, $engineer['id']]);
    $db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id, job_id, type, amount, description)
        VALUES (?, ?, 'credit', ?, ?)")
       ->execute([$engineer['id'], $jobId, $engineerEarning, 'Earnings for job #' . $job['job_number']]);
}

// Notify customer
$custRow = $db->query("SELECT device_token FROM customers WHERE id = " . (int)$job['customer_id'])->fetch();
sendPushNotification(
    $custRow['device_token'] ?? '',
    'Job Completed ✅',
    'Your job #' . $job['job_number'] . ' is done. Rate your engineer!',
    ['type' => 'job_completed', 'job_id' => $jobId]
);
logNotification((int)$job['customer_id'], 'customer', 'Job Completed ✅',
    'Your job is complete. Please rate your engineer.', ['job_id' => $jobId]);

jsonResponse(true, [
    'job_id'          => $jobId,
    'job_number'      => $job['job_number'],
    'status'          => 'completed',
    'full_amount'     => $fullAmount,
    'platform_charge' => $platformCharge,
    'your_earning'    => $engineerEarning,
    'parts_total'     => $partsTotal,
    'otp_verified'    => true,
], 'Job completed successfully! ✅ Earnings: ₹' . number_format($engineerEarning, 2));

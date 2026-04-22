<?php
// routes/engineer/complete_job.php — Mark job complete + auto-credit engineer wallet
// FIX: wallet_transactions uses ref_id (not job_id)
// FIX: engineer_wallet INSERT uses safe UPSERT without total_earned/total_withdrawn
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
$notes = trim($input['notes'] ?? '');
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$stmt = $db->prepare("SELECT j.*, COALESCE(s.platform_charge_pct, 20) AS svc_platform_pct
    FROM jobs j LEFT JOIN service_types s ON j.service_id=s.id
    WHERE j.id=? AND j.engineer_id=?");
$stmt->execute([$jobId, $engineer['id']]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, null, 'Job not found', 404);
if ($job['status'] === 'completed') jsonResponse(false, null, 'Job already completed', 409);
if (!in_array($job['status'], ['working','arrived','on_the_way','accepted','assigned'])) {
    jsonResponse(false, null, "Cannot complete job with status '{$job['status']}'", 422);
}

// Calculate amounts
$totalAmount    = (float)($job['final_amount'] ?? $job['amount'] ?? 0);
$visitCharge    = (float)($job['visit_charge'] ?? 0);
$fullAmount     = $totalAmount + $visitCharge;
$platformPct    = (float)($db->query("SELECT setting_value FROM app_settings WHERE setting_key='platform_charge_pct'")->fetchColumn() ?: $job['svc_platform_pct']);
$platformCharge = round($fullAmount * $platformPct / 100, 2);
$engineerEarning = round($fullAmount - $platformCharge, 2);

// Complete the job
$db->prepare("UPDATE jobs SET status='completed', end_time=NOW(), platform_charge=?,
    notes=CASE WHEN ? != '' THEN ? ELSE notes END, updated_at=NOW() WHERE id=?")
   ->execute([$platformCharge, $notes, $notes, $jobId]);

// Free engineer
$db->prepare("UPDATE engineers SET status='available' WHERE id=?")->execute([$engineer['id']]);

// Create invoice if not exists
$existing = $db->query("SELECT id FROM invoices WHERE job_id=$jobId LIMIT 1")->fetchColumn();
if (!$existing) {
    $invoiceNumber = 'INV-'.date('ymd').'-'.rand(1000,9999);
    $db->prepare("INSERT INTO invoices (invoice_number,job_id,customer_id,total,status,payment_method) VALUES (?,?,?,?,'draft','cash')")
       ->execute([$invoiceNumber, $jobId, $job['customer_id'], $fullAmount]);
}

// Credit engineer wallet — guard against double-credit
$alreadyCredited = $db->prepare("SELECT COUNT(*) FROM engineer_wallet_transactions WHERE engineer_id=? AND job_id=? AND type='credit'");
$alreadyCredited->execute([$engineer['id'], $jobId]);
if ((int)$alreadyCredited->fetchColumn() === 0) {
    $db->prepare("INSERT IGNORE INTO engineer_wallet (engineer_id, balance) VALUES (?,0)")->execute([$engineer['id']]);
    $db->prepare("UPDATE engineer_wallet SET balance=balance+? WHERE engineer_id=?")->execute([$engineerEarning, $engineer['id']]);
    $db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id,job_id,type,amount,description) VALUES (?,?,?,?,?)")
       ->execute([$engineer['id'], $jobId, 'credit', $engineerEarning,
           'Job #'.$job['job_number'].' completed. Platform fee: ₹'.number_format($platformCharge,2)]);
}

// FIX: wallet_transactions uses ref_id (not job_id)
$custWallet = $db->prepare("SELECT balance FROM customer_wallet WHERE customer_id=?");
$custWallet->execute([$job['customer_id']]);
$custBalance = (float)($custWallet->fetchColumn() ?: 0);
if ($custBalance >= $fullAmount && $fullAmount > 0) {
    $db->prepare("UPDATE customer_wallet SET balance=balance-? WHERE customer_id=?")
       ->execute([$fullAmount, $job['customer_id']]);
    // FIX: use ref_id not job_id
    $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description,ref_id) VALUES (?,?,?,?,?)")
       ->execute([$job['customer_id'], 'debit', $fullAmount, 'Payment for job #'.$job['job_number'], $jobId]);
}

// Notify customer
$cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
$cust->execute([$job['customer_id']]);
$custRow = $cust->fetch();
sendPushNotification(
    $custRow['device_token'] ?? '',
    'Job Completed ✅',
    'Your job has been completed. Total: ₹'.number_format($fullAmount,2).'. Please rate your experience.',
    ['type'=>'job_completed','job_id'=>$jobId]
);
logNotification($job['customer_id'],'customer','Job Completed','Job #'.$job['job_number'].' completed.',['job_id'=>$jobId]);

jsonResponse(true, [
    'job_id'          => $jobId,
    'status'          => 'completed',
    'full_amount'     => $fullAmount,
    'total_amount'    => $fullAmount,
    'platform_charge' => $platformCharge,
    'your_earning'    => $engineerEarning,
    'payment_method'  => $job['payment_method'] ?? 'cash',
], 'Job completed! You earned ₹'.number_format($engineerEarning,2));

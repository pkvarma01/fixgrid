<?php
// routes/engineer/quotation.php
// Engineer requests a parts quotation for a job
// Flow: engineer submits parts needed → job status = awaiting_quotation
//       admin reviews → sends quotation to customer → customer approves/rejects
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();
$engineerId = (int)$engineer['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get quotation status for a job
    $jobId = (int)($input['job_id'] ?? 0);
    if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
    $stmt = $db->prepare("SELECT q.*, ts.label AS slot_label FROM job_quotations q
        LEFT JOIN time_slots ts ON q.revisit_slot_id = ts.id
        WHERE q.job_id = ? AND q.engineer_id = ? ORDER BY q.created_at DESC LIMIT 1");
    $stmt->execute([$jobId, $engineerId]);
    jsonResponse(true, $stmt->fetch() ?: null);
}

// POST: request quotation
$jobId       = (int)($input['job_id']      ?? 0);
$notes       = trim($input['notes']        ?? '');
$partsJson   = $input['parts']             ?? []; // [{name, qty, est_price, unit}]
$visitCharge = (float)($input['visit_charge'] ?? 0);

if (!$jobId || !$notes) jsonResponse(false, null, 'job_id and notes required', 422);

// Verify job belongs to engineer and is in a valid state
$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=?");
$jobStmt->execute([$jobId, $engineerId]);
$job = $jobStmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);
if (!in_array($job['status'], ['working','arrived','on_the_way','accepted','assigned'])) {
    jsonResponse(false, null, "Cannot request quotation for job with status '{$job['status']}'", 422);
}

// Create quotation request
$db->prepare("INSERT INTO job_quotations (job_id, engineer_id, request_notes, parts_details, status)
    VALUES (?,?,?,?,'requested')")
   ->execute([$jobId, $engineerId, $notes, json_encode($partsJson)]);
$quotationId = $db->lastInsertId();

// Update job status
$db->prepare("UPDATE jobs SET status='awaiting_quotation', updated_at=NOW() WHERE id=?")->execute([$jobId]);

// If engineer collects visit charge separately before quotation
if ($visitCharge > 0) {
    $db->prepare("UPDATE jobs SET visit_charge=? WHERE id=?")->execute([$visitCharge, $jobId]);
}

// Notify admin (log notification for admin user_id=0 means admin)
logNotification(0, 'admin', 'Quotation Requested',
    'Engineer ' . $engineer['name'] . ' requested parts quotation for job #' . $job['job_number'],
    ['job_id' => $jobId, 'quotation_id' => $quotationId, 'type' => 'quotation_requested']);

// Notify customer
$cust = $db->prepare("SELECT device_token, name FROM customers WHERE id=?");
$cust->execute([$job['customer_id']]);
$custRow = $cust->fetch();
sendPushNotification($custRow['device_token'] ?? '', 'Parts Required',
    'Engineer has identified parts needed. We will send you a quotation shortly.',
    ['type' => 'quotation_requested', 'job_id' => $jobId]);
logNotification($job['customer_id'], 'customer', 'Parts Required',
    'Engineer identified parts needed for job #' . $job['job_number'] . '. Quotation coming soon.',
    ['job_id' => $jobId]);

jsonResponse(true, ['quotation_id' => $quotationId],
    'Quotation request submitted. Our team will prepare the quote and contact the customer.');

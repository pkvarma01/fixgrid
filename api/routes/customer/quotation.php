<?php
// routes/customer/quotation.php — Customer approves or rejects a quotation
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $jobId = (int)($input['job_id'] ?? 0);
    if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
    $stmt = $db->prepare("
        SELECT q.*, ts.label AS slot_label
        FROM job_quotations q
        LEFT JOIN time_slots ts ON q.revisit_slot_id = ts.id
        JOIN jobs j ON q.job_id = j.id
        WHERE q.job_id = ? AND j.customer_id = ?
        ORDER BY q.created_at DESC LIMIT 1
    ");
    $stmt->execute([$jobId, $customer['id']]);
    jsonResponse(true, $stmt->fetch() ?: null);
}

$quotationId = (int)($input['quotation_id'] ?? 0);
$action      = $input['action'] ?? ''; // 'approve' or 'reject'
if (!$quotationId || !in_array($action, ['approve','reject'])) {
    jsonResponse(false, null, 'quotation_id and action (approve|reject) required', 422);
}

$qStmt = $db->prepare("SELECT q.*, j.customer_id, j.job_number, j.engineer_id FROM job_quotations q JOIN jobs j ON q.job_id=j.id WHERE q.id=? AND j.customer_id=?");
$qStmt->execute([$quotationId, $customer['id']]);
$q = $qStmt->fetch();
if (!$q) jsonResponse(false, null, 'Quotation not found', 404);
if ($q['status'] !== 'sent') jsonResponse(false, null, 'Quotation is not pending your response', 422);

if ($action === 'approve') {
    $db->prepare("UPDATE job_quotations SET status='approved', customer_approved_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$quotationId]);
    // final_amount = quotation_amount (already has first visit charge deducted by admin)
    $db->prepare("UPDATE jobs SET status='revisit_scheduled', scheduled_date=?, slot_id=?, final_amount=?, updated_at=NOW() WHERE id=?")
       ->execute([$q['revisit_date'], $q['revisit_slot_id'], $q['quotation_amount'], $q['job_id']]);

    // Notify engineer
    $eng = $db->prepare("SELECT device_token, name FROM engineers WHERE id=?");
    $eng->execute([$q['engineer_id']]);
    $engRow = $eng->fetch();
    sendPushNotification($engRow['device_token'] ?? '', 'Quotation Approved ✅',
        'Customer approved quotation for job #'.$q['job_number'].'. Revisit: '.$q['revisit_date'],
        ['type'=>'quotation_approved','job_id'=>$q['job_id']]);
    logNotification($q['engineer_id'], 'engineer', 'Quotation Approved',
        'Customer approved job #'.$q['job_number'].'. Revisit on '.$q['revisit_date'], ['job_id'=>$q['job_id']]);

    jsonResponse(true, ['revisit_date' => $q['revisit_date'], 'amount' => $q['quotation_amount']], 'Quotation approved! Revisit scheduled for '.$q['revisit_date']);
}

// Reject
$rejectReason = trim($input['reason'] ?? 'Customer rejected quotation');
$db->prepare("UPDATE job_quotations SET status='rejected', admin_notes=CONCAT(COALESCE(admin_notes,''), ?), updated_at=NOW() WHERE id=?")->execute([' | Customer rejection: '.$rejectReason, $quotationId]);
$db->prepare("UPDATE jobs SET status='quotation_rejected', updated_at=NOW() WHERE id=?")->execute([$q['job_id']]);

$eng = $db->prepare("SELECT device_token FROM engineers WHERE id=?");
$eng->execute([$q['engineer_id']]);
sendPushNotification($eng->fetchColumn() ?? '', 'Quotation Rejected',
    'Customer rejected quotation for job #'.$q['job_number'].'. Reason: '.$rejectReason,
    ['type'=>'quotation_rejected','job_id'=>$q['job_id']]);
logNotification($q['engineer_id'], 'engineer', 'Quotation Rejected',
    'Customer rejected job #'.$q['job_number'].' quotation.', ['job_id'=>$q['job_id']]);

jsonResponse(true, null, 'Quotation rejected. We will contact you to discuss alternatives.');

<?php
// routes/customer/dispute.php — Raise or view a dispute for a job
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId   = (int)($input['job_id']      ?? 0);
    $reason  = trim($input['reason']       ?? '');
    $details = trim($input['details']      ?? '');

    if (!$jobId || !$reason) jsonResponse(false, null, 'job_id and reason required', 422);

    // Job must belong to this customer
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND customer_id=?");
    $stmt->execute([$jobId, $customer['id']]);
    $job = $stmt->fetch();
    if (!$job) jsonResponse(false, null, 'Job not found', 404);

    // Prevent duplicate open dispute
    $existing = $db->prepare("SELECT id FROM disputes WHERE job_id=? AND customer_id=? AND status='open'");
    $existing->execute([$jobId, $customer['id']]);
    if ($existing->fetch()) jsonResponse(false, null, 'An open dispute already exists for this job', 409);

    $db->prepare("INSERT INTO disputes (job_id, customer_id, reason, details) VALUES (?,?,?,?)")
       ->execute([$jobId, $customer['id'], $reason, $details ?: null]);

    jsonResponse(true, ['dispute_id' => $db->lastInsertId()], 'Dispute raised. Our team will review it shortly.');
}

// GET — list disputes for this customer
$stmt = $db->prepare("SELECT d.*, j.job_number FROM disputes d
    JOIN jobs j ON d.job_id = j.id
    WHERE d.customer_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$customer['id']]);
jsonResponse(true, $stmt->fetchAll());

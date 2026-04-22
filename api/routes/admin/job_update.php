<?php
// routes/admin/job_update.php — Update job status, priority, notes, amount
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Fetch existing job
$jobStmt = $db->prepare('SELECT * FROM jobs WHERE id=?');
$jobStmt->execute([$jobId]);
$job = $jobStmt->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);

$sets   = [];
$params = [];

// Updatable fields
$allowedStatuses = ['pending','assigned','accepted','on_the_way','arrived','working','completed','cancelled','awaiting_quotation','quotation_sent','quotation_approved','quotation_rejected','pickup_requested','device_picked','revisit_scheduled'];

if (isset($input['status'])) {
    $status = $input['status'];
    if (!in_array($status, $allowedStatuses)) jsonResponse(false, null, 'Invalid status', 422);
    $sets[]   = 'status=?';
    $params[] = $status;

    // Set end_time when completing
    if ($status === 'completed' && empty($job['end_time'])) {
        $sets[]   = 'end_time=NOW()';
    }
    // Free engineer if cancelling or completing and engineer was busy
    if (in_array($status, ['completed', 'cancelled']) && !empty($job['engineer_id'])) {
        $db->prepare("UPDATE engineers SET status='available' WHERE id=? AND status='busy'")
           ->execute([$job['engineer_id']]);
    }
}

if (isset($input['priority'])) {
    $priority = $input['priority'];
    if (!in_array($priority, ['low','normal','high','urgent'])) jsonResponse(false, null, 'Invalid priority', 422);
    $sets[]   = 'priority=?';
    $params[] = $priority;
}

if (isset($input['notes'])) {
    $sets[]   = 'notes=?';
    $params[] = trim($input['notes']);
}

if (isset($input['final_amount'])) {
    $sets[]   = 'final_amount=?';
    $params[] = (float)$input['final_amount'];
}

if (isset($input['address'])) {
    $sets[]   = 'address=?';
    $params[] = trim($input['address']);
}

if (empty($sets)) jsonResponse(false, null, 'Nothing to update', 422);

$sets[]   = 'updated_at=NOW()';
$params[] = $jobId;

$db->prepare('UPDATE jobs SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);

// Return updated job
$updated = $db->prepare("SELECT j.id, j.id AS job_id, j.job_number, j.status, j.priority,
    j.address, j.description, j.notes, j.created_at, j.updated_at,
    COALESCE(j.final_amount, j.amount, 0) AS amount,
    COALESCE(s.name, j.service_type) AS service,
    c.name AS customer, c.phone AS customer_phone,
    COALESCE(e.name,'Unassigned') AS engineer, e.phone AS engineer_phone
    FROM jobs j JOIN customers c ON j.customer_id=c.id
    LEFT JOIN engineers e ON j.engineer_id=e.id
    LEFT JOIN service_types s ON j.service_id=s.id
    WHERE j.id=?");
$updated->execute([$jobId]);

jsonResponse(true, $updated->fetch(), 'Job updated successfully');

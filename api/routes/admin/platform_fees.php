<?php
// routes/admin/platform_fees.php — View and mark cash platform fees as collected
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId  = (int)($input['job_id']   ?? 0);
    $action = trim($input['action']    ?? 'collect');
    $note   = trim($input['note']      ?? '');
    if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

    if ($action === 'collect') {
        $db->prepare("INSERT INTO platform_fee_collections (job_id, engineer_id, amount, status, collected_at, note)
            SELECT id, engineer_id, platform_charge, 'collected', NOW(), ?
            FROM jobs WHERE id=?
            ON DUPLICATE KEY UPDATE status='collected', collected_at=NOW(), note=?")
           ->execute([$note, $jobId, $note]);
        jsonResponse(true, null, 'Platform fee marked as collected');
    }
    jsonResponse(false, null, 'Invalid action', 422);
}

// GET — list all cash jobs with platform fee status
$from = $input['from'] ?? date('Y-m-01');
$to   = $input['to']   ?? date('Y-m-d');

$stmt = $db->prepare("
    SELECT j.id AS job_id, j.job_number, j.end_time,
        j.final_amount, j.platform_charge,
        j.payment_method,
        e.name AS engineer_name, e.phone AS engineer_phone,
        c.name AS customer_name,
        COALESCE(pfc.status,'pending') AS fee_status,
        pfc.collected_at, pfc.note
    FROM jobs j
    JOIN engineers e ON j.engineer_id = e.id
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN platform_fee_collections pfc ON pfc.job_id = j.id
    WHERE j.status='completed'
    AND j.payment_method = 'cash'
    AND j.platform_charge > 0
    AND DATE(j.end_time) BETWEEN ? AND ?
    ORDER BY j.end_time DESC
");
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

$totalFee      = array_sum(array_column($rows, 'platform_charge'));
$collectedFee  = array_sum(array_map(fn($r) => $r['fee_status']==='collected' ? $r['platform_charge'] : 0, $rows));
$pendingFee    = $totalFee - $collectedFee;

jsonResponse(true, [
    'fees'      => $rows,
    'summary'   => [
        'total'     => round($totalFee,2),
        'collected' => round($collectedFee,2),
        'pending'   => round($pendingFee,2),
    ],
    'from' => $from, 'to' => $to,
]);

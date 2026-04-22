<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $input['action']     ?? '';
    $disputeId = (int)($input['dispute_id'] ?? 0);
    $resolution= trim($input['resolution']  ?? '');
    if (!$disputeId) jsonResponse(false, null, 'dispute_id required', 422);
    if ($action === 'resolve') {
        $db->prepare("UPDATE disputes SET status='resolved', resolution=?, resolved_at=NOW() WHERE id=?")->execute([$resolution, $disputeId]);
    } elseif ($action === 'close') {
        $db->prepare("UPDATE disputes SET status='closed' WHERE id=?")->execute([$disputeId]);
    }
    jsonResponse(true, null, 'Dispute updated');
}
$status = $input['status'] ?? 'open';
$stmt = $db->prepare("SELECT d.*, j.job_number, c.name AS customer_name, c.phone AS customer_phone FROM disputes d JOIN jobs j ON d.job_id=j.id JOIN customers c ON d.customer_id=c.id WHERE d.status=? ORDER BY d.created_at DESC");
$stmt->execute([$status]);
jsonResponse(true, $stmt->fetchAll());

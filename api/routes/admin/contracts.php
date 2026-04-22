<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $input['action'] ?? 'create';
    if ($action === 'create') {
        $customerId = (int)($input['customer_id'] ?? 0);
        $title      = trim($input['title'] ?? '');
        $startDate  = $input['start_date'] ?? date('Y-m-d');
        $endDate    = $input['end_date']   ?? date('Y-m-d', strtotime('+1 year'));
        $amount     = (float)($input['amount'] ?? 0);
        $visits     = (int)($input['visits_total'] ?? 1);
        if (!$customerId || !$title) jsonResponse(false, null, 'customer_id and title required', 422);
        $contractNumber = 'CTR-' . date('ymd') . '-' . rand(1000, 9999);
        $nextDate = date('Y-m-d', strtotime('+1 month'));
        $db->prepare('INSERT INTO contracts (contract_number,customer_id,title,start_date,end_date,visits_total,amount,next_service_date) VALUES (?,?,?,?,?,?,?,?)')->execute([$contractNumber, $customerId, $title, $startDate, $endDate, $visits, $amount, $nextDate]);
        jsonResponse(true, ['contract_number' => $contractNumber], 'Contract created');
    }
    jsonResponse(false, null, 'Invalid action', 422);
}
$status = $input['status'] ?? 'active';
$stmt = $db->prepare('SELECT ct.*, c.name AS customer_name, c.phone AS customer_phone FROM contracts ct JOIN customers c ON ct.customer_id=c.id WHERE ct.status=? ORDER BY ct.created_at DESC');
$stmt->execute([$status]);
jsonResponse(true, $stmt->fetchAll());

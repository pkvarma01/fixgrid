<?php
// routes/admin/export.php — Export jobs/revenue as CSV or Excel
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$from   = trim($input['from']   ?? date('Y-m-01'));
$to     = trim($input['to']     ?? date('Y-m-d'));
$type   = trim($input['type']   ?? 'jobs');  // jobs | engineers | customers
$format = trim($input['format'] ?? 'csv');   // csv | json

$rows = [];
if ($type === 'jobs') {
    $stmt = $db->prepare("
        SELECT j.job_number, j.created_at, j.scheduled_date,
            COALESCE(s.name, j.service_type) AS service,
            c.name AS customer, c.phone AS customer_phone,
            e.name AS engineer, e.phone AS engineer_phone,
            j.status, j.priority, j.address,
            j.amount, j.visit_charge, j.discount_amount, j.final_amount,
            j.platform_charge, j.payment_method, j.promo_code, j.notes
        FROM jobs j
        LEFT JOIN service_types s ON j.service_id = s.id
        LEFT JOIN customers c ON j.customer_id = c.id
        LEFT JOIN engineers e ON j.engineer_id = e.id
        WHERE DATE(j.created_at) BETWEEN ? AND ?
        ORDER BY j.created_at DESC");
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
} elseif ($type === 'engineers') {
    $stmt = $db->prepare("
        SELECT e.name, e.email, e.phone, e.city, e.is_active, e.status, e.avg_rating,
            COUNT(DISTINCT j.id) AS total_jobs,
            SUM(CASE WHEN j.status='completed' THEN 1 ELSE 0 END) AS completed_jobs,
            COALESCE(SUM(CASE WHEN j.status='completed' THEN j.final_amount ELSE 0 END),0) AS total_revenue,
            COALESCE(ew.balance, 0) AS wallet_balance
        FROM engineers e
        LEFT JOIN jobs j ON j.engineer_id = e.id AND DATE(j.created_at) BETWEEN ? AND ?
        LEFT JOIN engineer_wallet ew ON ew.engineer_id = e.id
        GROUP BY e.id ORDER BY total_revenue DESC");
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
} elseif ($type === 'customers') {
    $stmt = $db->prepare("
        SELECT c.name, c.email, c.phone, c.address, c.created_at,
            COUNT(DISTINCT j.id) AS total_jobs,
            COALESCE(SUM(CASE WHEN j.status='completed' THEN j.final_amount ELSE 0 END),0) AS total_spent,
            COALESCE(cw.balance,0) AS wallet_balance
        FROM customers c
        LEFT JOIN jobs j ON j.customer_id = c.id AND DATE(j.created_at) BETWEEN ? AND ?
        LEFT JOIN customer_wallet cw ON cw.customer_id = c.id
        GROUP BY c.id ORDER BY total_spent DESC");
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
}

if ($format === 'json') {
    jsonResponse(true, ['rows' => $rows, 'count' => count($rows), 'from' => $from, 'to' => $to, 'type' => $type]);
}

// CSV output
$filename = "fixgrid_{$type}_{$from}_to_{$to}.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

if (!empty($rows)) {
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
} else {
    fputcsv($out, ['No data found for the selected date range']);
}
fclose($out);
exit;

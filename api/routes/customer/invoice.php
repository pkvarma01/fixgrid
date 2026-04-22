<?php
// routes/customer/invoice.php — Full invoice/quotation detail for customer
// Returns all job details, parts used, quotation info for display and download
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Verify job belongs to customer
$job = $db->prepare("
    SELECT j.*,
           COALESCE(s.name, j.service_type) AS service_name,
           s.icon AS service_icon,
           c.name AS customer_name, c.phone AS customer_phone,
           e.name AS engineer_name, e.phone AS engineer_phone,
           COALESCE(j.final_amount, j.amount, 0) AS total_amount
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN engineers e ON j.engineer_id = e.id
    LEFT JOIN service_types s ON j.service_id = s.id
    WHERE j.id = ? AND j.customer_id = ?
");
$job->execute([$jobId, $customer['id']]);
$jobData = $job->fetch();
if (!$jobData) jsonResponse(false, null, 'Job not found', 404);

// Parts used
$parts = $db->prepare("
    SELECT jpu.qty, jpu.unit_price, jpu.qty * jpu.unit_price AS subtotal, sp.name, sp.unit
    FROM job_parts_used jpu
    JOIN spare_parts sp ON jpu.part_id = sp.id
    WHERE jpu.job_id = ?
");
$parts->execute([$jobId]);
$partsData = $parts->fetchAll();
$partsTotal = array_sum(array_column($partsData, 'subtotal'));

// Quotation (if any)
$quot = $db->prepare("
    SELECT q.*, ts.label AS slot_label
    FROM job_quotations q
    LEFT JOIN time_slots ts ON q.revisit_slot_id = ts.id
    WHERE q.job_id = ? ORDER BY q.created_at DESC LIMIT 1
");
$quot->execute([$jobId]);
$quotData = $quot->fetch();

// Invoice record
$inv = $db->prepare("SELECT * FROM invoices WHERE job_id=? LIMIT 1");
$inv->execute([$jobId]);
$invoice = $inv->fetch();

// Company settings
$settings = $db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('company_name','company_phone','company_email','currency_symbol')")->fetchAll(PDO::FETCH_KEY_PAIR);

jsonResponse(true, [
    'job'            => $jobData,
    'parts'          => $partsData,
    'parts_total'    => round($partsTotal, 2),
    'quotation'      => $quotData,
    'invoice'        => $invoice,
    'company'        => $settings,
    'breakdown'      => [
        'service_charge'       => (float)($jobData['amount'] ?? 0),
        'visit_charge'         => (float)($jobData['visit_charge'] ?? 0),
        'emergency_fee'        => (float)($jobData['emergency_fee'] ?? 0),
        'parts_cost'           => $quotData ? (float)($quotData['parts_cost'] ?? 0) : 0,
        'installation_charge'  => $quotData ? (float)($quotData['installation_charge'] ?? 0) : 0,
        'first_visit_deducted' => $quotData ? (float)($quotData['first_visit_charge'] ?? 0) : 0,
        'parts_total'          => round($partsTotal, 2),
        'discount'             => (float)($jobData['discount_amount'] ?? 0),
        'total'                => (float)($jobData['total_amount'] ?? 0),
    ],
]);

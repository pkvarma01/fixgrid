<?php
// routes/payment/invoice.php — Get invoice (with GST support) for a job
require_once dirname(__DIR__, 2) . '/config.php';
// Allow customer, admin, or engineer to fetch their invoice
$caller = null;
$hdrs = getallheaders();
$tok = str_replace('Bearer ', '', $hdrs['Authorization'] ?? $hdrs['authorization'] ?? '');
if ($tok) {
    $_db = getDB();
    $_ts = $_db->prepare('SELECT * FROM auth_tokens WHERE token=? AND expires_at > NOW()');
    $_ts->execute([$tok]);
    $_tr = $_ts->fetch();
    if ($_tr) {
        $_tbl = ['customer'=>'customers','engineer'=>'engineers','admin'=>'admins'][$_tr['user_type']] ?? null;
        if ($_tbl) {
            $_us = $_db->prepare("SELECT * FROM {$_tbl} WHERE id=? AND is_active=1");
            $_us->execute([$_tr['user_id']]);
            $caller = $_us->fetch();
        }
    }
}
if (!$caller) jsonResponse(false, null, 'Unauthorized', 401);
$db = getDB();

$jobId     = (int)($input['job_id']     ?? 0);
$invoiceId = (int)($input['invoice_id'] ?? 0);
if (!$jobId && !$invoiceId) jsonResponse(false, null, 'job_id or invoice_id required', 422);

$sql = "SELECT i.*, j.job_number, j.description, j.scheduled_date, j.slot_id,
    COALESCE(s.name, j.service_type) AS service_name,
    c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email,
    c.address AS customer_address,
    e.name AS engineer_name, e.phone AS engineer_phone,
    j.amount AS base_amount, j.visit_charge, j.discount_amount, j.final_amount,
    j.platform_charge, j.promo_code, j.payment_method AS job_payment_method,
    j.is_emergency, j.emergency_fee
    FROM invoices i
    JOIN jobs j ON i.job_id = j.id
    LEFT JOIN service_types s ON j.service_id = s.id
    JOIN customers c ON i.customer_id = c.id
    LEFT JOIN engineers e ON j.engineer_id = e.id
    WHERE " . ($invoiceId ? "i.id=?" : "i.job_id=?");

$stmt = $db->prepare($sql);
$stmt->execute([$invoiceId ?: $jobId]);
$invoice = $stmt->fetch();
if (!$invoice) jsonResponse(false, null, 'Invoice not found', 404);

// Fetch parts used
$parts = $db->prepare("SELECT jp.*, sp.name AS part_name, sp.sell_price AS unit_price, NULL AS hsn_code
    FROM job_parts_used jp JOIN spare_parts sp ON jp.part_id = sp.id
    WHERE jp.job_id = ?");
$parts->execute([$invoice['job_id']]);
$partsUsed = $parts->fetchAll();

// GST settings
$companyName  = getSettingValue('company_name', 'Hridya Tech');
$companyGstin = getSettingValue('company_gstin', '');
$companyPan   = getSettingValue('company_pan', '');
$gstRate      = (float)getSettingValue('gst_rate', 0); // e.g. 18 for 18%
$currSymbol   = getSettingValue('currency_symbol', '₹');

// Calculate GST amounts if rate is configured
$subtotal = (float)($invoice['final_amount'] ?? $invoice['total'] ?? 0);
$gstAmount  = $gstRate > 0 ? round($subtotal * $gstRate / 100, 2) : 0;
$cgst       = round($gstAmount / 2, 2);
$sgst       = round($gstAmount / 2, 2);
$grandTotal = $subtotal + $gstAmount;

jsonResponse(true, array_merge($invoice, [
    'parts_used'      => $partsUsed,
    'company_name'    => $companyName,
    'company_gstin'   => $companyGstin,
    'company_pan'     => $companyPan,
    'gst_rate'        => $gstRate,
    'gst_amount'      => $gstAmount,
    'cgst'            => $cgst,
    'sgst'            => $sgst,
    'grand_total'     => $grandTotal,
    'currency_symbol' => $currSymbol,
]));

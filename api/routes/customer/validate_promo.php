<?php
// routes/customer/validate_promo.php — Validate a promo code before job creation
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

$code      = strtoupper(trim($input['code']       ?? ''));
$orderAmt  = (float)($input['order_amount'] ?? 0);

if (!$code) jsonResponse(false, null, 'code required', 422);

$stmt = $db->prepare("SELECT * FROM promo_codes WHERE code=? AND is_active=1");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) jsonResponse(false, null, 'Invalid or inactive promo code', 404);

// Check expiry
if ($promo['valid_till'] && strtotime($promo['valid_till']) < time()) {
    jsonResponse(false, null, 'Promo code has expired', 422);
}

// Check usage limit
if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
    jsonResponse(false, null, 'Promo code usage limit reached', 422);
}

// Check minimum order
if ($promo['min_order'] && $orderAmt < $promo['min_order']) {
    jsonResponse(false, null, 'Minimum order amount of ₹' . $promo['min_order'] . ' required', 422);
}

// Calculate discount
$discount = 0;
if ($promo['type'] === 'percent') {
    $discount = round($orderAmt * $promo['value'] / 100, 2);
    if ($promo['max_discount']) $discount = min($discount, $promo['max_discount']);
} else {
    $discount = min($promo['value'], $orderAmt);
}

jsonResponse(true, [
    'code'     => $code,
    'type'     => $promo['type'],
    'value'    => $promo['value'],
    'discount' => $discount,
    'final_amount' => max(0, $orderAmt - $discount),
], 'Promo code applied!');

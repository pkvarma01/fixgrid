<?php
// routes/admin/promo.php — Promo code management
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // FIX: corrected column names to match DB schema (discount_type, discount_value, min_order_value, valid_until)
    // Also handle usage_limit/used_count exhaustion check (both columns ARE in the DB)
    $promos = $db->query("
        SELECT *,
        CASE
            WHEN is_active = 0 THEN 'inactive'
            WHEN valid_until IS NOT NULL AND valid_until < CURDATE() THEN 'expired'
            WHEN usage_limit IS NOT NULL AND used_count >= usage_limit THEN 'exhausted'
            ELSE 'active'
        END AS computed_status
        FROM promo_codes ORDER BY created_at DESC
    ")->fetchAll();
    jsonResponse(true, $promos);
}

$action = $input['action'] ?? 'create';

if ($action === 'create') {
    $code       = strtoupper(trim($input['code']   ?? ''));
    // FIX: map frontend 'type'/'value'/'min_order'/'valid_till' to actual DB column names
    $type       = in_array($input['type'] ?? '', ['flat','percent']) ? $input['type'] : 'flat';
    $value      = (float)($input['value']      ?? 0);
    $minOrder   = (float)($input['min_order']  ?? 0);
    $maxDisc    = isset($input['max_discount']) && $input['max_discount'] !== '' ? (float)$input['max_discount'] : null;
    $usageLimit = isset($input['usage_limit'])  && $input['usage_limit']  !== '' ? (int)$input['usage_limit']   : null;
    $validUntil = isset($input['valid_till'])   && $input['valid_till']   !== '' ? $input['valid_till']         : null;

    if (!$code || !$value) jsonResponse(false, null, 'code and value required', 422);
    $exists = $db->prepare("SELECT COUNT(*) FROM promo_codes WHERE code=?");
    $exists->execute([$code]);
    if ($exists->fetchColumn()) jsonResponse(false, null, 'Code already exists', 409);

    $db->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, min_order_value, max_discount, usage_limit, valid_until) VALUES (?,?,?,?,?,?,?)")
       ->execute([$code, $type, $value, $minOrder, $maxDisc, $usageLimit, $validUntil]);
    jsonResponse(true, ['id' => $db->lastInsertId()], "Promo code $code created");
}

if ($action === 'toggle') {
    $id = (int)($input['id'] ?? 0);
    $db->prepare("UPDATE promo_codes SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    jsonResponse(true, null, 'Promo status toggled');
}

jsonResponse(false, null, 'Invalid action', 400);

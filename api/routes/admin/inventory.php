<?php
// routes/admin/inventory.php — Spare parts management
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$action = $input['action'] ?? 'list';

if ($method === 'GET' || $action === 'list') {
    // FIX: DB spare_parts uses min_stock (not low_stock_alert), cost_price/sell_price (not unit_price), no category column
    $parts = $db->query("
        SELECT sp.*,
               sp.cost_price AS unit_price,
               COALESCE(SUM(ei.qty), 0) AS total_in_field,
               CASE WHEN sp.stock_qty <= sp.min_stock THEN 1 ELSE 0 END AS is_low_stock
        FROM spare_parts sp
        LEFT JOIN engineer_inventory ei ON sp.id = ei.part_id
        WHERE sp.is_active = 1
        GROUP BY sp.id
        ORDER BY is_low_stock DESC, sp.name ASC
    ")->fetchAll();

    $lowStockCount = array_sum(array_column($parts, 'is_low_stock'));
    jsonResponse(true, ['parts' => $parts, 'low_stock_alert_count' => $lowStockCount]);
}

if ($method === 'POST') {
    if ($action === 'create') {
        $name      = trim($input['name']        ?? '');
        $sku       = trim($input['sku']         ?? '');
        // Accept unit_price from frontend, map to cost_price + sell_price in DB
        $price     = (float)($input['unit_price'] ?? $input['cost_price'] ?? 0);
        $qty       = (int)($input['stock_qty']  ?? 0);
        // Accept low_stock_alert from frontend, map to min_stock in DB
        $minStock  = (int)($input['low_stock_alert'] ?? $input['min_stock'] ?? 5);
        if (!$name) jsonResponse(false, null, 'name required', 422);
        // FIX: insert using correct DB columns (cost_price, sell_price, min_stock; no category)
        $db->prepare("INSERT INTO spare_parts (name, sku, cost_price, sell_price, stock_qty, min_stock) VALUES (?,?,?,?,?,?)")
           ->execute([$name, $sku ?: null, $price, $price, $qty, $minStock]);
        jsonResponse(true, ['id' => $db->lastInsertId()], 'Part created');
    }

    if ($action === 'restock') {
        $partId = (int)($input['part_id'] ?? 0);
        $qty    = (int)($input['qty']     ?? 0);
        if (!$partId || $qty <= 0) jsonResponse(false, null, 'part_id and qty required', 422);
        $db->prepare("UPDATE spare_parts SET stock_qty = stock_qty + ? WHERE id=?")->execute([$qty, $partId]);
        jsonResponse(true, null, 'Stock updated');
    }

    if ($action === 'assign_to_engineer') {
        $partId     = (int)($input['part_id']     ?? 0);
        $engineerId = (int)($input['engineer_id'] ?? 0);
        $qty        = (int)($input['qty']         ?? 0);
        if (!$partId || !$engineerId || $qty <= 0) jsonResponse(false, null, 'Missing fields', 422);
        $stock = $db->query("SELECT stock_qty FROM spare_parts WHERE id = $partId")->fetchColumn();
        if ($stock < $qty) jsonResponse(false, null, 'Insufficient stock', 422);
        $db->prepare("INSERT INTO engineer_inventory (engineer_id, part_id, qty) VALUES (?,?,?) ON DUPLICATE KEY UPDATE qty = qty + ?")
           ->execute([$engineerId, $partId, $qty, $qty]);
        $db->prepare("UPDATE spare_parts SET stock_qty = stock_qty - ? WHERE id=?")->execute([$qty, $partId]);
        jsonResponse(true, null, "Assigned $qty units to engineer");
    }
}

jsonResponse(false, null, 'Invalid action', 400);

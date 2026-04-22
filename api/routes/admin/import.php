<?php
// routes/admin/import.php — Bulk CSV import for services, skills, inventory
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$type = $input['type'] ?? '';
$rows = $input['rows'] ?? [];

if (!$type || !is_array($rows) || empty($rows)) {
    jsonResponse(false, null, 'type and rows are required', 422);
}

$imported = 0;
$skipped  = 0;
$errors   = [];

// ── SKILLS ─────────────────────────────────────────────────────────────────
if ($type === 'skills') {
    $check = $db->prepare("SELECT id FROM skills WHERE name=?");
    $insert = $db->prepare("INSERT IGNORE INTO skills (name, is_active) VALUES (?,1)");
    foreach ($rows as $i => $row) {
        $name = trim($row[0] ?? '');
        if (!$name) { $skipped++; continue; }
        $check->execute([$name]);
        if ($check->fetch()) { $skipped++; continue; }
        try {
            $insert->execute([$name]);
            $imported++;
        } catch (Exception $e) {
            $errors[] = "Row " . ($i+1) . ": " . $e->getMessage();
        }
    }
}

// ── SERVICES & SUB-SERVICES ────────────────────────────────────────────────
elseif ($type === 'services') {
    // CSV columns: name, base_price, visit_charge, duration_min, icon, is_active, parent_name
    $getParent = $db->prepare("SELECT id FROM service_types WHERE name=? AND parent_id IS NULL");
    $checkSvc  = $db->prepare("SELECT id FROM service_types WHERE name=? AND (parent_id=? OR (parent_id IS NULL AND ?=0))");
    $insertSvc = $db->prepare("INSERT INTO service_types
        (name, base_price, visit_charge, duration_min, icon, is_active, parent_id, sort_order)
        VALUES (?,?,?,?,?,?,?,?)");
    $sortStmt  = $db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM service_types WHERE parent_id IS NULL");
    $sortSubStmt = $db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM service_types WHERE parent_id=?");

    foreach ($rows as $i => $row) {
        $name       = trim($row[0] ?? '');
        $basePrice  = floatval($row[1] ?? 0);
        $visitCharge = floatval($row[2] ?? 0);
        $duration   = intval($row[3] ?? 60);
        $icon       = trim($row[4] ?? '🔧');
        $isActive   = intval($row[5] ?? 1);
        $parentName = trim($row[6] ?? '');

        if (!$name) { $skipped++; continue; }

        $parentId = null;
        if ($parentName) {
            $getParent->execute([$parentName]);
            $parent = $getParent->fetch();
            if (!$parent) {
                // Auto-create parent
                $sortStmt->execute();
                $sort = $sortStmt->fetchColumn();
                $insertSvc->execute([$parentName, 0, 0, 60, '🔧', 1, null, $sort]);
                $parentId = $db->lastInsertId();
            } else {
                $parentId = $parent['id'];
            }
        }

        // Check duplicate
        $checkSvc->execute([$name, $parentId ?? 0, $parentId ? 1 : 0]);
        if ($checkSvc->fetch()) { $skipped++; continue; }

        try {
            if ($parentId) {
                $sortSubStmt->execute([$parentId]);
                $sort = $sortSubStmt->fetchColumn();
            } else {
                $sortStmt->execute();
                $sort = $sortStmt->fetchColumn();
            }
            $insertSvc->execute([$name, $basePrice, $visitCharge, $duration, $icon, $isActive, $parentId, $sort]);
            $imported++;
        } catch (Exception $e) {
            $errors[] = "Row " . ($i+1) . ": " . $e->getMessage();
        }
    }
}

// ── INVENTORY / PARTS ──────────────────────────────────────────────────────
elseif ($type === 'inventory') {
    // CSV columns: name, sku, unit_price, stock_qty, min_stock_alert, sell_price
    $check  = $db->prepare("SELECT id FROM spare_parts WHERE sku=? AND sku != ''");
    $checkN = $db->prepare("SELECT id FROM spare_parts WHERE name=?");
    $insert = $db->prepare("INSERT INTO spare_parts
        (name, sku, cost_price, sell_price, stock_qty, min_stock)
        VALUES (?,?,?,?,?,?)");
    $update = $db->prepare("UPDATE spare_parts
        SET stock_qty=stock_qty+?, cost_price=? WHERE id=?");

    foreach ($rows as $i => $row) {
        $name      = trim($row[0] ?? '');
        $sku       = trim($row[1] ?? '');
        $cost      = floatval($row[2] ?? 0);
        $qty       = intval($row[3] ?? 0);
        $minAlert  = intval($row[4] ?? 5);
        $sellPrice = floatval($row[5] ?? 0);

        if (!$name) { $skipped++; continue; }
        if (!$sellPrice) $sellPrice = $cost * 1.2; // 20% markup default

        try {
            // If SKU exists → restock
            if ($sku) {
                $check->execute([$sku]);
                $existing = $check->fetch();
                if ($existing) {
                    $update->execute([$qty, $cost, $existing['id']]);
                    $imported++;
                    continue;
                }
            }
            // If name exists → restock
            $checkN->execute([$name]);
            $existing = $checkN->fetch();
            if ($existing) {
                $update->execute([$qty, $cost, $existing['id']]);
                $imported++;
                continue;
            }
            // New part
            $insert->execute([$name, $sku, $cost, $sellPrice, $qty, $minAlert]);
            $imported++;
        } catch (Exception $e) {
            $errors[] = "Row " . ($i+1) . ": " . $e->getMessage();
        }
    }
}

else {
    jsonResponse(false, null, "Unknown type: $type", 422);
}

jsonResponse(true, [
    'imported' => $imported,
    'skipped'  => $skipped,
    'errors'   => $errors,
], "$imported imported, $skipped skipped" . (count($errors) ? ', ' . count($errors) . ' errors' : ''));

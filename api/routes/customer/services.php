<?php
// routes/customer/services.php - Returns service tree with sub-services for booking
require_once dirname(__DIR__, 2) . '/config.php';
$db = getDB();

// Get parent categories
$parents = $db->query("SELECT id, name, icon, base_price, visit_charge FROM service_types WHERE parent_id IS NULL AND is_active=1 ORDER BY sort_order, name")->fetchAll();

// Get all active sub-services
$children = $db->query("SELECT id, parent_id, name, icon, base_price, visit_charge FROM service_types WHERE parent_id IS NOT NULL AND is_active=1 ORDER BY parent_id, sort_order, name")->fetchAll();

// Group by parent
$childMap = [];
foreach ($children as $c) {
    $childMap[$c['parent_id']][] = $c;
}

foreach ($parents as &$p) {
    $p['sub_services'] = $childMap[$p['id']] ?? [];
}

jsonResponse(true, $parents);

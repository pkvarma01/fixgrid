<?php
// routes/admin/services.php — Service categories and sub-services
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'toggle') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'id required', 422);
        // Toggle parent
        $db->prepare('UPDATE service_types SET is_active = NOT is_active WHERE id=?')->execute([$id]);
        // Sync children to same state — get new state first to avoid self-reference error
        $newState = $db->prepare('SELECT is_active FROM service_types WHERE id=?');
        $newState->execute([$id]);
        $state = $newState->fetchColumn();
        $db->prepare('UPDATE service_types SET is_active=? WHERE parent_id=?')->execute([$state, $id]);
        jsonResponse(true, null, 'Service updated');
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'id required', 422);
        $inUse = $db->prepare("SELECT COUNT(*) FROM jobs WHERE service_id=? AND status NOT IN ('completed','cancelled')");
        $inUse->execute([$id]);
        if ($inUse->fetchColumn() > 0) jsonResponse(false, null, 'Cannot delete — service has active jobs', 409);
        // Delete sub-services first, then parent
        $db->prepare('DELETE FROM service_types WHERE parent_id=?')->execute([$id]);
        $db->prepare('DELETE FROM service_types WHERE id=?')->execute([$id]);
        jsonResponse(true, null, 'Service deleted');
    }

    // Create or update
    $id               = (int)($input['id']                  ?? 0) ?: null;
    $parentId         = isset($input['parent_id']) && $input['parent_id'] !== '' ? (int)$input['parent_id'] : null;
    $name             = trim($input['name']                  ?? '');
    $icon             = trim($input['icon']                  ?? '🔧');
    $skillId          = isset($input['required_skill_id']) && $input['required_skill_id'] ? (int)$input['required_skill_id'] : null;
    $basePrice        = (float)($input['base_price']         ?? 0);
    $visitCharge      = (float)($input['visit_charge']       ?? 0);
    $perKmRate        = (float)($input['per_km_rate']         ?? 0);
    $platformPct      = (float)($input['platform_charge_pct'] ?? 20);
    $sortOrder        = (int)($input['sort_order']           ?? 0);

    if (!$name) jsonResponse(false, null, 'Service name required', 422);

    if ($id) {
        $db->prepare('UPDATE service_types SET parent_id=?, name=?, icon=?, required_skill_id=?, base_price=?, visit_charge=?, per_km_rate=?, platform_charge_pct=?, sort_order=? WHERE id=?')
           ->execute([$parentId, $name, $icon ?: '🔧', $skillId, $basePrice, $visitCharge, $perKmRate, $platformPct, $sortOrder, $id]);
        jsonResponse(true, null, 'Service updated');
    } else {
        $exists = $db->prepare('SELECT id FROM service_types WHERE name=? AND COALESCE(parent_id,0)=?');
        $exists->execute([$name, $parentId ?? 0]);
        if ($exists->fetch()) jsonResponse(false, null, 'Service already exists under this category', 422);
        $db->prepare('INSERT INTO service_types (parent_id, name, icon, required_skill_id, base_price, visit_charge, per_km_rate, platform_charge_pct, sort_order) VALUES (?,?,?,?,?,?,?,?,?)')
           ->execute([$parentId, $name, $icon ?: '🔧', $skillId, $basePrice, $visitCharge, $perKmRate, $platformPct, $sortOrder]);
        jsonResponse(true, ['id' => $db->lastInsertId()], 'Service created');
    }
}

// GET — return hierarchical tree
// Parent services (parent_id IS NULL) with their children
$parents = $db->query("SELECT s.*, sk.name AS skill_name FROM service_types s LEFT JOIN skills sk ON s.required_skill_id=sk.id WHERE s.parent_id IS NULL ORDER BY s.sort_order, s.name")->fetchAll();
$children = $db->query("SELECT s.*, sk.name AS skill_name FROM service_types s LEFT JOIN skills sk ON s.required_skill_id=sk.id WHERE s.parent_id IS NOT NULL ORDER BY s.parent_id, s.sort_order, s.name")->fetchAll();

// Group children by parent_id
$childMap = [];
foreach ($children as $c) {
    $childMap[$c['parent_id']][] = $c;
}

// Attach children to parents
foreach ($parents as &$p) {
    $p['sub_services'] = $childMap[$p['id']] ?? [];
}

jsonResponse(true, $parents);

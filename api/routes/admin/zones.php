<?php
// api/routes/admin/zones.php
// Zone CRUD — list, create, update, delete, toggle active
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── LIST ──────────────────────────────────────────────────────────────────
if ($method === 'GET' && empty($input['action'])) {
    $zones = $db->query("
        SELECT z.*,
            COUNT(DISTINCT ze.engineer_id) AS engineer_count
        FROM zones z
        LEFT JOIN zone_engineers ze ON ze.zone_id = z.id
        GROUP BY z.id
        ORDER BY z.city ASC, z.name ASC
    ")->fetchAll();
    jsonResponse(true, $zones, 'Zones loaded');
}

// ── GET SINGLE (with engineers) ───────────────────────────────────────────
if ($method === 'GET' && ($input['action'] ?? '') === 'detail') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(false, null, 'id required', 422);

    $stmt = $db->prepare("SELECT * FROM zones WHERE id=?");
    $stmt->execute([$id]);
    $zone = $stmt->fetch();
    if (!$zone) jsonResponse(false, null, 'Zone not found', 404);

    // Engineers assigned to this zone
    $stmt2 = $db->prepare("
        SELECT e.id, e.name, e.phone, e.email, e.status, e.profile_photo,
               e.city, ze.is_available, ze.created_at AS assigned_at
        FROM zone_engineers ze
        JOIN engineers e ON e.id = ze.engineer_id
        WHERE ze.zone_id = ?
        ORDER BY e.name ASC
    ");
    $stmt2->execute([$id]);
    $zone['engineers'] = $stmt2->fetchAll();
    jsonResponse(true, $zone, 'Zone detail loaded');
}

// ── CREATE ────────────────────────────────────────────────────────────────
if ($method === 'POST' && ($input['action'] ?? '') === 'create') {
    $name        = trim($input['name']        ?? '');
    $city        = trim($input['city']        ?? '');
    $state       = trim($input['state']       ?? '');
    $description = trim($input['description'] ?? '');
    $lat         = isset($input['latitude'])  ? (float)$input['latitude']  : null;
    $lng         = isset($input['longitude']) ? (float)$input['longitude'] : null;
    $radius_km   = isset($input['radius_km']) ? (float)$input['radius_km'] : 10.0;

    if (!$name || !$city) jsonResponse(false, null, 'name and city are required', 422);

    // Check duplicate
    $chk = $db->prepare("SELECT id FROM zones WHERE name=? AND city=?");
    $chk->execute([$name, $city]);
    if ($chk->fetch()) jsonResponse(false, null, 'A zone with this name already exists in ' . $city, 409);

    $stmt = $db->prepare("
        INSERT INTO zones (name, city, state, description, latitude, longitude, radius_km, is_active)
        VALUES (?,?,?,?,?,?,?,1)
    ");
    $stmt->execute([$name, $city, $state, $description, $lat, $lng, $radius_km]);
    $id = $db->lastInsertId();
    jsonResponse(true, ['id' => $id], 'Zone created successfully');
}

// ── UPDATE ────────────────────────────────────────────────────────────────
if ($method === 'POST' && ($input['action'] ?? '') === 'update') {
    $id          = (int)($input['id']          ?? 0);
    $name        = trim($input['name']         ?? '');
    $city        = trim($input['city']         ?? '');
    $state       = trim($input['state']        ?? '');
    $description = trim($input['description']  ?? '');
    $lat         = isset($input['latitude'])   ? (float)$input['latitude']  : null;
    $lng         = isset($input['longitude'])  ? (float)$input['longitude'] : null;
    $radius_km   = isset($input['radius_km'])  ? (float)$input['radius_km'] : 10.0;

    if (!$id || !$name || !$city) jsonResponse(false, null, 'id, name and city required', 422);

    $stmt = $db->prepare("
        UPDATE zones SET name=?, city=?, state=?, description=?,
            latitude=?, longitude=?, radius_km=?, updated_at=NOW()
        WHERE id=?
    ");
    $stmt->execute([$name, $city, $state, $description, $lat, $lng, $radius_km, $id]);
    jsonResponse(true, null, 'Zone updated successfully');
}

// ── TOGGLE ACTIVE ─────────────────────────────────────────────────────────
if ($method === 'POST' && ($input['action'] ?? '') === 'toggle') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(false, null, 'id required', 422);
    $db->prepare("UPDATE zones SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    jsonResponse(true, null, 'Zone status updated');
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($method === 'POST' && ($input['action'] ?? '') === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(false, null, 'id required', 422);
    $db->prepare("DELETE FROM zone_engineers WHERE zone_id=?")->execute([$id]);
    $db->prepare("DELETE FROM zones WHERE id=?")->execute([$id]);
    jsonResponse(true, null, 'Zone deleted');
}

jsonResponse(false, null, 'Invalid request', 400);

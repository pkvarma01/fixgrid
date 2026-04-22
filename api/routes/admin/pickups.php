<?php
// routes/admin/pickups.php -- List and manage device pickup requests
// Flow: requested -> scheduled -> picked -> repaired -> delivered
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($method === 'POST') {
    // Update pickup status + optional fields
    $pickupId     = (int)($input['pickup_id']    ?? 0);
    $newStatus    = trim($input['status']         ?? '');
    $pickupDate   = $input['pickup_date']          ?? null;
    $deliveryDate = $input['delivery_date']        ?? null;
    $repairNotes  = $input['repair_notes']         ?? null;
    $repairCharge = isset($input['repair_charge']) ? (float)$input['repair_charge'] : null;

    $allowed = ['requested', 'scheduled', 'picked', 'repaired', 'delivered'];

    if (!$pickupId) jsonResponse(false, null, 'pickup_id required', 422);
    if (!in_array($newStatus, $allowed))
        jsonResponse(false, null, 'status must be one of: ' . implode(', ', $allowed), 422);

    $sets   = ['status=?', 'updated_at=NOW()'];
    $params = [$newStatus];

    if ($pickupDate)            { $sets[] = 'pickup_date=?';   $params[] = $pickupDate; }
    if ($deliveryDate)          { $sets[] = 'delivery_date=?'; $params[] = $deliveryDate; }
    if ($repairNotes !== null)  { $sets[] = 'repair_notes=?';  $params[] = $repairNotes; }
    if ($repairCharge !== null) { $sets[] = 'repair_charge=?'; $params[] = $repairCharge; }

    $params[] = $pickupId;
    $stmt = $db->prepare('UPDATE device_pickups SET ' . implode(', ', $sets) . ' WHERE id=?');
    $stmt->execute($params);
    if ($stmt->rowCount() === 0) jsonResponse(false, null, 'Pickup not found', 404);

    jsonResponse(true, ['pickup_id' => $pickupId, 'status' => $newStatus], 'Pickup updated');
}

// GET -- list pickups by status
$status = $input['status'] ?? 'requested';
$stmt = $db->prepare("
    SELECT dp.*, j.job_number, j.address AS job_address,
           c.name AS customer_name, c.phone AS customer_phone,
           e.name AS engineer_name, e.phone AS engineer_phone
    FROM device_pickups dp
    JOIN jobs j ON dp.job_id = j.id
    JOIN customers c ON j.customer_id = c.id
    JOIN engineers e ON dp.engineer_id = e.id
    WHERE dp.status = ?
    ORDER BY dp.created_at DESC
");
$stmt->execute([$status]);
jsonResponse(true, $stmt->fetchAll());

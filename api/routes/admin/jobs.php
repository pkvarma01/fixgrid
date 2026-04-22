<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
$status = $input['status'] ?? null;
$search = trim($input['search'] ?? '');
$id     = isset($input['id']) ? (int)$input['id'] : null;
$sql = "SELECT j.id, j.id AS job_id, j.job_number, j.status, j.priority, j.address, j.created_at, j.updated_at,
    COALESCE(s.name, j.service_type) AS service, c.name AS customer, c.phone AS customer_phone,
    COALESCE(e.name,'Unassigned') AS engineer, e.phone AS engineer_phone,
    j.latitude, j.longitude, j.description, j.notes,
    COALESCE(j.final_amount, j.amount, 0) AS amount,
    j.scheduled_date, ts.label AS slot_label, j.is_emergency,
    j.payment_status, j.payment_method
    FROM jobs j JOIN customers c ON j.customer_id=c.id
    LEFT JOIN engineers e ON j.engineer_id=e.id
    LEFT JOIN service_types s ON j.service_id=s.id
    LEFT JOIN time_slots ts ON j.slot_id=ts.id
    WHERE 1=1";
$params = [];
if ($id)     { $sql .= ' AND j.id=?';     $params[] = $id; }
if ($status) { $sql .= ' AND j.status=?'; $params[] = $status; }
if ($search) { $sql .= ' AND (j.job_number LIKE ? OR c.name LIKE ? OR j.service_type LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= ' ORDER BY j.created_at DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
jsonResponse(true, $stmt->fetchAll());

<?php
// routes/admin/engineer_create.php
// FIX: Column 'photo' renamed to 'profile_photo' to match the database schema.
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$name  = trim($input['name']         ?? '');
$phone = trim($input['phone']        ?? '');
$email = trim($input['email']        ?? '');
$pass  = trim($input['password']     ?? '');
$area  = trim($input['service_area'] ?? '');

if (!$name || !$phone || !$email || !$pass) {
    jsonResponse(false, null, 'name, phone, email, password required', 422);
}

$check = $db->prepare('SELECT id FROM engineers WHERE phone=? OR email=?');
$check->execute([$phone, $email]);
if ($check->fetch()) jsonResponse(false, null, 'Phone or email already exists', 409);

// FIX: column is profile_photo
$photo = null;
if (!empty($_FILES['photo']['tmp_name'])) {
    $photo = uploadFile($_FILES['photo'], 'engineers');
}

$db->prepare('INSERT INTO engineers (name,phone,email,password,service_area,profile_photo) VALUES (?,?,?,?,?,?)')
   ->execute([$name, $phone, $email, password_hash($pass, PASSWORD_DEFAULT), $area, $photo]);

jsonResponse(true, ['id' => $db->lastInsertId()], 'Engineer created successfully');

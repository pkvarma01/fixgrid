<?php
// routes/admin/engineer_update.php
// FIX: Column 'photo' renamed to 'profile_photo' to match the database schema.
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(false, null, 'id required', 422);

$name  = trim($input['name']         ?? '');
$phone = trim($input['phone']        ?? '');
$email = trim($input['email']        ?? '');
$area  = trim($input['service_area'] ?? '');

$sets   = [];
$params = [];

if ($name)  { $sets[] = 'name=?';         $params[] = $name; }
if ($phone) { $sets[] = 'phone=?';        $params[] = $phone; }
if ($email) { $sets[] = 'email=?';        $params[] = $email; }
if ($area)  { $sets[] = 'service_area=?'; $params[] = $area; }

if (!empty($input['password'])) {
    $sets[]   = 'password=?';
    $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
}

// FIX: column is profile_photo
if (!empty($_FILES['photo']['tmp_name'])) {
    $sets[]   = 'profile_photo=?';
    $params[] = uploadFile($_FILES['photo'], 'engineers');
}

if (empty($sets)) jsonResponse(false, null, 'Nothing to update', 422);

$params[] = $id;
$db->prepare('UPDATE engineers SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);

jsonResponse(true, null, 'Engineer updated');

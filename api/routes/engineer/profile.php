<?php
// routes/engineer/profile.php — Engineer get/update own profile
require_once dirname(__DIR__, 2) . '/config.php';
$auth = requireAuth('engineer');
$db   = getDB();
$engineerId = (int)$auth['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sets   = [];
    $params = [];

    $name  = trim($input['name']         ?? '');
    $email = trim($input['email']        ?? '');
    $area  = trim($input['service_area'] ?? '');

    if ($name)  { $sets[] = 'name=?';         $params[] = $name; }
    if ($email) { $sets[] = 'email=?';         $params[] = $email; }
    if ($area)  { $sets[] = 'service_area=?';  $params[] = $area; }

    if (!empty($input['password']) && !empty($input['current_password'])) {
        // Verify current password first
        $engStmt = $db->prepare('SELECT password FROM engineers WHERE id=?');
        $engStmt->execute([$engineerId]);
        $hash = $engStmt->fetchColumn();
        if (!password_verify($input['current_password'], $hash)) {
            jsonResponse(false, null, 'Current password is incorrect', 401);
        }
        $sets[]   = 'password=?';
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    if (!empty($_FILES['photo']['tmp_name'])) {
        $photoUrl = uploadFile($_FILES['photo'], 'engineers');
        if ($photoUrl) { $sets[] = 'profile_photo=?'; $params[] = $photoUrl; }
    }

    if (empty($sets)) jsonResponse(false, null, 'No fields to update', 422);

    $params[] = $engineerId;
    $db->prepare('UPDATE engineers SET ' . implode(',', $sets) . ', updated_at=NOW() WHERE id=?')->execute($params);
}

$stmt = $db->prepare("
    SELECT id, name, phone, email, service_area, profile_photo, status, last_online, created_at,
        (SELECT COUNT(*) FROM jobs WHERE engineer_id=e.id AND status='completed') AS completed_jobs,
        (SELECT COALESCE(ROUND(AVG(rating),1),0) FROM ratings WHERE engineer_id=e.id) AS avg_rating
    FROM engineers e WHERE id=?
");
$stmt->execute([$engineerId]);
jsonResponse(true, $stmt->fetch());

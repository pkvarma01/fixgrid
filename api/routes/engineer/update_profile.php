<?php
// routes/engineer/update_profile.php — Alias for POST profile update
// The engineer app calls /engineer/update-profile for profile saves
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$sets   = [];
$params = [];

if (!empty($input['name']))         { $sets[] = 'name=?';         $params[] = trim($input['name']); }
if (!empty($input['email']))        { $sets[] = 'email=?';        $params[] = trim($input['email']); }
if (!empty($input['service_area'])) { $sets[] = 'service_area=?'; $params[] = trim($input['service_area']); }

if (!empty($input['password'])) {
    $sets[]   = 'password=?';
    $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
}

if (!empty($_FILES['photo']['tmp_name'])) {
    $sets[]   = 'profile_photo=?';
    $params[] = uploadFile($_FILES['photo'], 'engineers');
}

// Handle skill updates
if (isset($input['skills']) && is_array($input['skills'])) {
    $db->prepare("DELETE FROM engineer_skills WHERE engineer_id=?")->execute([$engineer['id']]);
    foreach (array_unique(array_map('intval', $input['skills'])) as $skillId) {
        if ($skillId > 0) {
            $db->prepare("INSERT IGNORE INTO engineer_skills (engineer_id,skill_id) VALUES (?,?)")
               ->execute([$engineer['id'], $skillId]);
        }
    }
}

if (empty($sets) && !isset($input['skills'])) {
    jsonResponse(false, null, 'Nothing to update', 422);
}

if (!empty($sets)) {
    $params[] = $engineer['id'];
    $db->prepare('UPDATE engineers SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
}

jsonResponse(true, null, 'Profile updated');

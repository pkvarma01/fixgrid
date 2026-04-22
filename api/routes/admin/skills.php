<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $input['action'] ?? 'create';

    if ($action === 'toggle') {
        $skillId = (int)($input['skill_id'] ?? 0);
        if (!$skillId) jsonResponse(false, null, 'skill_id required', 422);
        $db->prepare('UPDATE skills SET is_active = NOT is_active WHERE id=?')->execute([$skillId]);
        jsonResponse(true, null, 'Skill updated');
    }

    if ($action === 'delete') {
        $skillId = (int)($input['skill_id'] ?? 0);
        if (!$skillId) jsonResponse(false, null, 'skill_id required', 422);
        // Check if any active service uses this skill
        $inUse = $db->prepare("SELECT COUNT(*) FROM service_types WHERE required_skill_id=?");
        $inUse->execute([$skillId]);
        if ($inUse->fetchColumn() > 0) {
            jsonResponse(false, null, 'Cannot delete — skill is assigned to a service. Remove it from the service first.', 409);
        }
        // Remove from engineer profiles too
        $db->prepare("DELETE FROM engineer_skills WHERE skill_id=?")->execute([$skillId]);
        $db->prepare("DELETE FROM skills WHERE id=?")->execute([$skillId]);
        jsonResponse(true, null, 'Skill deleted');
    }

    // Create
    $name = trim($input['name'] ?? '');
    if (!$name) jsonResponse(false, null, 'Skill name required', 422);
    $exists = $db->prepare('SELECT id FROM skills WHERE name=?');
    $exists->execute([$name]);
    if ($exists->fetch()) jsonResponse(false, null, 'Skill already exists', 422);
    $db->prepare('INSERT INTO skills (name) VALUES (?)')->execute([$name]);
    jsonResponse(true, ['id' => $db->lastInsertId(), 'name' => $name], 'Skill created');
}

$stmt = $db->query("SELECT s.*,
    (SELECT COUNT(*) FROM engineer_skills es WHERE es.skill_id=s.id) AS engineer_count
    FROM skills s ORDER BY s.name");
jsonResponse(true, $stmt->fetchAll());

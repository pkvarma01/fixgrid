<?php
// routes/engineer/skills.php
// GET  — list all available skills (for dropdown)
// POST {skills: [id,id,...]} — save this engineer's selected skills
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skillIds = array_map('intval', (array)($input['skills'] ?? []));
    // Delete existing and re-insert
    $db->prepare("DELETE FROM engineer_skills WHERE engineer_id=?")->execute([$engineer['id']]);
    if ($skillIds) {
        $stmt = $db->prepare("INSERT IGNORE INTO engineer_skills (engineer_id, skill_id, certified_at) VALUES (?,?,CURDATE())");
        foreach ($skillIds as $sid) {
            if ($sid > 0) $stmt->execute([$engineer['id'], $sid]);
        }
    }
    jsonResponse(true, null, 'Skills updated');
}

// GET — return all skills with this engineer's selections marked
$all = $db->query("SELECT id, name FROM skills WHERE is_active=1 ORDER BY name")->fetchAll();

$mine = $db->prepare("SELECT skill_id FROM engineer_skills WHERE engineer_id=?");
$mine->execute([$engineer['id']]);
$myIds = array_column($mine->fetchAll(), 'skill_id');

foreach ($all as &$s) {
    $s['selected'] = in_array((int)$s['id'], array_map('intval', $myIds));
}

jsonResponse(true, $all);

<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
$sql = "SELECT e.*, 
    (SELECT COUNT(*) FROM jobs WHERE engineer_id=e.id AND status='completed') AS completed_jobs,
    (SELECT COALESCE(ROUND(AVG(rating),1),0) FROM ratings WHERE engineer_id=e.id) AS avg_rating
    FROM engineers e WHERE e.is_active=1 ORDER BY e.created_at DESC";
jsonResponse(true, $db->query($sql)->fetchAll());

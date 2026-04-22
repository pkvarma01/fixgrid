<?php // routes/admin/live_engineers.php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin'); $db = getDB();
$stmt = $db->query("SELECT e.id,e.name,e.phone,e.status,e.latitude,e.longitude,e.last_online, (SELECT COUNT(*) FROM jobs WHERE engineer_id=e.id AND status NOT IN ('completed','cancelled')) AS active_jobs FROM engineers e WHERE e.is_active=1 AND e.latitude IS NOT NULL");
jsonResponse(true, $stmt->fetchAll());

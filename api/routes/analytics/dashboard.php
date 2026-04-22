<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
$period = (int)($input['period'] ?? 30);

$daily = $db->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS jobs FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date");
$daily->execute([$period]);

$byService = $db->prepare("SELECT s.name, COUNT(j.id) AS total_jobs FROM jobs j LEFT JOIN service_types s ON j.service_id=s.id WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY s.id ORDER BY total_jobs DESC");
$byService->execute([$period]);

$stats = $db->prepare("SELECT COUNT(*) AS total_jobs, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed, SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled, ROUND(AVG(CASE WHEN status='completed' AND start_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,created_at,start_time) END),0) AS avg_response_mins FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
$stats->execute([$period]);

jsonResponse(true, [
    'period'     => $period,
    'daily'      => $daily->fetchAll(),
    'by_service' => $byService->fetchAll(),
    'summary'    => $stats->fetch(),
]);

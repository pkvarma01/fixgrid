<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();
$from = $input['from'] ?? date('Y-m-01');
$to   = $input['to']   ?? date('Y-m-d');

$summary = $db->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
    COALESCE(SUM(CASE WHEN status='completed' THEN final_amount ELSE 0 END),0) AS revenue,
    COALESCE(SUM(CASE WHEN status='completed' THEN platform_charge ELSE 0 END),0) AS platform_fee,
    COALESCE(SUM(CASE WHEN status='completed' AND payment_method IN ('online','razorpay') THEN platform_charge ELSE 0 END),0) AS online_fee,
    COALESCE(SUM(CASE WHEN status='completed' AND payment_method='cash' THEN platform_charge ELSE 0 END),0) AS cash_fee
    FROM jobs WHERE DATE(created_at) BETWEEN ? AND ?");
$summary->execute([$from, $to]);
$sum = $summary->fetch();

$avgRating = $db->query("SELECT ROUND(AVG(rating),1) FROM ratings")->fetchColumn() ?: 0;

$topEngineers = $db->prepare("SELECT e.name,
    COUNT(j.id) AS jobs,
    COALESCE(SUM(j.final_amount),0) AS revenue,
    COALESCE(SUM(j.platform_charge),0) AS platform_earned,
    COALESCE(ROUND(AVG(r.rating),1),0) AS rating
    FROM engineers e
    LEFT JOIN jobs j ON j.engineer_id=e.id AND DATE(j.created_at) BETWEEN ? AND ? AND j.status='completed'
    LEFT JOIN ratings r ON r.engineer_id=e.id
    WHERE e.is_active=1
    GROUP BY e.id ORDER BY jobs DESC LIMIT 10");
$topEngineers->execute([$from, $to]);

$byService = $db->prepare("SELECT COALESCE(s.name, j.service_type) AS name,
    COUNT(*) AS jobs,
    COALESCE(SUM(j.final_amount),0) AS revenue,
    COALESCE(SUM(j.platform_charge),0) AS platform_fee
    FROM jobs j LEFT JOIN service_types s ON j.service_id=s.id
    WHERE DATE(j.created_at) BETWEEN ? AND ?
    GROUP BY j.service_id, j.service_type ORDER BY jobs DESC");
$byService->execute([$from, $to]);

$daily = $db->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS jobs,
    COALESCE(SUM(CASE WHEN status='completed' THEN final_amount ELSE 0 END),0) AS revenue,
    COALESCE(SUM(CASE WHEN status='completed' THEN platform_charge ELSE 0 END),0) AS platform_fee
    FROM jobs WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY date");
$daily->execute([$from, $to]);

// Cash fee outstanding (not yet collected)
$cashOutstanding = $db->prepare("
    SELECT COALESCE(SUM(j.platform_charge),0)
    FROM jobs j
    WHERE j.status='completed' AND j.payment_method='cash'
    AND DATE(j.end_time) BETWEEN ? AND ?
    AND j.id NOT IN (SELECT job_id FROM platform_fee_collections WHERE status='collected')
");
$cashOutstanding->execute([$from, $to]);

jsonResponse(true, array_merge($sum, [
    'avg_rating'        => (float)$avgRating,
    'cash_outstanding'  => (float)$cashOutstanding->fetchColumn(),
    'top_engineers'     => $topEngineers->fetchAll(),
    'by_service'        => $byService->fetchAll(),
    'daily'             => $daily->fetchAll(),
    'from' => $from, 'to' => $to,
]));

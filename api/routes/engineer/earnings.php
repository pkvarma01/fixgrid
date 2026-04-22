<?php
// routes/engineer/earnings.php — Engineer earnings summary and history
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

// Summary
$summary = $db->prepare("SELECT
    COALESCE(SUM(amount), 0) AS total_earned,
    COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END), 0) AS total_paid,
    COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END), 0) AS pending_amount,
    COUNT(*) AS total_jobs
    FROM engineer_earnings WHERE engineer_id=?");
$summary->execute([$engineer['id']]);

// Monthly breakdown
$monthly = $db->prepare("SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS month,
    COALESCE(SUM(amount), 0) AS earned,
    COUNT(*) AS jobs
    FROM engineer_earnings WHERE engineer_id=?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC LIMIT 12");
$monthly->execute([$engineer['id']]);

// Recent earnings with job info
$recent = $db->prepare("SELECT ee.*, j.job_number,
    COALESCE(s.name, j.service_type) AS service,
    c.name AS customer
    FROM engineer_earnings ee
    JOIN jobs j ON ee.job_id = j.id
    LEFT JOIN service_types s ON j.service_id = s.id
    JOIN customers c ON j.customer_id = c.id
    WHERE ee.engineer_id = ?
    ORDER BY ee.created_at DESC LIMIT 30");
$recent->execute([$engineer['id']]);

jsonResponse(true, [
    'summary' => $summary->fetch(),
    'monthly' => $monthly->fetchAll(),
    'recent'  => $recent->fetchAll(),
]);

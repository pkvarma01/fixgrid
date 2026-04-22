<?php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$totalEngineers   = (int)$db->query("SELECT COUNT(*) FROM engineers WHERE is_active=1")->fetchColumn();
$availEngineers   = (int)$db->query("SELECT COUNT(*) FROM engineers WHERE status='available' AND is_active=1")->fetchColumn();
$busyEngineers    = (int)$db->query("SELECT COUNT(*) FROM engineers WHERE status='busy' AND is_active=1")->fetchColumn();
$completedToday   = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status='completed' AND DATE(end_time)=CURDATE()")->fetchColumn();
$completedMonth   = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status='completed' AND MONTH(end_time)=MONTH(NOW())")->fetchColumn();
$activeJobs       = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status NOT IN ('completed','cancelled')")->fetchColumn();
$pendingJobs      = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status='pending'")->fetchColumn();
$totalCustomers   = (int)$db->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn();

// Optional tables — safe fallback if table doesn't exist
try { $quotationJobs = (int)$db->query("SELECT COUNT(*) FROM job_quotations WHERE status='requested'")->fetchColumn(); } catch(Exception $e) { $quotationJobs = 0; }
try { $pickupJobs    = (int)$db->query("SELECT COUNT(*) FROM device_pickups WHERE status='requested'")->fetchColumn(); } catch(Exception $e) { $pickupJobs = 0; }
try { $avgRating     = $db->query("SELECT ROUND(AVG(rating),1) FROM ratings")->fetchColumn() ?: 0; } catch(Exception $e) { $avgRating = 0; }

// Gross revenue
$revenueToday = (float)$db->query("SELECT COALESCE(SUM(final_amount),0) FROM jobs WHERE status='completed' AND DATE(end_time)=CURDATE()")->fetchColumn();
$revenueMonth = (float)$db->query("SELECT COALESCE(SUM(final_amount),0) FROM jobs WHERE status='completed' AND MONTH(end_time)=MONTH(NOW()) AND YEAR(end_time)=YEAR(NOW())")->fetchColumn();

// Platform fee
try { $feeToday = (float)$db->query("SELECT COALESCE(SUM(platform_charge),0) FROM jobs WHERE status='completed' AND DATE(end_time)=CURDATE()")->fetchColumn(); } catch(Exception $e) { $feeToday = 0; }
try { $feeMonth = (float)$db->query("SELECT COALESCE(SUM(platform_charge),0) FROM jobs WHERE status='completed' AND MONTH(end_time)=MONTH(NOW()) AND YEAR(end_time)=YEAR(NOW())")->fetchColumn(); } catch(Exception $e) { $feeMonth = 0; }

// Cash platform fee outstanding
try {
    $cashFeeOutstanding = (float)$db->query("
        SELECT COALESCE(SUM(j.platform_charge),0)
        FROM jobs j
        WHERE j.status='completed' AND j.payment_method='cash'
        AND j.id NOT IN (SELECT job_id FROM platform_fee_collections WHERE status='collected')
    ")->fetchColumn();
} catch(Exception $e) { $cashFeeOutstanding = 0; }

// Online platform fee
try {
    $onlineFeeMonth = (float)$db->query("
        SELECT COALESCE(SUM(platform_charge),0) FROM jobs
        WHERE status='completed' AND payment_method IN ('online','razorpay','upi')
        AND MONTH(end_time)=MONTH(NOW()) AND YEAR(end_time)=YEAR(NOW())
    ")->fetchColumn();
} catch(Exception $e) { $onlineFeeMonth = 0; }

$recentJobs = $db->query("SELECT j.id, j.id AS job_id, j.job_number, j.status, j.priority, j.address, j.created_at,
    COALESCE(s.name, j.service_type) AS service, c.name AS customer, c.phone AS customer_phone,
    COALESCE(e.name,'Unassigned') AS engineer,
    COALESCE(j.final_amount, j.amount, 0) AS amount,
    COALESCE(j.platform_charge, 0) AS platform_charge,
    j.payment_method
    FROM jobs j JOIN customers c ON j.customer_id=c.id
    LEFT JOIN engineers e ON j.engineer_id=e.id
    LEFT JOIN service_types s ON j.service_id=s.id
    ORDER BY j.created_at DESC LIMIT 10")->fetchAll();

$topEngineers = $db->query("SELECT e.name, COUNT(j.id) AS jobs,
    COALESCE(SUM(j.final_amount),0) AS revenue,
    COALESCE(SUM(j.platform_charge),0) AS platform_earned,
    COALESCE(ROUND(AVG(r.rating),1),0) AS rating
    FROM engineers e
    LEFT JOIN jobs j ON j.engineer_id=e.id AND j.status='completed' AND MONTH(j.end_time)=MONTH(NOW())
    LEFT JOIN ratings r ON r.engineer_id=e.id
    WHERE e.is_active=1
    GROUP BY e.id ORDER BY jobs DESC LIMIT 5")->fetchAll();

$daily = $db->query("SELECT DATE(end_time) AS date,
    COUNT(*) AS jobs,
    COALESCE(SUM(final_amount),0) AS revenue,
    COALESCE(SUM(platform_charge),0) AS platform_fee
    FROM jobs WHERE status='completed' AND end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(end_time) ORDER BY date DESC")->fetchAll();

jsonResponse(true, [
    'total_engineers'       => $totalEngineers,
    'available_engineers'   => $availEngineers,
    'busy_engineers'        => $busyEngineers,
    'completed_today'       => $completedToday,
    'completed_month'       => $completedMonth,
    'active_jobs'           => $activeJobs,
    'pending_jobs'          => $pendingJobs,
    'quotation_requests'    => $quotationJobs,
    'pickup_requests'       => $pickupJobs,
    'total_customers'       => $totalCustomers,
    'revenue_today'         => $revenueToday,
    'revenue_month'         => $revenueMonth,
    'platform_fee_today'    => $feeToday,
    'platform_fee_month'    => $feeMonth,
    'cash_fee_outstanding'  => $cashFeeOutstanding,
    'online_fee_month'      => $onlineFeeMonth,
    'avg_rating'            => (float)$avgRating,
    'recent_jobs'           => $recentJobs,
    'top_engineers'         => $topEngineers,
    'daily'                 => $daily,
]);

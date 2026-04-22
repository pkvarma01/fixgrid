<?php
// routes/engineer/jobs.php
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();
$engineerId = (int)$engineer['id'];

$filter = $input['filter'] ?? 'active';

if ($filter === 'history') {
    $stmt = $db->prepare("
        SELECT j.*, s.name AS service_name, c.name AS customer_name,
               COALESCE(j.final_amount, j.amount, 0) AS display_amount,
               r.rating, r.feedback
        FROM jobs j
        JOIN service_types s ON j.service_id = s.id
        JOIN customers c ON j.customer_id = c.id
        LEFT JOIN ratings r ON r.job_id = j.id AND r.engineer_id = j.engineer_id
        WHERE j.engineer_id = ? AND j.status IN ('completed','cancelled','quotation_rejected')
        ORDER BY j.updated_at DESC LIMIT 50
    ");
    $stmt->execute([$engineerId]);
    jsonResponse(true, $stmt->fetchAll());
}

// Get engineer location for distance calc
$engLoc = $db->prepare("SELECT latitude, longitude FROM engineers WHERE id=?");
$engLoc->execute([$engineerId]);
$engLoc = $engLoc->fetch();
$engLat = (float)($engLoc['latitude'] ?? 0);
$engLng = (float)($engLoc['longitude'] ?? 0);

// ── 1. Jobs directly assigned to this engineer (all active statuses) ──────────
$active = $db->prepare("
    SELECT j.*, s.name AS service_name, c.name AS customer_name, c.phone AS customer_phone,
           COALESCE(j.final_amount, j.amount, 0) AS display_amount,
           ts.label AS slot_label, ts.start_time AS slot_start, ts.end_time AS slot_end
    FROM jobs j
    JOIN service_types s ON j.service_id = s.id
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN time_slots ts ON ts.id = j.slot_id
    WHERE j.engineer_id = ?
      AND j.status IN ('assigned','accepted','on_the_way','arrived','working',
                       'awaiting_quotation','quotation_sent','quotation_approved',
                       'pickup_requested','device_picked','revisit_scheduled')
    ORDER BY COALESCE(j.scheduled_date, j.created_at) ASC
");
$active->execute([$engineerId]);
$activeJobs = $active->fetchAll();

// ── 2. Broadcast offered jobs (job_offers row, engineer_id still NULL or pending) ─
$offered = $db->prepare("
    SELECT j.*, s.name AS service_name, c.name AS customer_name, c.phone AS customer_phone,
           jo.offered_at,
           COALESCE(j.final_amount, j.amount, 0) AS display_amount,
           ts.label AS slot_label, ts.start_time AS slot_start, ts.end_time AS slot_end,
           COALESCE((SELECT ROUND(AVG(r2.rating),1) FROM ratings r2 WHERE r2.engineer_id=$engineerId),0) AS avg_rating,
           COALESCE((SELECT COUNT(*) FROM jobs j2 WHERE j2.engineer_id=$engineerId AND j2.status='completed'),0) AS completed_jobs,
           COALESCE(
               (SELECT JSON_UNQUOTE(JSON_EXTRACT(n.data,'$.type'))
                FROM notifications n
                WHERE n.user_id=$engineerId AND n.user_type='engineer'
                  AND JSON_UNQUOTE(JSON_EXTRACT(n.data,'$.job_id')) = CAST(j.id AS CHAR)
                ORDER BY n.created_at DESC LIMIT 1),
               'zone_job'
           ) AS notification_type
    FROM job_offers jo
    JOIN jobs j  ON jo.job_id = j.id
    JOIN service_types s ON j.service_id = s.id
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN time_slots ts ON ts.id = j.slot_id
    WHERE jo.engineer_id = ?
      AND jo.status = 'pending'
      AND j.status = 'pending'
    ORDER BY j.is_emergency DESC, j.scheduled_date ASC, jo.offered_at ASC
");
$offered->execute([$engineerId]);
$offeredJobs = $offered->fetchAll();

// ── Distance helper ──────────────────────────────────────────────────────────
function calcDistKm(float $lat1, float $lng1, float $lat2, float $lng2): ?float {
    if (!$lat1 || !$lng1 || !$lat2 || !$lng2) return null;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
    return round(6371 * 2 * asin(sqrt($a)), 1);
}

$addDist = function (array &$jobs) use ($engLat, $engLng): void {
    foreach ($jobs as &$j) {
        $j['distance_km'] = calcDistKm($engLat, $engLng, (float)($j['latitude'] ?? 0), (float)($j['longitude'] ?? 0));
    }
};
$addDist($offeredJobs);
$addDist($activeJobs);

// Offered jobs first (so engineer sees new/scheduled offers at top)
jsonResponse(true, array_merge($offeredJobs, $activeJobs));

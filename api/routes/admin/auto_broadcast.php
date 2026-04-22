<?php
// routes/admin/auto_broadcast.php
// Cron job: broadcast same-day scheduled jobs to smart-ranked engineers
// Schedule: daily at 7:00 AM in cPanel cron
//
// FIX: MySQL strict mode rejects HAVING that references a computed SELECT alias
//      (distance_km) directly alongside base-table columns. Wrapped in subquery
//      so the outer WHERE sees 'distance_km' as a real column — same pattern
//      already used in zone_notify.php and create_job_v2.php.
require_once dirname(__DIR__, 2) . '/config.php';
$db = getDB();

$today  = date('Y-m-d');
$radius = (float)getSettingValue('assign_radius_km', 20);

// All pending scheduled jobs for today with no active offers yet
$jobs = $db->query("
    SELECT j.*, s.name AS service_name, s.required_skill_id
    FROM jobs j
    LEFT JOIN service_types s ON j.service_id = s.id
    WHERE j.status = 'pending'
      AND j.engineer_id IS NULL
      AND j.scheduled_date = '$today'
      AND (SELECT COUNT(*) FROM job_offers jo WHERE jo.job_id = j.id AND jo.status = 'pending') = 0
")->fetchAll();

$totalNotified = 0;

foreach ($jobs as $job) {
    $lat             = (float)$job['latitude'];
    $lng             = (float)$job['longitude'];
    $requiredSkillId = (int)($job['required_skill_id'] ?? 0);

    // FIX: Use subquery so WHERE can reference the computed distance_km alias
    //      without triggering "Unknown column in HAVING" on strict MySQL servers.
    $stmt = $db->prepare("
        SELECT * FROM (
            SELECT e.*,
                ROUND(6371*ACOS(COS(RADIANS(?))*COS(RADIANS(e.latitude))*COS(RADIANS(e.longitude)-RADIANS(?))+SIN(RADIANS(?))*SIN(RADIANS(e.latitude))),2) AS distance_km,
                COALESCE((SELECT ROUND(AVG(r.rating),2) FROM ratings r WHERE r.engineer_id=e.id),0) AS avg_rating,
                COALESCE((SELECT COUNT(*) FROM jobs j2 WHERE j2.engineer_id=e.id AND j2.status='completed'),0) AS completed_jobs,
                CASE WHEN ? = 0 THEN 1 WHEN EXISTS(SELECT 1 FROM engineer_skills es WHERE es.engineer_id=e.id AND es.skill_id=?) THEN 1 ELSE 0 END AS skill_match
            FROM engineers e
            WHERE e.is_active=1 AND e.status='available' AND e.latitude IS NOT NULL
        ) AS sub
        WHERE sub.distance_km <= ?
        ORDER BY sub.distance_km ASC LIMIT 50
    ");
    $stmt->execute([$lat, $lng, $lat, $requiredSkillId, $requiredSkillId, $radius]);
    $candidates = $stmt->fetchAll();
    if (!$candidates) continue;

    $maxDist = max(array_column($candidates,'distance_km')) ?: 1;
    $maxJobs = max(array_column($candidates,'completed_jobs')) ?: 1;
    foreach ($candidates as &$c) {
        $c['match_score'] =
            (1 - $c['distance_km']/$maxDist) * 0.40 +
            ((float)$c['skill_match'])        * 0.25 +
            ($c['avg_rating']/5)              * 0.20 +
            ($c['completed_jobs']/$maxJobs)   * 0.15;
    }
    unset($c);
    usort($candidates, fn($a,$b) => $b['match_score'] <=> $a['match_score']);

    $limit = 5;
    foreach ($candidates as $eng) {
        if ($limit <= 0) break;
        if ($requiredSkillId && !$eng['skill_match'] && ($limit > 2)) continue;
        $db->prepare("INSERT IGNORE INTO job_offers (job_id,engineer_id) VALUES (?,?)")->execute([$job['id'],$eng['id']]);
        sendPushNotification($eng['device_token']??'','📅 Scheduled Job Today','Job #'.$job['job_number'].' — '.$job['service_name'].' is today!',['job_id'=>$job['id'],'type'=>'job_offer']);
        logNotification($eng['id'],'engineer','📅 Today\'s Scheduled Job','Job #'.$job['job_number'].' near you. Tap to accept.',['job_id'=>$job['id']]);
        $totalNotified++;
        $limit--;
    }
}

jsonResponse(true, ['jobs_processed' => count($jobs), 'engineers_notified' => $totalNotified]);

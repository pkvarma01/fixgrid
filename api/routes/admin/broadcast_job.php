<?php
// routes/admin/broadcast_job.php
// Smart broadcast a pending/unassigned job to best-matched engineers
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$job = $db->query("SELECT j.*, s.name AS service_name, s.required_skill_id
    FROM jobs j LEFT JOIN service_types s ON j.service_id = s.id
    WHERE j.id = $jobId")->fetch();

if (!$job)                    jsonResponse(false, null, 'Job not found', 404);
if ($job['status'] !== 'pending') jsonResponse(false, null, 'Job is already assigned or active', 409);
if ($job['engineer_id'])      jsonResponse(false, null, 'Job already has an engineer', 409);

$lat            = (float)$job['latitude'];
$lng            = (float)$job['longitude'];
$serviceId      = (int)$job['service_id'];
$requiredSkillId= (int)($job['required_skill_id'] ?? 0);
$radius         = (float)getSettingValue('assign_radius_km', 20);

// Smart ranking query — same scoring as create_job_v2
$sql = "
    SELECT e.*,
        ROUND(6371*ACOS(COS(RADIANS(:lat1))*COS(RADIANS(e.latitude))*COS(RADIANS(e.longitude)-RADIANS(:lng))+SIN(RADIANS(:lat2))*SIN(RADIANS(e.latitude))),2) AS distance_km,
        COALESCE((SELECT ROUND(AVG(r.rating),2) FROM ratings r WHERE r.engineer_id=e.id),0) AS avg_rating,
        COALESCE((SELECT COUNT(*) FROM jobs j WHERE j.engineer_id=e.id AND j.status='completed'),0) AS completed_jobs,
        CASE WHEN :skill=0 THEN 1
             WHEN EXISTS(SELECT 1 FROM engineer_skills es WHERE es.engineer_id=e.id AND es.skill_id=:skill2) THEN 1
             ELSE 0 END AS skill_match
    FROM engineers e
    WHERE e.is_active=1 AND e.status='available' AND e.latitude IS NOT NULL
      AND e.id NOT IN (SELECT engineer_id FROM job_offers WHERE job_id=:jid AND status IN ('pending','accepted'))
    HAVING distance_km <= :radius
    ORDER BY distance_km ASC LIMIT 50
";
$stmt = $db->prepare($sql);
$stmt->execute([':lat1'=>$lat,':lat2'=>$lat,':lng'=>$lng,':skill'=>$requiredSkillId,':skill2'=>$requiredSkillId,':jid'=>$jobId,':radius'=>$radius]);
$candidates = $stmt->fetchAll();

if (!$candidates) jsonResponse(false, null, 'No available engineers within ' . $radius . 'km', 404);

// Score and sort
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

// Top 5, prefer skill-matched
$toNotify = []; $limit = 5;
foreach ($candidates as $c) {
    if ($limit <= 0) break;
    if ($requiredSkillId && !$c['skill_match'] && count($toNotify) >= 3) continue;
    $toNotify[] = $c;
    $limit--;
}

$count = 0;
$scheduledDate = $job['scheduled_date'] ?? null;
$title = $scheduledDate && $scheduledDate > date('Y-m-d') ? '📅 Scheduled Job Available' : '🔔 New Job Available';
$body  = 'Job #' . $job['job_number'] . ' — ' . $job['service_name'];
if ($scheduledDate) $body .= ' | Date: ' . $scheduledDate;

foreach ($toNotify as $eng) {
    $db->prepare("INSERT IGNORE INTO job_offers (job_id,engineer_id) VALUES (?,?)")->execute([$jobId,$eng['id']]);
    sendPushNotification($eng['device_token']??'', $title, $body . ' | Score: ' . round($eng['match_score'],2), ['job_id'=>$jobId,'type'=>'job_offer']);
    logNotification($eng['id'],'engineer',$title,$body,['job_id'=>$jobId]);
    $count++;
}

jsonResponse(true, [
    'engineers_notified' => $count,
    'job_id'             => $jobId,
    'engineers'          => array_map(fn($e) => [
        'name'           => $e['name'],
        'distance_km'    => $e['distance_km'],
        'avg_rating'     => $e['avg_rating'],
        'completed_jobs' => $e['completed_jobs'],
        'skill_match'    => (bool)$e['skill_match'],
        'score'          => round($e['match_score'], 3),
    ], $toNotify),
], $count . ' engineer(s) notified for job #' . $job['job_number']);

<?php
// routes/engineer/reject_job.php
// FIX: Uses job_offers table for broadcast model
// When rejected: marks offer as rejected, finds and offers job to next nearest engineer
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId  = (int)($input['job_id'] ?? 0);
$reason = trim($input['reason'] ?? 'Unable to take this job');
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);
$engineerId = (int)$engineer['id'];

// Check broadcast offer
$offerStmt = $db->prepare("SELECT * FROM job_offers WHERE job_id=? AND engineer_id=? AND status='pending'");
$offerStmt->execute([$jobId, $engineerId]);
$offer = $offerStmt->fetch();

// Check direct assignment
$directStmt = $db->prepare("SELECT * FROM jobs WHERE id=? AND engineer_id=? AND status IN ('assigned','accepted')");
$directStmt->execute([$jobId, $engineerId]);
$directJob = $directStmt->fetch();

if (!$offer && !$directJob) {
    jsonResponse(false, null, 'No active job offer found to reject', 404);
}

$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id=?");
$jobStmt->execute([$jobId]);
$job = $jobStmt->fetch();

if ($offer) {
    // Mark this offer rejected
    $db->prepare("UPDATE job_offers SET status='rejected', responded_at=NOW() WHERE job_id=? AND engineer_id=?")
       ->execute([$jobId, $engineerId]);
} else {
    // Direct-assigned: free job and engineer
    $db->prepare("UPDATE jobs SET status='pending', engineer_id=NULL, updated_at=NOW() WHERE id=?")
       ->execute([$jobId]);
    $db->prepare("UPDATE engineers SET status='available' WHERE id=?")->execute([$engineerId]);
}

// Find next nearest engineer not yet offered this job
$radius = (float)getSettingValue('assign_radius_km', 20);

// Get engineers already offered
$alreadyStmt = $db->prepare("SELECT engineer_id FROM job_offers WHERE job_id=?");
$alreadyStmt->execute([$jobId]);
$alreadyOffered = $alreadyStmt->fetchAll(PDO::FETCH_COLUMN);
$alreadyOffered[] = $engineerId; // exclude current engineer too
$ph = implode(',', array_fill(0, count($alreadyOffered), '?'));

$nextStmt = $db->prepare("
    SELECT e.*,
        (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(e.latitude)) *
        COS(RADIANS(e.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(e.latitude)))) AS distance_km
    FROM engineers e
    WHERE e.is_active=1 AND e.status='available' AND e.latitude IS NOT NULL
      AND e.id NOT IN ($ph)
    HAVING distance_km <= ?
    ORDER BY distance_km ASC LIMIT 1
");
$params = [$job['latitude'], $job['longitude'], $job['latitude'], ...$alreadyOffered, $radius];
$nextStmt->execute($params);
$nextEng = $nextStmt->fetch();

if ($nextEng) {
    $db->prepare("INSERT IGNORE INTO job_offers (job_id, engineer_id) VALUES (?,?)")
       ->execute([$jobId, $nextEng['id']]);
    sendPushNotification($nextEng['device_token'] ?? '', 'New Job Available', 'Job #' . $job['job_number'] . ' near you — tap to accept', ['job_id'=>$jobId,'type'=>'job_offer']);
    logNotification($nextEng['id'], 'engineer', 'New Job Available', 'Job #' . $job['job_number'] . ' is available near you.', ['job_id'=>$jobId]);
}

logNotification(0, 'admin', 'Job Rejected', $engineer['name'].' rejected job #'.$job['job_number'].'. Reason: '.$reason, ['job_id'=>$jobId,'reason'=>$reason]);

jsonResponse(true, ['job_id'=>$jobId], 'Job rejected. ' . ($nextEng ? 'Next engineer notified.' : 'No engineers available nearby.'));

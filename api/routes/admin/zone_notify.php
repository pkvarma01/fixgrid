<?php
// api/routes/admin/zone_notify.php
// Find which zones a job location falls in and notify assigned engineers
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');

$db    = getDB();
$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

$job = $db->prepare("SELECT * FROM jobs WHERE id=?");
$job->execute([$jobId]);
$job = $job->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);

$lat = (float)$job['latitude'];
$lng = (float)$job['longitude'];

// Find all active zones whose radius covers the job location.
// FIX: MySQL does not allow HAVING to reference a column from the same
//      SELECT's base table (z.radius_km) alongside a computed alias.
//      Wrapping in a subquery lets the outer WHERE see both columns cleanly.
$zones = $db->query("
    SELECT * FROM (
        SELECT z.*,
            ROUND(6371 * ACOS(
                COS(RADIANS($lat)) * COS(RADIANS(z.latitude)) *
                COS(RADIANS(z.longitude) - RADIANS($lng)) +
                SIN(RADIANS($lat)) * SIN(RADIANS(z.latitude))
            ), 2) AS distance_km
        FROM zones z
        WHERE z.is_active   = 1
          AND z.latitude  IS NOT NULL
          AND z.longitude IS NOT NULL
    ) AS sub
    WHERE sub.distance_km <= sub.radius_km
    ORDER BY sub.distance_km ASC
    LIMIT 1
")->fetchAll();

if (empty($zones)) {
    jsonResponse(true, ['notified' => 0, 'zones_matched' => 0], 'No zone found for this job location — no engineers notified');
}

$notified    = [];
$zoneMatched = [];

foreach ($zones as $zone) {
    $zoneMatched[] = $zone['name'] . ' (' . $zone['city'] . ')';

    // Get available engineers in this zone
    $stmt = $db->prepare("
        SELECT e.id, e.name, e.device_token
        FROM zone_engineers ze
        JOIN engineers e ON e.id = ze.engineer_id
        WHERE ze.zone_id = ?
          AND ze.is_available = 1
          AND e.is_active = 1
          AND e.status = 'available'
          AND e.id NOT IN (
              SELECT engineer_id FROM job_offers
              WHERE job_id = ? AND status IN ('pending','accepted')
          )
    ");
    $stmt->execute([$zone['id'], $jobId]);
    $engineers = $stmt->fetchAll();

    foreach ($engineers as $eng) {
        if (isset($notified[$eng['id']])) continue; // Don't double-notify

        $title = '🔔 New Job in Your Zone';
        $body  = 'Job #' . $job['job_number'] . ' — ' . $job['service_type']
               . ' | Zone: ' . $zone['name'] . ', ' . $zone['city'];

        // Create job offer record
        $db->prepare("INSERT IGNORE INTO job_offers (job_id, engineer_id) VALUES (?,?)")
           ->execute([$jobId, $eng['id']]);

        // Send push notification
        sendPushNotification(
            $eng['device_token'] ?? '',
            $title,
            $body,
            ['job_id' => $jobId, 'type' => 'zone_job', 'zone' => $zone['name']]
        );

        // Log notification
        logNotification($eng['id'], 'engineer', $title, $body, [
            'job_id'  => $jobId,
            'zone_id' => $zone['id'],
            'type'    => 'zone_job'
        ]);

        $notified[$eng['id']] = $eng['name'];
    }
}

jsonResponse(true, [
    'notified'      => count($notified),
    'zones_matched' => count($zoneMatched),
    'zones'         => $zoneMatched,
    'engineers'     => array_values($notified),
], count($notified) . ' engineer(s) notified across ' . count($zoneMatched) . ' zone(s)');

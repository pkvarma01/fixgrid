<?php
// routes/engineer/location.php — Engineer updates GPS coordinates
require_once dirname(__DIR__, 2) . '/config.php';
$auth = requireAuth('engineer');
$db   = getDB();
$engineerId = (int)$auth['id'];

$lat      = (float)($input['latitude']  ?? 0);
$lng      = (float)($input['longitude'] ?? 0);
$jobId    = (int)($input['job_id']      ?? 0);
$speed    = (float)($input['speed']     ?? 0);
$heading  = (float)($input['heading']   ?? 0);
$accuracy = (float)($input['accuracy']  ?? 0);

if (!$lat || !$lng) jsonResponse(false, null, 'latitude and longitude required', 422);

// Update engineer current position and last_online
$db->prepare("UPDATE engineers SET latitude=?, longitude=?, last_online=NOW() WHERE id=?")
   ->execute([$lat, $lng, $engineerId]);

// Log to location history
$db->prepare("INSERT INTO engineer_locations (engineer_id,latitude,longitude,speed,heading,accuracy,job_id,timestamp) VALUES (?,?,?,?,?,?,?,NOW())")
   ->execute([$engineerId, $lat, $lng, $speed, $heading, $accuracy, $jobId ?: null]);

jsonResponse(true, null, 'Location updated');

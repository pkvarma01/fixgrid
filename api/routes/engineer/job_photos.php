<?php
// routes/engineer/job_photos.php — Upload/fetch before & after job photos
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Verify job belongs to engineer
$job = $db->prepare("SELECT id, status FROM jobs WHERE id=? AND engineer_id=?");
$job->execute([$jobId, $engineer['id']]);
$job = $job->fetch();
if (!$job) jsonResponse(false, null, 'Job not found', 404);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $photos = $db->prepare("SELECT * FROM job_photos WHERE job_id=? ORDER BY photo_type, created_at");
    $photos->execute([$jobId]);
    jsonResponse(true, $photos->fetchAll());
}

// POST — upload photo
$photoType = trim($input['photo_type'] ?? 'before'); // before | after | during
if (!in_array($photoType, ['before', 'after', 'during'])) {
    jsonResponse(false, null, 'photo_type must be before, after, or during', 422);
}

if (empty($_FILES['photo']['tmp_name'])) {
    // Diagnose why file wasn't received
    $uploadErr = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errMsgs = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (exceeds server limit of ' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (exceeds form limit)',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded — check connection',
        UPLOAD_ERR_NO_FILE    => 'No photo file received — please select a photo',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing — contact support',
        UPLOAD_ERR_CANT_WRITE => 'Server cannot write file — contact support',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension',
    ];
    $msg = $errMsgs[$uploadErr] ?? 'Upload failed (code ' . $uploadErr . ')';
    jsonResponse(false, null, $msg, 422);
}

$url = uploadFile($_FILES['photo'], 'job_photos');
if (!$url) jsonResponse(false, null, 'Failed to save photo', 500);

// Ensure job_photos table exists (safe CREATE IF NOT EXISTS)
$db->exec("CREATE TABLE IF NOT EXISTS `job_photos` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` int UNSIGNED NOT NULL,
    `engineer_id` int UNSIGNED NOT NULL,
    `photo_type` enum('before','after','during') NOT NULL DEFAULT 'before',
    `photo_url` varchar(500) NOT NULL,
    `caption` varchar(255) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$caption = trim($input['caption'] ?? '');
$db->prepare("INSERT INTO job_photos (job_id, engineer_id, photo_type, photo_url, caption)
    VALUES (?,?,?,?,?)")
   ->execute([$jobId, $engineer['id'], $photoType, $url, $caption]);

jsonResponse(true, ['photo_url' => $url, 'photo_type' => $photoType], ucfirst($photoType) . ' photo uploaded');

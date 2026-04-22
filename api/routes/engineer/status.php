<?php
// routes/engineer/status.php — Engineer sets own status: available / offline
// FIX: requireAuth() returns user row — use $engineer['id'] not $auth['id']
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$status = trim($input['status'] ?? '');

if (!in_array($status, ['available', 'offline'])) {
    jsonResponse(false, null, 'status must be: available or offline', 422);
}

$db->prepare("UPDATE engineers SET status=?, last_online=NOW() WHERE id=?")
   ->execute([$status, $engineer['id']]);

if ($status === 'offline') {
    $db->prepare("UPDATE engineers SET latitude=NULL, longitude=NULL WHERE id=?")
       ->execute([$engineer['id']]);
}

jsonResponse(true, ['status' => $status], 'Status updated to ' . $status);

<?php
// routes/chat/messages.php — Get/Send chat messages
require_once dirname(__DIR__, 2) . '/config.php';

// Determine caller type from Bearer token
$caller = null; $callerType = null;
$_hdrs = getallheaders();
$_tok = str_replace('Bearer ', '', $_hdrs['Authorization'] ?? $_hdrs['authorization'] ?? '');
if ($_tok) {
    $_db = getDB();
    $_ts = $_db->prepare('SELECT * FROM auth_tokens WHERE token=? AND expires_at > NOW()');
    $_ts->execute([$_tok]);
    $_tr = $_ts->fetch();
    if ($_tr) {
        $_tbl = ['customer'=>'customers','engineer'=>'engineers','admin'=>'admins'][$_tr['user_type']] ?? null;
        if ($_tbl) {
            $_us = $_db->prepare("SELECT * FROM {$_tbl} WHERE id=? AND is_active=1");
            $_us->execute([$_tr['user_id']]);
            $caller = $_us->fetch();
            if ($caller) $callerType = $_tr['user_type'];
        }
    }
}
if (!$caller) jsonResponse(false, null, 'Unauthorized', 401);

$db    = getDB();
$jobId = (int)($input['job_id'] ?? 0);
if (!$jobId) jsonResponse(false, null, 'job_id required', 422);

// Get or create chat room
$room = $db->prepare('SELECT * FROM chat_rooms WHERE job_id=?');
$room->execute([$jobId]);
$room = $room->fetch();

if (!$room) {
    $job = $db->query("SELECT * FROM jobs WHERE id=$jobId")->fetch();
    if (!$job) jsonResponse(false, null, 'Job not found', 404);
    $db->prepare('INSERT INTO chat_rooms (job_id,customer_id,engineer_id) VALUES (?,?,?)')->execute([$jobId, $job['customer_id'], $job['engineer_id']]);
    $roomId = $db->lastInsertId();
    $room   = $db->query("SELECT * FROM chat_rooms WHERE id=$roomId")->fetch();
}

$method = $_SERVER['REQUEST_METHOD'];

// ── POST: send message ────────────────────────────────────
if ($method === 'POST') {
    $message   = trim($input['message'] ?? '');
    $mediaUrl  = null;
    $mediaType = null;

    if (!empty($_FILES['media']['tmp_name'])) {
        $ext      = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $isImage  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $isVideo  = in_array($ext, ['mp4', 'mov']);
        $isDoc    = in_array($ext, ['pdf', 'doc', 'docx']);
        $folder   = $isImage ? 'chat_images' : ($isVideo ? 'chat_videos' : 'chat_docs');
        $mediaUrl = uploadFile($_FILES['media'], $folder);
        $mediaType = $isImage ? 'image' : ($isVideo ? 'video' : 'document');
    }

    if (!$message && !$mediaUrl) jsonResponse(false, null, 'message or media required', 422);

    $db->prepare('INSERT INTO chat_messages (room_id,sender_type,sender_id,message,media_url) VALUES (?,?,?,?,?)')->execute([$room['id'], $callerType, $caller['id'], $message ?: null, $mediaUrl]);

    $msgId = $db->lastInsertId();

    // Push notification to the other party
    if ($callerType === 'customer' && $room['engineer_id']) {
        $eng = $db->query("SELECT device_token, name FROM engineers WHERE id={$room['engineer_id']}")->fetch();
        sendPushNotification($eng['device_token'] ?? '', 'New message', $caller['name'] . ': ' . ($message ?: '📷 Media'), ['type' => 'chat', 'job_id' => $jobId]);
    } elseif ($callerType === 'engineer') {
        $cust = $db->query("SELECT device_token, name FROM customers WHERE id={$room['customer_id']}")->fetch();
        sendPushNotification($cust['device_token'] ?? '', 'New message from engineer', $caller['name'] . ': ' . ($message ?: '📷 Media'), ['type' => 'chat', 'job_id' => $jobId]);
    }

    jsonResponse(true, ['id' => $msgId, 'message' => $message, 'media_url' => $mediaUrl, 'sender_type' => $callerType, 'created_at' => date('Y-m-d H:i:s')], 'Message sent');
}

// ── GET: fetch messages ───────────────────────────────────
$since = $input['since'] ?? null; // for polling — pass last message id
$query = 'SELECT cm.*, CASE cm.sender_type WHEN \'customer\' THEN c.name WHEN \'engineer\' THEN e.name ELSE \'Admin\' END AS sender_name FROM chat_messages cm LEFT JOIN customers c ON cm.sender_type=\'customer\' AND c.id=cm.sender_id LEFT JOIN engineers e ON cm.sender_type=\'engineer\' AND e.id=cm.sender_id WHERE cm.room_id=?';
$params = [$room['id']];
if ($since) { $query .= ' AND cm.id > ?'; $params[] = (int)$since; }
$query .= ' ORDER BY cm.created_at ASC LIMIT 100';

$msgs = $db->prepare($query);
$msgs->execute($params);
$messages = $msgs->fetchAll();

// Mark as read
$db->prepare("UPDATE chat_messages SET is_read=1 WHERE room_id=? AND sender_type!=?")->execute([$room['id'], $callerType]);

jsonResponse(true, ['room' => $room, 'messages' => $messages]);

<?php
// routes/engineer/notifications.php — Engineer notifications
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

$stmt = $db->prepare("SELECT * FROM notifications
    WHERE user_id=? AND user_type='engineer'
    ORDER BY created_at DESC LIMIT 30");
$stmt->execute([$engineer['id']]);
$notifications = $stmt->fetchAll();

// Mark all as read
$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND user_type='engineer'")
   ->execute([$engineer['id']]);

// Unread count before marking
$unread = array_filter($notifications, fn($n) => !$n['is_read']);

jsonResponse(true, [
    'notifications' => $notifications,
    'unread_count'  => count($unread),
]);

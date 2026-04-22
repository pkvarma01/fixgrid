<?php
// routes/customer/notifications.php — Customer notifications list
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all read
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND user_type='customer'")->execute([$customer['id']]);
    jsonResponse(true, null, 'Marked as read');
}

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id=? AND user_type='customer' ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$customer['id']]);
$notifs = $stmt->fetchAll();
$unread = array_sum(array_column($notifs, 'is_read') ? [] : [0]);
$unread = count(array_filter($notifs, fn($n) => !$n['is_read']));
jsonResponse(true, ['notifications' => $notifs, 'unread_count' => $unread]);

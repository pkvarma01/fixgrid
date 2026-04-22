<?php
// routes/engineer/availability.php
// GET  — return this week's availability for the engineer
// POST {availability: [{slot_id, date, is_available}]} — bulk save
require_once dirname(__DIR__, 2) . '/config.php';
$engineer = requireAuth('engineer');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = $input['availability'] ?? [];
    if (!is_array($rows)) jsonResponse(false, null, 'availability must be an array', 422);

    foreach ($rows as $row) {
        $slotId = (int)($row['slot_id'] ?? 0);
        $date   = preg_replace('/[^0-9-]/', '', $row['date'] ?? '');
        $avail  = isset($row['is_available']) ? (int)(bool)$row['is_available'] : 1;
        if (!$slotId || !$date) continue;
        $db->prepare("INSERT INTO engineer_availability (engineer_id, slot_id, date, is_available)
            VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE is_available=?")
           ->execute([$engineer['id'], $slotId, $date, $avail, $avail]);
    }
    jsonResponse(true, null, 'Availability saved');
}

// GET — return slots + this engineer's availability for next 7 days
$slots = $db->query("SELECT * FROM time_slots WHERE is_active=1 ORDER BY start_time")->fetchAll();

$dates = [];
for ($i = 0; $i < 7; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i days"));
}

// Fetch existing availability records
$stmt = $db->prepare("SELECT slot_id, date, is_available FROM engineer_availability
    WHERE engineer_id=? AND date BETWEEN ? AND ?");
$stmt->execute([$engineer['id'], $dates[0], $dates[6]]);
$existing = [];
foreach ($stmt->fetchAll() as $row) {
    $existing[$row['date']][$row['slot_id']] = (bool)$row['is_available'];
}

jsonResponse(true, [
    'slots'    => $slots,
    'dates'    => $dates,
    'schedule' => $existing,
]);

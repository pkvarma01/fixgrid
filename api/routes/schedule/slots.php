<?php
require_once dirname(__DIR__, 2) . '/config.php';
$db = getDB();

$date      = $input['date']       ?? date('Y-m-d');
$serviceId = (int)($input['service_id'] ?? 0);

$slots = $db->query("SELECT * FROM time_slots WHERE is_active=1 ORDER BY start_time")->fetchAll();

$result = [];
foreach ($slots as $slot) {
    $booked = $db->prepare("SELECT COUNT(*) FROM jobs WHERE slot_id=? AND scheduled_date=? AND status NOT IN ('cancelled')");
    $booked->execute([$slot['id'], $date]);
    $count  = (int)$booked->fetchColumn();
    $result[] = array_merge($slot, [
        'available'    => $count < 5,
        'booked_count' => $count,
    ]);
}

// Always return the same shape so the frontend only needs one parsing path
jsonResponse(true, ['date' => $date, 'slots' => $result]);

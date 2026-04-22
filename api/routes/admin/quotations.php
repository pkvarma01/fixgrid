<?php
// routes/admin/quotations.php
// FIX: Added parts_cost + installation_charge breakdown
// FIX: first_visit_charge deducted from revisit total
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $input['action']             ?? '';
    $quotationId = (int)($input['quotation_id'] ?? 0);

    if ($action === 'send') {
        if (!$quotationId) jsonResponse(false, null, 'quotation_id required', 422);

        $qStmt = $db->prepare("SELECT q.*, j.customer_id, j.job_number, j.engineer_id, j.visit_charge
            FROM job_quotations q JOIN jobs j ON q.job_id=j.id WHERE q.id=?");
        $qStmt->execute([$quotationId]);
        $q = $qStmt->fetch();
        if (!$q) jsonResponse(false, null, 'Quotation not found', 404);

        $partsCost           = (float)($input['parts_cost']           ?? 0);
        $installCharge       = (float)($input['installation_charge']  ?? 0);
        $adminNotes          = trim($input['admin_notes']             ?? '');
        $revisitDate         = $input['revisit_date']                 ?? null;
        $revisitSlot         = (int)($input['revisit_slot_id']        ?? 0) ?: null;

        // Total = parts + installation
        $totalAmount = $partsCost + $installCharge;
        if ($totalAmount <= 0) jsonResponse(false, null, 'Parts cost and/or installation charge required', 422);
        if (!$revisitDate)     jsonResponse(false, null, 'revisit_date required', 422);

        // First visit charge already collected — deduct from what customer owes on revisit
        $firstVisitCharge    = (float)($q['visit_charge'] ?? 0);
        // Amount customer pays on revisit = total - already paid visit charge
        $revisitPayable      = max(0, $totalAmount - $firstVisitCharge);

        $db->prepare("UPDATE job_quotations SET
            parts_cost=?, installation_charge=?, quotation_amount=?,
            first_visit_charge=?, admin_notes=?, revisit_date=?, revisit_slot_id=?,
            status='sent', updated_at=NOW() WHERE id=?")
           ->execute([$partsCost, $installCharge, $revisitPayable,
                      $firstVisitCharge, $adminNotes, $revisitDate, $revisitSlot, $quotationId]);

        $db->prepare("UPDATE jobs SET status='quotation_sent', updated_at=NOW() WHERE id=?")->execute([$q['job_id']]);

        // Build customer notification with full breakdown
        $slotLabel = '';
        if ($revisitSlot) {
            $slotStmt = $db->prepare("SELECT label FROM time_slots WHERE id=?");
            $slotStmt->execute([$revisitSlot]);
            $slotLabel = $slotStmt->fetchColumn() ?: '';
        }

        $notifMsg = "Quotation ready for job #{$q['job_number']}:\n";
        if ($partsCost > 0)      $notifMsg .= "Parts: ₹" . number_format($partsCost, 2) . "\n";
        if ($installCharge > 0)  $notifMsg .= "Installation: ₹" . number_format($installCharge, 2) . "\n";
        if ($firstVisitCharge>0) $notifMsg .= "Visit paid: -₹" . number_format($firstVisitCharge, 2) . "\n";
        $notifMsg .= "You pay on revisit: ₹" . number_format($revisitPayable, 2);
        $notifMsg .= "\nRevisit: $revisitDate" . ($slotLabel ? " ($slotLabel)" : "");

        $cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
        $cust->execute([$q['customer_id']]);
        sendPushNotification($cust->fetchColumn() ?? '', 'Quotation Ready', $notifMsg,
            ['type'=>'quotation_sent','job_id'=>$q['job_id'],'quotation_id'=>$quotationId]);
        logNotification($q['customer_id'], 'customer', 'Quotation Ready', $notifMsg, ['job_id'=>$q['job_id'],'quotation_id'=>$quotationId]);

        // Notify engineer
        $eng = $db->prepare("SELECT device_token FROM engineers WHERE id=?");
        $eng->execute([$q['engineer_id']]);
        sendPushNotification($eng->fetchColumn() ?? '', 'Quotation Sent',
            "Quotation sent to customer for job #{$q['job_number']}. Revisit: $revisitDate",
            ['type'=>'quotation_sent','job_id'=>$q['job_id']]);

        jsonResponse(true, [
            'parts_cost'          => $partsCost,
            'installation_charge' => $installCharge,
            'first_visit_deducted'=> $firstVisitCharge,
            'customer_pays'       => $revisitPayable,
        ], 'Quotation sent to customer. Customer pays ₹'.number_format($revisitPayable,2).' on revisit.');
    }

    // Update pickup status
    if ($action === 'update_pickup') {
        $pickupId     = (int)($input['pickup_id']     ?? 0);
        $status       = $input['status']              ?? '';
        $repairNotes  = trim($input['repair_notes']   ?? '');
        $repairCharge = (float)($input['repair_charge'] ?? 0);
        $allowed      = ['scheduled','picked','repaired','delivered'];
        if (!in_array($status, $allowed)) jsonResponse(false, null, 'Invalid pickup status', 422);

        $db->prepare("UPDATE device_pickups SET status=?, repair_notes=COALESCE(NULLIF(?,''),repair_notes), repair_charge=COALESCE(NULLIF(?,0),repair_charge), updated_at=NOW() WHERE id=?")
           ->execute([$status, $repairNotes, $repairCharge, $pickupId]);

        $pickStmt = $db->prepare("SELECT dp.*, j.customer_id, j.job_number FROM device_pickups dp JOIN jobs j ON dp.job_id=j.id WHERE dp.id=?");
        $pickStmt->execute([$pickupId]);
        $pickup = $pickStmt->fetch();

        if ($status === 'picked')     $db->prepare("UPDATE jobs SET status='device_picked', updated_at=NOW() WHERE id=?")->execute([$pickup['job_id']]);
        if ($status === 'delivered')  $db->prepare("UPDATE jobs SET status='completed', end_time=NOW(), updated_at=NOW() WHERE id=?")->execute([$pickup['job_id']]);

        $msgs = ['scheduled'=>'Pickup scheduled','picked'=>'Device picked up','repaired'=>'Device repaired','delivered'=>'Device delivered'];
        $cust = $db->prepare("SELECT device_token FROM customers WHERE id=?");
        $cust->execute([$pickup['customer_id']]);
        sendPushNotification($cust->fetchColumn() ?? '', 'Device Update', $msgs[$status] ?? 'Pickup updated', ['type'=>'pickup_update','job_id'=>$pickup['job_id']]);
        logNotification($pickup['customer_id'], 'customer', 'Device Update', $msgs[$status] ?? '', ['job_id'=>$pickup['job_id']]);

        jsonResponse(true, null, 'Pickup status updated');
    }

    jsonResponse(false, null, 'Invalid action', 422);
}

// GET: list quotations by status
$status = $input['status'] ?? 'requested';
$stmt = $db->prepare("
    SELECT q.*, j.job_number, j.address, j.visit_charge AS job_visit_charge,
           c.name AS customer_name, c.phone AS customer_phone,
           e.name AS engineer_name, ts.label AS slot_label
    FROM job_quotations q
    JOIN jobs j ON q.job_id = j.id
    JOIN customers c ON j.customer_id = c.id
    JOIN engineers e ON q.engineer_id = e.id
    LEFT JOIN time_slots ts ON q.revisit_slot_id = ts.id
    WHERE q.status = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$status]);
jsonResponse(true, $stmt->fetchAll());

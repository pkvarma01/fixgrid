<?php
// routes/admin/engineer_wallet.php — Admin view and manage engineer wallets
// FIX: engineer_wallet table may not have total_earned/total_withdrawn columns
//      Compute them dynamically from engineer_wallet_transactions instead
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $input['action']      ?? '';
    $engineerId = (int)($input['engineer_id'] ?? 0);
    $amount     = (float)($input['amount']    ?? 0);
    $note       = trim($input['note']         ?? '');

    if (!$engineerId || $amount <= 0) jsonResponse(false, null, 'engineer_id and amount required', 422);

    // Ensure wallet row exists
    $db->prepare("INSERT IGNORE INTO engineer_wallet (engineer_id, balance) VALUES (?,0)")->execute([$engineerId]);

    if ($action === 'credit') {
        $db->prepare("UPDATE engineer_wallet SET balance=balance+? WHERE engineer_id=?")->execute([$amount, $engineerId]);
        $db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id,type,amount,description) VALUES (?,?,?,?)")
           ->execute([$engineerId, 'credit', $amount, $note ?: 'Admin credit']);
        jsonResponse(true, null, '₹'.number_format($amount,2).' credited to engineer wallet');
    }

    if ($action === 'payout') {
        $walletStmt = $db->prepare("SELECT balance FROM engineer_wallet WHERE engineer_id=?");
        $walletStmt->execute([$engineerId]);
        $balance = (float)$walletStmt->fetchColumn();
        if ($amount > $balance) jsonResponse(false, null, 'Insufficient balance: ₹'.number_format($balance,2), 422);
        $db->prepare("UPDATE engineer_wallet SET balance=balance-? WHERE engineer_id=?")->execute([$amount, $engineerId]);
        $db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id,type,amount,description) VALUES (?,?,?,?)")
           ->execute([$engineerId, 'withdrawal', $amount, $note ?: 'Admin payout']);
        jsonResponse(true, null, 'Payout of ₹'.number_format($amount,2).' processed');
    }

    jsonResponse(false, null, 'Invalid action', 422);
}

// GET — compute total_earned and total_withdrawn from transactions
$stmt = $db->query("
    SELECT
        e.id,
        e.name,
        e.phone,
        COALESCE(ew.balance, 0) AS balance,
        COALESCE(SUM(CASE WHEN ewt.type IN ('credit') THEN ewt.amount ELSE 0 END), 0)    AS total_earned,
        COALESCE(SUM(CASE WHEN ewt.type = 'withdrawal'  THEN ewt.amount ELSE 0 END), 0)  AS total_withdrawn
    FROM engineers e
    LEFT JOIN engineer_wallet ew          ON ew.engineer_id  = e.id
    LEFT JOIN engineer_wallet_transactions ewt ON ewt.engineer_id = e.id
    WHERE e.is_active = 1
    GROUP BY e.id
    ORDER BY balance DESC
");
jsonResponse(true, $stmt->fetchAll());

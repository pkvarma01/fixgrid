<?php
// routes/engineer/wallet.php — Engineer wallet balance, transactions, withdraw request
require_once dirname(__DIR__, 2) . '/config.php';
$auth = requireAuth('engineer');
$db   = getDB();
$engineerId = (int)$auth['id'];

// Ensure wallet row exists
$db->prepare("INSERT IGNORE INTO engineer_wallet (engineer_id, balance) VALUES (?,0)")->execute([$engineerId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'withdraw') {
        $amount = (float)($input['amount'] ?? 0);
        if ($amount <= 0) jsonResponse(false, null, 'Enter a valid amount', 422);

        $balStmt = $db->prepare("SELECT balance FROM engineer_wallet WHERE engineer_id=?");
        $balStmt->execute([$engineerId]);
        $balance = (float)$balStmt->fetchColumn();

        if ($amount > $balance) {
            jsonResponse(false, null, 'Insufficient balance. Available: ₹'.number_format($balance,2), 422);
        }

        $db->prepare("UPDATE engineer_wallet SET balance=balance-? WHERE engineer_id=?")->execute([$amount, $engineerId]);
        $db->prepare("INSERT INTO engineer_wallet_transactions (engineer_id,type,amount,description) VALUES (?,?,?,?)")
           ->execute([$engineerId, 'withdrawal', $amount, 'Withdrawal request']);

        jsonResponse(true, ['amount' => $amount], 'Withdrawal of ₹'.number_format($amount,2).' requested');
    }

    jsonResponse(false, null, 'Invalid action', 422);
}

// GET
$walletStmt = $db->prepare("
    SELECT ew.balance,
        COALESCE(SUM(CASE WHEN ewt.type='credit'     THEN ewt.amount ELSE 0 END),0) AS total_earned,
        COALESCE(SUM(CASE WHEN ewt.type='withdrawal' THEN ewt.amount ELSE 0 END),0) AS total_withdrawn
    FROM engineer_wallet ew
    LEFT JOIN engineer_wallet_transactions ewt ON ewt.engineer_id = ew.engineer_id
    WHERE ew.engineer_id = ?
");
$walletStmt->execute([$engineerId]);
$wallet = $walletStmt->fetch();

$txns = $db->prepare("
    SELECT ewt.*, j.job_number
    FROM engineer_wallet_transactions ewt
    LEFT JOIN jobs j ON ewt.job_id = j.id
    WHERE ewt.engineer_id = ?
    ORDER BY ewt.created_at DESC LIMIT 30
");
$txns->execute([$engineerId]);

jsonResponse(true, [
    'wallet'       => $wallet,
    'transactions' => $txns->fetchAll(),
]);

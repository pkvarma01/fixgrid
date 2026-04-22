<?php
// routes/customer/wallet.php
// FIX: wallet_transactions uses ref_id (not job_id), no 'reference' column
//      referrals table has no 'status' column - uses reward_given tinyint
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();
$customerId = (int)$customer['id'];

// Ensure wallet row exists
$db->prepare("INSERT IGNORE INTO customer_wallet (customer_id, balance) VALUES (?,0)")->execute([$customerId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'topup') {
        $amount = (float)($input['amount'] ?? 0);
        if ($amount < 1) jsonResponse(false, null, 'Minimum top-up is ₹1', 422);
        $db->prepare("UPDATE customer_wallet SET balance=balance+? WHERE customer_id=?")->execute([$amount,$customerId]);
        // FIX: use ref_id (not job_id/reference) matching actual DB schema
        $db->prepare("INSERT INTO wallet_transactions (customer_id,type,amount,description) VALUES (?,?,?,?)")
           ->execute([$customerId,'credit',$amount,'Wallet top-up']);
        jsonResponse(true, ['amount'=>$amount], 'Wallet topped up by ₹'.number_format($amount,2));
    }

    jsonResponse(false, null, 'Invalid action', 422);
}

// GET
$walletStmt = $db->prepare("SELECT * FROM customer_wallet WHERE customer_id=?");
$walletStmt->execute([$customerId]);
$walletData = $walletStmt->fetch();

// FIX: join on ref_id=jobs.id (correct column name) — no 'reference' column
$txns = $db->prepare("
    SELECT wt.*, j.job_number
    FROM wallet_transactions wt
    LEFT JOIN jobs j ON wt.ref_id = j.id
    WHERE wt.customer_id = ?
    ORDER BY wt.created_at DESC
    LIMIT 30
");
$txns->execute([$customerId]);

// FIX: referrals table uses reward_given (tinyint), not status='rewarded'
$referralCount = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id=? AND reward_given=1");
$referralCount->execute([$customerId]);

jsonResponse(true, [
    'wallet'         => $walletData,
    'transactions'   => $txns->fetchAll(),
    'referrals_done' => (int)$referralCount->fetchColumn(),
]);

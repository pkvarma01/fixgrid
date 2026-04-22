<?php
// routes/admin/kyc_review.php — Admin reviews and approves/rejects engineer KYC
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin');
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List engineers pending KYC review
    $status = trim($input['status'] ?? 'submitted');
    $stmt = $db->prepare("
        SELECT id, name, phone, email, city, kyc_status,
               kyc_aadhaar_number, kyc_aadhaar_masked, kyc_aadhaar_doc_url,
               kyc_aadhaar_verified, kyc_aadhaar_name, kyc_aadhaar_dob, kyc_aadhaar_gender,
               kyc_pan_number, kyc_pan_doc_url, kyc_pan_verified, kyc_pan_name,
               kyc_selfie_url,
               kyc_rejection_reason, kyc_reviewed_at, created_at
        FROM engineers
        WHERE kyc_status = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$status]);
    jsonResponse(true, $stmt->fetchAll());
}

if ($method === 'POST') {
    $engId  = (int)($input['engineer_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // approve | reject
    $reason = trim($input['reason'] ?? '');

    if (!$engId || !in_array($action, ['approve', 'reject']))
        jsonResponse(false, null, 'engineer_id and action (approve/reject) required', 422);

    $eng = $db->query("SELECT * FROM engineers WHERE id=$engId")->fetch();
    if (!$eng) jsonResponse(false, null, 'Engineer not found', 404);

    if ($action === 'approve') {
        $db->prepare("UPDATE engineers SET
            kyc_status='approved', is_active=1,
            kyc_reviewed_at=NOW(), kyc_rejection_reason=NULL
            WHERE id=?")
           ->execute([$engId]);

        sendPushNotification($eng['device_token'] ?? '',
            '✅ Account Approved!',
            'Congratulations ' . $eng['name'] . '! Your Hridya Tech engineer account is now active. Log in and start taking jobs!',
            ['type' => 'kyc_approved']);
        logNotification($engId, 'engineer', '✅ Account Approved!',
            'Your KYC has been verified. Your account is now active!', ['type' => 'kyc_approved']);

        jsonResponse(true, null, 'Engineer approved and activated');
    }

    if ($action === 'reject') {
        if (!$reason) jsonResponse(false, null, 'Rejection reason is required', 422);
        $db->prepare("UPDATE engineers SET
            kyc_status='rejected', is_active=0,
            kyc_rejection_reason=?, kyc_reviewed_at=NOW()
            WHERE id=?")
           ->execute([$reason, $engId]);

        sendPushNotification($eng['device_token'] ?? '',
            '❌ KYC Verification Issue',
            'Your application was not approved: ' . $reason . '. Please re-register with correct documents.',
            ['type' => 'kyc_rejected']);

        jsonResponse(true, null, 'Engineer application rejected');
    }
}

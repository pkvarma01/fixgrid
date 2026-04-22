<?php
// routes/customer/profile.php — Get and update customer profile
require_once dirname(__DIR__, 2) . '/config.php';
$customer = requireAuth('customer');
$db = getDB();
$customerId = (int)$customer['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sets   = [];
    $params = [];

    // Updatable fields
    $name    = trim($input['name']    ?? '');
    $email   = trim($input['email']   ?? '');
    $address = trim($input['address'] ?? '');

    if ($name)    { $sets[] = 'name=?';    $params[] = $name; }
    if ($email)   { $sets[] = 'email=?';   $params[] = $email; }
    if ($address) { $sets[] = 'address=?'; $params[] = $address; }

    // Profile photo upload
    if (!empty($_FILES['photo']['tmp_name'])) {
        $photoUrl = uploadFile($_FILES['photo'], 'customers');
        if ($photoUrl) { $sets[] = 'profile_photo=?'; $params[] = $photoUrl; }
    }

    if (empty($sets)) jsonResponse(false, null, 'No fields to update', 422);

    $params[] = $customerId;
    $db->prepare('UPDATE customers SET ' . implode(',', $sets) . ', updated_at=NOW() WHERE id=?')
       ->execute($params);

    // Return updated profile
    $stmt = $db->prepare('SELECT id,name,phone,email,address,profile_photo,created_at FROM customers WHERE id=?');
    $stmt->execute([$customerId]);
    jsonResponse(true, $stmt->fetch(), 'Profile updated successfully');
}

// GET — return profile
$stmt = $db->prepare('SELECT id,name,phone,email,address,profile_photo,created_at FROM customers WHERE id=?');
$stmt->execute([$customerId]);
$profile = $stmt->fetch();

// Job stats
$stats = $db->prepare("SELECT COUNT(*) AS total_jobs, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed FROM jobs WHERE customer_id=?");
$stats->execute([$customerId]);

jsonResponse(true, array_merge($profile, $stats->fetch()));

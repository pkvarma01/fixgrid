<?php // routes/admin/customers.php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin'); $db = getDB();
$stmt = $db->query("SELECT c.id,c.name,c.phone,c.email,c.address,c.created_at,(SELECT COUNT(*) FROM jobs WHERE customer_id=c.id) AS total_jobs FROM customers c WHERE c.is_active=1 ORDER BY c.created_at DESC LIMIT 200");
jsonResponse(true,$stmt->fetchAll());

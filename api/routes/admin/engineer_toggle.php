<?php // routes/admin/engineer_toggle.php
require_once dirname(__DIR__, 2) . '/config.php';
requireAuth('admin'); $db = getDB();
$id = (int)($input['id'] ?? 0); if (!$id) jsonResponse(false,null,'id required',422);
$db->prepare('UPDATE engineers SET is_active = 1-is_active WHERE id=?')->execute([$id]);
jsonResponse(true,null,'Engineer status toggled');

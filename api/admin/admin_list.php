<?php
require_once '../config.php';
check_admin_auth();

$stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY id DESC");
$admins = $stmt->fetchAll();

response($admins);
?>

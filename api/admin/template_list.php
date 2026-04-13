<?php
require_once '../config.php';
check_admin_auth();

$stmt = $pdo->query("SELECT * FROM product_templates ORDER BY id DESC");
$templates = $stmt->fetchAll();

write_log('查看模板', '管理员查看了产品模板列表');

response($templates);
?>

<?php
require_once '../config.php';
check_admin_auth();

$stmt = $pdo->query("SELECT * FROM product_types ORDER BY id DESC");
$types = $stmt->fetchAll();

response($types);
?>

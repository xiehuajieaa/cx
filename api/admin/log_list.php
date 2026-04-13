<?php
require_once '../config.php';
check_admin_auth();

// 获取配置
$stmt = $pdo->query("SELECT * FROM system_config");
$configs = [];
while ($row = $stmt->fetch()) {
    $configs[$row['config_key']] = $row['config_value'];
}

// 获取日志列表 (分页)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM logs ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

response([
    'configs' => $configs,
    'logs' => $logs,
    'total' => (int)$total,
    'page' => $page,
    'limit' => $limit
]);
?>

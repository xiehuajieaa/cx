<?php
require_once '../config.php';
check_admin_auth();

// 获取分页、筛选、搜索参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$type = clean_input(isset($_GET['type']) ? $_GET['type'] : '');
$search = clean_input(isset($_GET['search']) ? $_GET['search'] : '');

if ($page < 1) $page = 1;
if ($limit < 1) $limit = 10;

// 构建 WHERE 子句
$where = [];
$params = [];

if ($type !== '') {
    $where[] = "product_type = :type";
    $params[':type'] = $type;
}

if ($search !== '') {
    $where[] = "(product_name LIKE :search OR product_model LIKE :search OR sn LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// 获取总数
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();

// 获取分页数据
$offset = ($page - 1) * $limit;
$stmt = $pdo->prepare("SELECT * FROM products $where_sql ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll();

// 补全图片路径
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . $host . str_replace('api/admin/', '', $baseDir);

foreach ($products as &$p) {
    if ($p['image'] && !preg_match('/^http/', $p['image'])) {
        $p['image_url'] = $baseUrl . $p['image'];
    } else {
        $p['image_url'] = $p['image'];
    }
}

response([
    'total' => (int)$total,
    'page' => $page,
    'limit' => $limit,
    'products' => $products
]);
?>

<?php
require_once '../config.php';
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    response(['error' => '无效的请求数据'], 400);
}

$id = $input['id'];

try {
    // 检查是否有产品使用此类型
    $stmt = $pdo->prepare("SELECT type_name FROM product_types WHERE id = ?");
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    
    if ($type) {
        $pStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_type = ?");
        $pStmt->execute([$type['type_name']]);
        if ($pStmt->fetchColumn() > 0) {
            response(['error' => '该产品类型下已有产品，无法删除'], 400);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM product_types WHERE id = ?");
    $stmt->execute([$id]);
    
    write_log('删除产品类型', "类型ID: $id" . ($type ? ", 类型名称: " . $type['type_name'] : ""));
    
    response(['message' => '产品类型删除成功']);
} catch (PDOException $e) {
    response(['error' => '删除失败: ' . $e->getMessage()], 500);
}
?>

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
    $stmt = $pdo->prepare("DELETE FROM product_templates WHERE id = ?");
    $stmt->execute([$id]);
    
    write_log('删除产品模板', "模板ID: $id");
    
    response(['message' => '模板删除成功']);
} catch (PDOException $e) {
    response(['error' => '删除失败: ' . $e->getMessage()], 500);
}
?>

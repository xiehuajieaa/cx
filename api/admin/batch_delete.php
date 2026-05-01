<?php
require_once '../config.php';
check_admin_auth();

$data = json_decode(file_get_contents('php://input'), true);
$ids = isset($data['ids']) ? $data['ids'] : [];

if (empty($ids) || !is_array($ids)) {
    response(['error' => '请选择要删除的产品'], 400);
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    write_log('批量删除产品', "成功删除 " . count($ids) . " 条产品数据");
    
    response(['success' => true, 'count' => $stmt->rowCount()]);
} catch (PDOException $e) {
    response(['error' => '批量删除失败: ' . $e->getMessage()], 500);
}
?>

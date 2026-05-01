<?php
require_once '../config.php';
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$id = $input['id'] ?? '';
$type_name = $input['type_name'] ?? '';
$sn_prefix = strtoupper($input['sn_prefix'] ?? '');

if (empty($id) || empty($type_name) || empty($sn_prefix)) {
    response(['error' => '请填写完整信息'], 400);
}

try {
    $stmt = $pdo->prepare("UPDATE product_types SET type_name=?, sn_prefix=? WHERE id=?");
    $stmt->execute([$type_name, $sn_prefix, $id]);
    
    write_log('修改产品类型', "类型ID: $id, 新名称: $type_name, 新前缀: $sn_prefix");
    
    response(['message' => '产品类型修改成功']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        response(['error' => '类型名称或前缀已存在'], 400);
    }
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

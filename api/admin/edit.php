<?php
require_once '../config.php';
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$input = clean_input($input);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$id = $input['id'] ?? '';
$sn_code = $input['sn_code'] ?? '';
$product_type = $input['product_type'] ?? '';
$product_name = $input['product_name'] ?? '';
$product_model = $input['product_model'] ?? '';
$manufacturing_date = $input['manufacturing_date'] ?? '';
$warranty_months = intval($input['warranty_months'] ?? 12);
$sales_channel = $input['sales_channel'] ?? '';
$manual_link = $input['manual_link'] ?? '';
$image = $input['image'] ?? '';
$remarks = $input['remarks'] ?? '';

if (empty($id) || empty($product_name) || empty($product_model) || empty($manufacturing_date)) {
    response(['error' => '请填写必要信息'], 400);
}

// 计算过期日期
$m_date = new DateTime($manufacturing_date);
$m_date->modify("+$warranty_months months");
$expiry_date = $m_date->format('Y-m-d');

try {
    $stmt = $pdo->prepare("UPDATE products SET sn_code=?, product_type=?, product_name=?, product_model=?, manufacturing_date=?, warranty_months=?, expiry_date=?, sales_channel=?, manual_link=?, image=?, remarks=? WHERE id=?");
    $stmt->execute([
        $sn_code,
        $product_type,
        $product_name,
        $product_model,
        $manufacturing_date,
        $warranty_months,
        $expiry_date,
        $sales_channel,
        $manual_link,
        $image,
        $remarks,
        $id
    ]);
    
    write_log('修改产品', "产品ID: $id, 产品名称: $product_name");
    
    response(['message' => '修改成功']);
} catch (PDOException $e) {
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

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

$template_name = $input['template_name'] ?? '';
$product_type = $input['product_type'] ?? '';
$product_name = $input['product_name'] ?? '';
$product_model = $input['product_model'] ?? '';
$warranty_months = intval($input['warranty_months'] ?? 12);
$sales_channel = $input['sales_channel'] ?? '';
$manual_link = $input['manual_link'] ?? '';
$image = $input['image'] ?? '';
$remarks = $input['remarks'] ?? '';

if (empty($template_name) || empty($product_name)) {
    response(['error' => '请填写模板名称和产品名称'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO product_templates (template_name, product_type, product_name, product_model, warranty_months, sales_channel, manual_link, image, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $template_name,
        $product_type,
        $product_name,
        $product_model,
        $warranty_months,
        $sales_channel,
        $manual_link,
        $image,
        $remarks
    ]);
    
    write_log('添加产品模板', "模板名称: $template_name, 产品: $product_name");
    
    response(['message' => '模板保存成功']);
} catch (PDOException $e) {
    response(['error' => '保存模板失败: ' . $e->getMessage()], 500);
}
?>

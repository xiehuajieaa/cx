<?php
require_once '../config.php';
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

// 接收 JSON 数据
$input = json_decode(file_get_contents('php://input'), true);
$input = clean_input($input);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$product_type = $input['product_type'] ?? '';
$sn_code = $input['sn_code'] ?? '';
$product_name = $input['product_name'] ?? '';
$product_model = $input['product_model'] ?? '';
$manufacturing_date = $input['manufacturing_date'] ?? '';
$warranty_months = intval($input['warranty_months'] ?? 12);
$sales_channel = $input['sales_channel'] ?? '';
$manual_link = $input['manual_link'] ?? '';
$image = $input['image'] ?? '';
$remarks = $input['remarks'] ?? '';

if (empty($product_type) || empty($product_name) || empty($product_model) || empty($manufacturing_date)) {
    response(['error' => '请填写必要信息'], 400);
}

// 获取产品类型对应的前缀
$stmt = $pdo->prepare("SELECT sn_prefix FROM product_types WHERE type_name = ?");
$stmt->execute([$product_type]);
$type_info = $stmt->fetch();
$prefix = $type_info ? $type_info['sn_prefix'] : 'OTH';

// 生成唯一的序列号
do {
    $timestamp = date('Ymd');
    $random = strtoupper(substr(uniqid(), -4));
    $sn = $prefix . '-' . $timestamp . '-' . $random;
    
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE sn = ?");
    $check_stmt->execute([$sn]);
} while ($check_stmt->fetch());

// 计算过期日期
$m_date = new DateTime($manufacturing_date);
$m_date->modify("+$warranty_months months");
$expiry_date = $m_date->format('Y-m-d');

try {
    $stmt = $pdo->prepare("INSERT INTO products (sn, sn_code, product_type, product_name, product_model, manufacturing_date, warranty_months, expiry_date, sales_channel, manual_link, image, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $sn,
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
        $remarks
    ]);
    
    write_log('添加产品', "产品名称: $product_name, SN: $sn");
    
    response(['message' => '添加成功', 'sn' => $sn]);
} catch (PDOException $e) {
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

<?php
require_once '../config.php';
check_admin_auth();

$data = json_decode(file_get_contents('php://input'), true);
$data = clean_input($data);
$products = isset($data['products']) ? $data['products'] : [];

if (empty($products) || !is_array($products)) {
    response(['error' => '请提供有效的产品数据列表'], 400);
}

// 获取所有产品类型及其前缀
$stmt = $pdo->query("SELECT type_name, sn_prefix FROM product_types");
$types = [];
while ($row = $stmt->fetch()) {
    $types[$row['type_name']] = $row['sn_prefix'];
}

$results = [];
$errors = [];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO products (sn, sn_code, product_type, product_name, product_model, manufacturing_date, warranty_months, expiry_date, sales_channel, manual_link, image, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($products as $index => $p) {
        $product_type = $p['product_type'] ?? '';
        $sn_code = $p['sn_code'] ?? '';
        $product_name = $p['product_name'] ?? '';
        $product_model = $p['product_model'] ?? '';
        $manufacturing_date = $p['manufacturing_date'] ?? date('Y-m-d');
        $warranty_months = intval($p['warranty_months'] ?? 12);
        $sales_channel = $p['sales_channel'] ?? '';
        $manual_link = $p['manual_link'] ?? '';
        $image = $p['image'] ?? '';
        $remarks = $p['remarks'] ?? '';

        if (empty($product_type) || empty($product_name) || empty($product_model)) {
            $errors[] = "第 " . ($index + 1) . " 条数据缺少必要信息 (产品名称/型号/类型)";
            continue;
        }

        $prefix = $types[$product_type] ?? 'OTH';
        
        // 生成序列号
        $timestamp = date('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        $sn = $prefix . '-' . $timestamp . '-' . $random;

        // 计算过期日期
        $m_date = new DateTime($manufacturing_date);
        $m_date->modify("+$warranty_months months");
        $expiry_date = $m_date->format('Y-m-d');

        $stmt->execute([
            $sn, $sn_code, $product_type, $product_name, $product_model, 
            $manufacturing_date, $warranty_months, $expiry_date, 
            $sales_channel, $manual_link, $image, $remarks
        ]);
    }
    
    if (!empty($errors)) {
        $pdo->rollBack();
        response(['error' => '部分数据有误，导入已取消', 'details' => $errors], 400);
    }
    
    $pdo->commit();
    
    write_log('批量添加产品', "成功导入 " . count($products) . " 条产品数据");
    
    response(['success' => true, 'count' => count($products)]);
} catch (Exception $e) {
    $pdo->rollBack();
    response(['error' => '批量添加失败: ' . $e->getMessage()], 500);
}
?>

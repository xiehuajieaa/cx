<?php
require_once 'config.php';

// 仅允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['success' => false, 'error' => '仅支持 POST 请求'], 405);
}

// 禁用 CDN/浏览器缓存
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// 检查工厂模式是否被系统设置启用
try {
    $cfgStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'factory_api_enabled'");
    $cfg = $cfgStmt->fetch();
    $factoryApiEnabled = $cfg ? $cfg['config_value'] : '0';
} catch (Exception $e) {
    $factoryApiEnabled = '0';
}

if ($factoryApiEnabled !== '1') {
    response(['success' => false, 'error' => '工厂模式已被管理员关闭'], 403);
}

// 校验访问口令（token）
try {
    $tokStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'factory_api_token'");
    $tokRow = $tokStmt->fetch();
    $storedToken = $tokRow ? $tokRow['config_value'] : '';
} catch (Exception $e) {
    $storedToken = '';
}

if (!empty($storedToken)) {
    $clientToken = clean_input($_GET['token'] ?? '');
    if ($clientToken !== $storedToken) {
        response(['success' => false, 'error' => '访问口令无效或缺失'], 401);
    }
}

// 读取 JSON 请求体
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    response(['success' => false, 'error' => '请求体必须为 JSON 格式'], 400);
}

// 必填字段校验
$sn = clean_input($input['sn'] ?? '');
$productType = clean_input($input['product_type'] ?? '');
$productName = clean_input($input['product_name'] ?? '');
$productModel = clean_input($input['product_model'] ?? '');

if (empty($sn) || empty($productType) || empty($productName) || empty($productModel)) {
    $missing = [];
    if (empty($sn)) $missing[] = 'sn';
    if (empty($productType)) $missing[] = 'product_type';
    if (empty($productName)) $missing[] = 'product_name';
    if (empty($productModel)) $missing[] = 'product_model';
    response(['success' => false, 'error' => '缺少必填字段: ' . implode(', ', $missing)], 200);
}

// 检查 SN 是否已存在
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sn = ?");
$checkStmt->execute([$sn]);
if ($checkStmt->fetchColumn() > 0) {
    response(['success' => false, 'error' => '序列号 "' . $sn . '" 已存在，不可重复'], 200);
}

// 可选字段
$snCode = clean_input($input['sn_code'] ?? '');
$salesChannel = clean_input($input['sales_channel'] ?? '');
$manualLink = clean_input($input['manual_link'] ?? '');
$remarks = clean_input($input['remarks'] ?? '');
$image = clean_input($input['image'] ?? '');

// 写入数据库
try {
    $stmt = $pdo->prepare("INSERT INTO products (sn, sn_code, product_type, product_name, product_model, sales_channel, manual_link, remarks, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sn, $snCode, $productType, $productName, $productModel, $salesChannel, $manualLink, $remarks, $image]);
    $newId = $pdo->lastInsertId();

    response([
        'success' => true,
        'id' => (int)$newId,
        'sn' => $sn,
        'message' => '产品已保存'
    ]);
} catch (Exception $e) {
    response(['success' => false, 'error' => '保存失败: ' . $e->getMessage()], 500);
}
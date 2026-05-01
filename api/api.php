<?php
require_once 'config.php';

// 仅允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

// 禁用 CDN/浏览器缓存
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// 检查 POST 接口是否被系统设置启用
try {
    $cfgStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'post_api_enabled'");
    $cfg = $cfgStmt->fetch();
    $postApiEnabled = $cfg ? $cfg['config_value'] : '1';
} catch (Exception $e) {
    $postApiEnabled = '1';
}

if ($postApiEnabled !== '1') {
    response(['error' => 'POST 查询接口已被管理员关闭'], 403);
}

// 校验访问口令（token）
try {
    $tokStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'api_token'");
    $tokRow = $tokStmt->fetch();
    $storedToken = $tokRow ? $tokRow['config_value'] : '';
} catch (Exception $e) {
    $storedToken = '';
}

if (!empty($storedToken)) {
    $clientToken = clean_input($_GET['token'] ?? '');
    if ($clientToken !== $storedToken) {
        response(['error' => '访问口令无效或缺失'], 401);
    }
}

// 从 URL 参数获取 sn
$sn = clean_input($_GET['sn'] ?? '');

if (empty($sn)) {
    response(['error' => '请输入序列号，格式: ?sn=AAAA-BBBB-CCCC&token=口令'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE sn = ?");
$stmt->execute([$sn]);
$product = $stmt->fetch();

if ($product) {
    // 查询次数 +1（兼容旧表无此字段的情况）
    try {
        $upd = $pdo->prepare("UPDATE products SET query_count = query_count + 1 WHERE id = ?");
        $upd->execute([$product['id']]);
        $product['query_count'] = $product['query_count'] + 1;
    } catch (Exception $e) {
        $product['query_count'] = ($product['query_count'] ?? 0) + 1;
    }

    // POST 不返回图片原始路径
    unset($product['image']);
    response($product);
} else {
    response(['valid' => false, 'error' => '序列号不存在或非正品']);
}
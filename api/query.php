<?php
require_once 'config.php';

// 禁用 CDN/浏览器缓存，确保每次查询返回实时数据
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// ========== 仅 GET 请求：内部前端页面查询 ==========
$sn = clean_input($_GET['sn'] ?? '');

if (empty($sn)) {
    response(['error' => '请输入序列号'], 400);
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
        // query_count 字段可能不存在，忽略异常，前端仍可正常显示
        $product['query_count'] = ($product['query_count'] ?? 0) + 1;
    }

    // 补全图片访问 URL
    if ($product['image'] && !preg_match('/^http/', $product['image'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        $product['image_url'] = $protocol . $host . str_replace('api/', '', $baseDir) . $product['image'];
    } else {
        $product['image_url'] = $product['image'];
    }
    unset($product['image']);
    response($product);
} else {
    response(['error' => '您输入的序列号不正确或非正品'], 404);
}
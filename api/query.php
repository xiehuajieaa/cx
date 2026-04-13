<?php
require_once 'config.php';

$sn = clean_input($_GET['sn'] ?? '');

if (empty($sn)) {
    response(['error' => '请输入序列号'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE sn = ?");
$stmt->execute([$sn]);
$product = $stmt->fetch();

if ($product) {
    // 补全图片路径
    if ($product['image'] && !preg_match('/^http/', $product['image'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        // 假设图片在 uploads 目录下，API 在 api 目录下
        $product['image_url'] = $protocol . $host . str_replace('api/', '', $baseDir) . $product['image'];
    } else {
        $product['image_url'] = $product['image'];
    }
    response($product);
} else {
    response(['error' => '您输入的序列号不正确或非正品'], 404);
}
?>

<?php
require_once '../config.php';
check_admin_auth();

try {
    global $pdo;
    
    // 产品总数
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = (int)$stmt->fetchColumn();
    
    // 产品类型数
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_types");
    $total_types = (int)$stmt->fetchColumn();
    
    // 模板数
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_templates");
    $total_templates = (int)$stmt->fetchColumn();
    
    // 图片资源数 (uploads 目录下文件数，排除 placeholder.txt)
    $upload_dir = dirname(__DIR__) . '/uploads';
    $total_images = 0;
    if (is_dir($upload_dir)) {
        $files = array_diff(scandir($upload_dir), ['.', '..', 'placeholder.txt']);
        $total_images = count($files);
    }
    
    response([
        'total_products' => $total_products,
        'total_types' => $total_types,
        'total_templates' => $total_templates,
        'total_images' => $total_images
    ]);
} catch (Exception $e) {
    response(['error' => '获取统计数据失败: ' . $e->getMessage()], 500);
}
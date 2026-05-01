<?php
require_once '../config.php';
check_admin_auth();

try {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        response(['error' => '不支持的请求方法'], 405);
        return;
    }
    
    // 获取序列号生成规则
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('sn_groups', 'sn_chars_per_group')");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['config_key']] = $row['config_value'];
    }
    
    $groups = (int)($settings['sn_groups'] ?? 3);
    $chars_per_group = (int)($settings['sn_chars_per_group'] ?? 4);
    
    // 限制范围
    $groups = max(1, min(10, $groups));
    $chars_per_group = max(2, min(10, $chars_per_group));
    
    // 获取所有产品
    $stmt = $pdo->query("SELECT id FROM products");
    $products = $stmt->fetchAll();
    
    if (count($products) === 0) {
        response(['success' => true, 'message' => '没有产品需要更新', 'updated' => 0]);
        return;
    }
    
    // 生成随机序列号的字符集（大写字母 + 数字，排除容易混淆的字符）
    $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    
    $updated = 0;
    $used_sns = []; // 避免生成重复的序列号
    
    // 首先获取所有已存在的序列号
    $stmt = $pdo->query("SELECT sn FROM products");
    $existing_sns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $pdo->beginTransaction();
    
    foreach ($products as $product) {
        $attempts = 0;
        do {
            // 生成随机序列号
            $parts = [];
            for ($g = 0; $g < $groups; $g++) {
                $part = '';
                for ($c = 0; $c < $chars_per_group; $c++) {
                    $part .= $charset[random_int(0, strlen($charset) - 1)];
                }
                $parts[] = $part;
            }
            $new_sn = implode('-', $parts);
            $attempts++;
            
            // 最多尝试50次避免死循环
            if ($attempts > 50) {
                break;
            }
        } while (in_array($new_sn, $existing_sns) || isset($used_sns[$new_sn]));
        
        $used_sns[$new_sn] = true;
        
        if ($new_sn) {
            $stmt = $pdo->prepare("UPDATE products SET sn = ? WHERE id = ?");
            $stmt->execute([$new_sn, $product['id']]);
            $updated++;
        }
    }
    
    $pdo->commit();
    
    write_log('refresh_sn', "刷新了 {$updated} 个产品的序列号，规则：{$groups}组×{$chars_per_group}字符");
    response([
        'success' => true,
        'message' => "已更新 {$updated} 个产品的序列号",
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    response(['error' => '刷新序列号失败: ' . $e->getMessage()], 500);
}
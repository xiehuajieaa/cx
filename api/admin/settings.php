<?php
require_once '../config.php';
check_admin_auth();

try {
    global $pdo;
    
    // GET：获取所有设置
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('site_name', 'icp_no', 'gongan_no', 'copyright_text', 'sn_groups', 'sn_chars_per_group', 'post_api_enabled', 'api_token', 'factory_api_enabled', 'factory_api_token')");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['config_key']] = $row['config_value'];
        }
        
        // 确保所有字段都有默认值
        $defaults = [
            'site_name' => '产品后台管理平台',
            'icp_no' => '',
            'gongan_no' => '',
            'copyright_text' => '© 2026 正版查询中心. 保留所有权利.',
            'sn_groups' => '3',
            'sn_chars_per_group' => '4',
            'post_api_enabled' => '1',
            'api_token' => '',
            'factory_api_enabled' => '0',
            'factory_api_token' => ''
        ];
        
        foreach ($defaults as $key => $default) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default;
            }
        }
        
        response($settings);
        return;
    }
    
    // POST：保存设置
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            response(['error' => '无效的请求数据'], 400);
            return;
        }
        
        $allowed_keys = ['site_name', 'icp_no', 'gongan_no', 'copyright_text', 'sn_groups', 'sn_chars_per_group', 'post_api_enabled', 'api_token', 'factory_api_enabled', 'factory_api_token'];
        
        foreach ($allowed_keys as $key) {
            if (isset($input[$key])) {
                $value = clean_input($input[$key]);
                // 验证序列号参数
                if ($key === 'sn_groups') {
                    $value = (string)max(1, min(10, (int)$value));
                }
                if ($key === 'sn_chars_per_group') {
                    $value = (string)max(2, min(10, (int)$value));
                }
                
                $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $stmt->execute([$key, $value]);
            }
        }
        
        write_log('save_settings', '保存了系统设置');
        response(['success' => true, 'message' => '设置保存成功']);
        return;
    }
    
    response(['error' => '不支持的请求方法'], 405);
    
} catch (Exception $e) {
    response(['error' => '操作失败: ' . $e->getMessage()], 500);
}
<?php
require_once 'config.php';

try {
        $stmt = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('site_name', 'icp_no', 'gongan_no', 'copyright_text', 'post_api_enabled')");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
        response([
            'site_name' => $config['site_name'] ?? '正版查询系统',
            'icp_no' => $config['icp_no'] ?? '',
            'gongan_no' => $config['gongan_no'] ?? '',
            'copyright_text' => $config['copyright_text'] ?? '© 2026 正版查询中心. 保留所有权利.',
            'post_api_enabled' => $config['post_api_enabled'] ?? '1'
        ]);
} catch (Exception $e) {
        response([
            'site_name' => '正版查询系统',
            'icp_no' => '',
            'gongan_no' => '',
            'copyright_text' => '© 2026 正版查询中心. 保留所有权利.',
            'post_api_enabled' => '1'
        ]);
}
?>
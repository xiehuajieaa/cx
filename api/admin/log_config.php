<?php
require_once '../config.php';
check_admin_auth();

// 只有主管理员 admin 才能操作
if ($_SESSION['admin_username'] !== 'admin') {
    response(['error' => '权限不足，只有主管理员可以操作'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

try {
    $pdo->beginTransaction();
    
    if (isset($input['log_enabled'])) {
        $stmt = $pdo->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'log_enabled'");
        $stmt->execute([$input['log_enabled'] ? '1' : '0']);
    }
    
    if (isset($input['log_retention_days'])) {
        $stmt = $pdo->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'log_retention_days'");
        $stmt->execute([$input['log_retention_days']]);
    }
    
    if (isset($input['clear_logs']) && $input['clear_logs'] === true) {
        $pdo->exec("TRUNCATE TABLE logs");
        write_log('清空日志', '管理员清空了所有系统日志');
    } else {
        write_log('修改配置', '修改了系统日志配置');
    }
    
    $pdo->commit();
    response(['message' => '配置已更新']);
} catch (Exception $e) {
    $pdo->rollBack();
    response(['error' => '更新失败: ' . $e->getMessage()], 500);
}
?>

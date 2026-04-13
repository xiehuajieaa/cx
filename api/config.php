<?php
// 关闭错误显示，防止干扰 JSON 输出
error_reporting(0);
ini_set('display_errors', 0);

// 开启 Session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// 设置安全响应头
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// 数据库配置
$host = 'localhost';
$dbname = 'cs';
$user = 'cs';
$pass = 'cs'; // 请根据实际情况修改

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(['error' => '数据库连接失败: ' . $e->getMessage()]));
}

// 辅助函数: 统一返回 JSON
function response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 输入清理函数 (防止 XSS 和基础 SQL 注入尝试)
function clean_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = clean_input($value);
        }
    } else {
        $data = trim($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// 后台鉴权函数
function check_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        response(['error' => '未登录或登录已过期'], 401);
    }
    
    // 简单的 CSRF 防护: 检查 Referer 是否来自本站
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if ($referer_host !== $_SERVER['HTTP_HOST']) {
            response(['error' => '非法请求 (CSRF)'], 403);
        }
    }
}

// 记录系统日志
function write_log($action, $details = '') {
    global $pdo;
    
    // 检查日志是否开启
    try {
        $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'log_enabled'");
        $stmt->execute();
        $enabled = $stmt->fetchColumn();
        if ($enabled === '0') return;
        
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $username = $_SESSION['admin_username'] ?? 'System';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO logs (admin_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $username, $action, $details, $ip]);
        
        // 自动清理过期日志 (按配置天数)
        if (rand(1, 100) === 1) { // 1% 的概率执行清理，避免每次都跑
            $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'log_retention_days'");
            $stmt->execute();
            $days = (int)$stmt->fetchColumn();
            if ($days > 0) {
                $pdo->exec("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
            }
        }
    } catch (Exception $e) {
        // 记录失败不影响主流程
    }
}
?>

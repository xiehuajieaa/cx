<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    response(['error' => '请填写用户名和密码'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && $user['password'] === $password) {
    // 登录成功，设置 Session
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    
    write_log('登录', '管理员登录系统');
    
    response(['message' => '登录成功']);
} else {
    write_log('登录失败', "尝试登录用户名: $username");
    response(['error' => '用户名或密码错误'], 401);
}
?>

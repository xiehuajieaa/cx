<?php
require_once '../config.php';
check_admin_auth();

// 只有主管理员 admin 才能添加管理员
if ($_SESSION['admin_username'] !== 'admin') {
    response(['error' => '权限不足，只有主管理员可以添加账号'], 403);
}

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

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);
    
    write_log('添加管理员', "新管理员用户名: $username");
    
    response(['message' => '管理员添加成功']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        response(['error' => '用户名已存在'], 400);
    }
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

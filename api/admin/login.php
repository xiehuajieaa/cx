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

if ($user) {
    $storedPassword = $user['password'];
    $authenticated = false;
    $needsUpgrade = false;

    // 先尝试 bcrypt 验证（$2y$ 格式）
    if (strpos($storedPassword, '$2y$') === 0 || strpos($storedPassword, '$2b$') === 0) {
        $authenticated = password_verify($password, $storedPassword);
    } elseif (strpos($storedPassword, 'sha256$') === 0) {
        // SHA256(密码+盐) 格式，首次导入 SQL 时使用，登录后自动升级为 bcrypt
        $salt = 'ZxQuery_2024_Salt';
        $expected = 'sha256$' . base64_encode(hash('sha256', $password . $salt, true));
        if (hash_equals($expected, $storedPassword)) {
            $authenticated = true;
            $needsUpgrade = true;
        }
    }

    if ($authenticated) {
        // 自动升级明文密码为 bcrypt 哈希
        if ($needsUpgrade) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET password=? WHERE id=?");
            $updateStmt->execute([$newHash, $user['id']]);
        }

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];

        write_log('登录', '管理员登录系统');

        response(['message' => '登录成功']);
    }
}

write_log('登录失败', "尝试登录用户名: $username");
response(['error' => '用户名或密码错误'], 401);
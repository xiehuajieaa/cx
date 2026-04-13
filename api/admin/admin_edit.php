<?php
require_once '../config.php';
check_admin_auth();

// 只有主管理员 admin 才能修改管理员信息
if ($_SESSION['admin_username'] !== 'admin') {
    response(['error' => '权限不足，只有主管理员可以修改账号信息'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$id = $input['id'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($id) || empty($username)) {
    response(['error' => '请填写管理员 ID 和用户名'], 400);
}

try {
    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE admins SET username=?, password=? WHERE id=?");
        $stmt->execute([$username, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET username=? WHERE id=?");
        $stmt->execute([$username, $id]);
    }
    
    write_log('修改管理员', "管理员ID: $id, 用户名修改为: $username");
    
    response(['message' => '管理员修改成功']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        response(['error' => '用户名已存在'], 400);
    }
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

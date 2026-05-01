<?php
require_once '../config.php';
check_admin_auth();

// 只有主管理员 admin 才能删除管理员
if ($_SESSION['admin_username'] !== 'admin') {
    response(['error' => '权限不足，只有主管理员可以删除账号'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    response(['error' => '无效的请求数据'], 400);
}

$id = $input['id'];

try {
    // 检查是否还有其他管理员，防止删除最后一个管理员
    $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($count <= 1) {
        response(['error' => '系统至少需要保留一个管理员账号'], 400);
    }
    
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    
    write_log('删除管理员', "管理员ID: $id");
    
    response(['message' => '管理员删除成功']);
} catch (PDOException $e) {
    response(['error' => '删除失败: ' . $e->getMessage()], 500);
}
?>

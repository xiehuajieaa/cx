<?php
require_once '../config.php';
check_admin_auth();

$data = json_decode(file_get_contents('php://input'), true);
$data = clean_input($data);
$ids = isset($data['ids']) ? $data['ids'] : [];
$fields = isset($data['fields']) ? $data['fields'] : [];

if (empty($ids) || !is_array($ids)) {
    response(['error' => '请选择要修改的产品'], 400);
}

if (empty($fields) || !is_array($fields)) {
    response(['error' => '请提供要修改的字段'], 400);
}

// 允许批量修改的字段
$allowed_fields = [
    'product_type', 'product_name', 'product_model', 
    'sales_channel', 'manual_link', 'remarks', 'image'
];

$update_clauses = [];
$params = [];

foreach ($fields as $key => $value) {
    if (in_array($key, $allowed_fields)) {
        $update_clauses[] = "$key = :$key";
        $params[":$key"] = $value;
    }
}

if (empty($update_clauses)) {
    response(['error' => '没有有效的可修改字段'], 400);
}

try {
    $placeholders = [];
    foreach ($ids as $i => $id) {
        $placeholder = ":id_$i";
        $placeholders[] = $placeholder;
        $params[$placeholder] = $id;
    }
    
    $ids_sql = implode(',', $placeholders);
    $sql = "UPDATE products SET " . implode(', ', $update_clauses) . " WHERE id IN ($ids_sql)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    write_log('批量修改产品', "成功修改 " . count($ids) . " 条产品数据，修改字段: " . implode(', ', array_keys($fields)));
    
    response(['success' => true, 'count' => $stmt->rowCount()]);
} catch (PDOException $e) {
    response(['error' => '批量修改失败: ' . $e->getMessage()], 500);
}
?>

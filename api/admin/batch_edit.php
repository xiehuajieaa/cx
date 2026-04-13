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
    'manufacturing_date', 'warranty_months', 
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

// 如果修改了制造日期或保修月数，需要重新计算过期日期
if (isset($fields['manufacturing_date']) || isset($fields['warranty_months'])) {
    // 这种情况下，简单的 UPDATE 无法直接处理。
    // 为了简化逻辑，我们可以在这里根据传入的值重新计算 expiry_date。
    // 如果只修改了其中一个，我们需要获取另一个值。
    // 这里为了方便，要求如果修改其中之一，最好两个都传，或者我们单独处理。
    // 我们可以在 SQL 层面使用 DATE_ADD。
    
    if (isset($fields['manufacturing_date']) && isset($fields['warranty_months'])) {
        $m_date = new DateTime($fields['manufacturing_date']);
        $m_date->modify("+" . intval($fields['warranty_months']) . " months");
        $expiry_date = $m_date->format('Y-m-d');
        $update_clauses[] = "expiry_date = :expiry_date";
        $params[':expiry_date'] = $expiry_date;
    } else {
        // 复杂情况：根据每个产品的现有值更新过期日期。
        // 这里为了简化，我们在 API 文档中建议批量修改时同时提供这两个字段，或者仅在不涉及过期日期时使用批量修改。
        // 或者使用 SQL 的 INTERVAL 函数。
        if (isset($fields['manufacturing_date'])) {
             $update_clauses[] = "expiry_date = DATE_ADD(manufacturing_date, INTERVAL warranty_months MONTH)";
        } else if (isset($fields['warranty_months'])) {
             $update_clauses[] = "expiry_date = DATE_ADD(manufacturing_date, INTERVAL :warranty_months MONTH)";
        }
    }
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

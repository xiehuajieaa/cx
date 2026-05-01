<?php
require_once '../config.php';
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '仅支持 POST 请求'], 405);
}

// 接收 JSON 数据
$input = json_decode(file_get_contents('php://input'), true);
$input = clean_input($input);

if (!$input) {
    response(['error' => '无效的请求数据'], 400);
}

$product_type = $input['product_type'] ?? '';
$sn_code = $input['sn_code'] ?? '';
$product_name = $input['product_name'] ?? '';
$product_model = $input['product_model'] ?? '';
$sales_channel = $input['sales_channel'] ?? '';
$manual_link = $input['manual_link'] ?? '';
$image = $input['image'] ?? '';
$remarks = $input['remarks'] ?? '';

if (empty($product_type) || empty($product_name) || empty($product_model)) {
    response(['error' => '请填写必要信息'], 400);
}

// 获取产品类型对应的前缀
$stmt = $pdo->prepare("SELECT sn_prefix FROM product_types WHERE type_name = ?");
$stmt->execute([$product_type]);
$type_info = $stmt->fetch();
$prefix = $type_info ? $type_info['sn_prefix'] : 'OTH';

// 从配置中读取序列号生成规则
$stmt_config = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('sn_groups', 'sn_chars_per_group')");
$rule_config = [];
while ($row = $stmt_config->fetch()) {
    $rule_config[$row['config_key']] = $row['config_value'];
}
$sn_groups = max(1, min(10, (int)($rule_config['sn_groups'] ?? 3)));
$sn_chars = max(2, min(10, (int)($rule_config['sn_chars_per_group'] ?? 4)));
$charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

// 生成唯一的序列号（前缀 + 按规则生成的随机码）
do {
    $parts = [];
    for ($g = 0; $g < $sn_groups; $g++) {
        $part = '';
        for ($c = 0; $c < $sn_chars; $c++) {
            $part .= $charset[random_int(0, strlen($charset) - 1)];
        }
        $parts[] = $part;
    }
    $sn = $prefix . '-' . implode('-', $parts);
    
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE sn = ?");
    $check_stmt->execute([$sn]);
} while ($check_stmt->fetch());

try {
    $stmt = $pdo->prepare("INSERT INTO products (sn, sn_code, product_type, product_name, product_model, sales_channel, manual_link, image, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $sn,
        $sn_code,
        $product_type,
        $product_name,
        $product_model,
        $sales_channel,
        $manual_link,
        $image,
        $remarks
    ]);
    
    write_log('添加产品', "产品名称: $product_name, SN: $sn");
    
    response(['message' => '添加成功', 'sn' => $sn]);
} catch (PDOException $e) {
    response(['error' => '保存失败: ' . $e->getMessage()], 500);
}
?>

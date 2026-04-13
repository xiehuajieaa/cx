<?php
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo '{"status":0,"msg":"仅支持POST"}';
    exit;
}

if (!isset($_FILES['file'])) {
    header('Content-Type: application/json');
    echo '{"status":0,"msg":"请选择文件"}';
    exit;
}

// 上传目录（上两级）
$target_dir = '../../uploads/';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$file = $_FILES['file'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$name = uniqid('prod_') . '.' . $ext;
$dest = $target_dir . $name;
$tmp = $file['tmp_name'];

// 先上传保存
move_uploaded_file($tmp, $dest);

// ==============================================
// 安全加入：自动缩放图片（最大宽度800px，不崩500）
// ==============================================
function resize_img($src, $max_w = 800) {
    if (!file_exists($src)) return;
    $info = getimagesize($src);
    if (!$info) return;
    
    $w = $info[0];
    $h = $info[1];
    $t = $info[2];
    
    if ($w <= $max_w) return; // 比限制小就不处理
    
    $new_w = $max_w;
    $new_h = round($h * $new_w / $w);
    
    $img = false;
    switch ($t) {
        case 2: $img = imagecreatefromjpeg($src); break;
        case 3: $img = imagecreatefrompng($src);  break;
        case 1: $img = imagecreatefromgif($src);  break;
    }
    if (!$img) return;
    
    $new_img = imagecreatetruecolor($new_w, $new_h);
    
    // PNG透明处理
    if ($t == 3 || $t == 1) {
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        $transparent = imagecolorallocatealpha($new_img, 255,255,255, 127);
        imagefilledrectangle($new_img, 0,0,$new_w,$new_h,$transparent);
    }
    
    imagecopyresampled($new_img, $img, 0,0,0,0, $new_w,$new_h, $w,$h);
    
    switch ($t) {
        case 2: imagejpeg($new_img, $src, 85); break;
        case 3: imagepng($new_img, $src);      break;
        case 1: imagegif($new_img, $src);      break;
    }
    
    imagedestroy($img);
    imagedestroy($new_img);
}

// 执行缩图
resize_img($dest, 800);

// 返回成功
header('Content-Type: application/json');
echo '{"status":1,"msg":"上传成功","path":"uploads/'.$name.'"}';
exit;
?>
<?php
require_once '../config.php';
check_admin_auth();

$upload_dir = '../../uploads/';
$images = [];

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            $url = $protocol . $host . str_replace('api/admin/gallery.php', '', $_SERVER['SCRIPT_NAME']) . 'uploads/' . $file;
            
            $images[] = [
                'name' => $file,
                'path' => 'uploads/' . $file,
                'url' => $url
            ];
        }
    }
}

response($images);
?>

<?php
require_once '../config.php';

write_log('退出', '管理员退出登录');

session_unset();
session_destroy();

response(['message' => '已成功退出登录']);
?>

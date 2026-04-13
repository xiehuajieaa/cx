<?php
require_once '../config.php';

if (isset($_SESSION['admin_id'])) {
    response([
        'logged_in' => true, 
        'username' => $_SESSION['admin_username']
    ]);
} else {
    response(['logged_in' => false], 401);
}
?>

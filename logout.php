<?php
$portal = $_GET['portal'] ?? 'user';
if ($portal === 'admin') {
    session_name('SESSION_ADMIN');
} else {
    session_name('SESSION_USER');
}
session_start();
$_SESSION = array(); //Xóa toàn bộ session
session_destroy();
header('location:login_form.php');

?>  
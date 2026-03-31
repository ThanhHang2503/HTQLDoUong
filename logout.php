<?php
session_start();
$_SESSION = array(); //Xóa toàn bộ session
session_destroy();
header('location:login_form.php');

?>  
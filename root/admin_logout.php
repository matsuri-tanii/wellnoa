<?php
session_start();
$_SESSION['is_admin_logged_in'] = false;
session_write_close();
header('Location: admin_login.php');
exit;
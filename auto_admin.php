<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
header("Location: admin/manage_products.php");
exit;
?>

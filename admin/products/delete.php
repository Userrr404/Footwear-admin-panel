<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$id = $_GET['id'];
$connection->query("DELETE FROM products WHERE product_id = $id");

header("Location: list.php");
exit();
?>
<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$id = $_GET['order_id'];

$connection->query("DELETE FROM order_items WHERE order_id = $id");
$connection->query("DELETE FROM orders WHERE order_id = $id");

header("Location: list.php");
exit();

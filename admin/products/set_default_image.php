<?php
require_once '../includes/db_connections.php';

$product_id = intval($_GET['pid']);
$image_url = basename($_GET['img']);

$connection->query("UPDATE product_images SET is_default=0 WHERE product_id=$product_id");
$connection->query("UPDATE product_images SET is_default=1 WHERE product_id=$product_id AND image_url='$image_url'");

header("Location: edit.php?id=$product_id");
exit;
?>
<?php
require_once '../includes/db_connections.php';

$product_id = intval($_GET['pid']);
$image_url = basename($_GET['img']);

$connection->query("DELETE FROM product_images WHERE product_id=$product_id AND image_url='$image_url'");

$file = "../uploads/products/$image_url";
if (file_exists($file)) unlink($file);

header("Location: edit.php?id=$product_id");
exit;
?>
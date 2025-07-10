<?php
require_once '../includes/db_connections.php';

$id = intval($_POST['id']);
$field = $_POST['field'];
$value = $_POST['value'];

$allowedFields = ['product_name', 'price', 'stock', 'description'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo "Invalid field";
    exit;
}

$stmt = $connection->prepare("UPDATE products SET $field = ? WHERE product_id = ?");
$stmt->bind_param("si", $value, $id);
if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Error updating";
}
?>

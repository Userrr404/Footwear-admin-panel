<?php
require_once '../includes/db_connections.php';

$id = intval($_POST['id']);
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowedFields = ['product_name', 'description', 'is_active', 'brand_id', 'category_id', 'stock_hold', 'stock'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo "Invalid field";
    exit;
}

// detect type
$type = 's';
if (in_array($field, ['is_active', 'brand_id', 'category_id', 'stock_hold', 'stock'])) {
    $type = 'i';
    $value = intval($value);
} else {
    $value = trim($value);
}

$stmt = $connection->prepare("UPDATE products SET $field = ? WHERE product_id = ?");
if(!$stmt){
    http_response_code(500);
    echo "Prepare failed";
    exit;
}

$stmt->bind_param($type . 'i', $value, $id);
if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Error updating";
}
?>

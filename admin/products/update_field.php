<?php
require_once '../config.php';
require_once '../includes/db_connections.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Validate inputs
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowedFields = ['price', 'stock', 'product_name', 'description', 'is_active'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo "Invalid field";
    exit;
}

if ($id <= 0) {
    http_response_code(400);
    echo "Invalid product ID";
    exit;
}

// Determine data type for prepared statement
switch ($field) {
    case 'price':
        $type = 'di'; // double, int
        $value = floatval($value);
        break;
    case 'stock':
    case 'is_active':
        $type = 'ii'; // int, int
        $value = intval($value);
        break;
    case 'product_name':
    case 'description':
        $type = 'si'; // string, int
        $value = trim($value);
        break;
    default:
        http_response_code(400);
        echo "Invalid field type";
        exit;
}

$query = "UPDATE products SET `$field` = ? WHERE product_id = ?";
$stmt = $connection->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo "Failed to prepare statement";
    exit;
}

// Bind dynamically
if ($type === 'di') {
    $stmt->bind_param("di", $value, $id);
} elseif ($type === 'ii') {
    $stmt->bind_param("ii", $value, $id);
} else {
    $stmt->bind_param("si", $value, $id);
}

if ($stmt->execute()) {
    echo ucfirst(str_replace('_', ' ', $field)) . " updated successfully";
} else {
    http_response_code(500);
    echo "Update failed";
}

$stmt->close();
$connection->close();
?>
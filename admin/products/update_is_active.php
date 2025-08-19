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
$value = isset($_POST['value']) ? intval($_POST['value']) : -1; // should be 0 or 1

if ($id <= 0 || ($value !== 0 && $value !== 1)) {
    http_response_code(400);
    echo "Invalid product ID";
    exit;
}

$query = "UPDATE products SET is_active = ? WHERE product_id = ?";
$stmt = $connection->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo "Failed to prepare statement";
    exit;
}

$stmt->bind_param("ii", $value,$id);

if ($stmt->execute()) {
    echo "Status updated successfully";
} else {
    http_response_code(500);
    echo "Update failed";
}

$stmt->close();
$connection->close();
?>
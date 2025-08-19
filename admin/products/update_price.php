<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = intval($_POST['product_id']);
    $cost_price   = floatval($_POST['cost_price']);
    $profit_price = floatval($_POST['profit_price']);
    // $selling_price= floatval($_POST['selling_price']);

    // Server-side validation: no negative values
    if ($cost_price < 0 || $profit_price < 0) {
        echo "NEGATIVE";
        exit;
    }

    // Update query
    $stmt = $connection->prepare("
        UPDATE products 
        SET cost_price = ?, profit_price = ?, selling_price = ? 
        WHERE product_id = ?
    ");

    $selling_price = $cost_price + $profit_price;

    $stmt->bind_param("dddi", $cost_price, $profit_price, $selling_price, $product_id);

    if ($stmt->execute()) {
        echo "OK";
    } else {
        http_response_code(500);
        echo "Error updating";
    }
    exit;
}

?>
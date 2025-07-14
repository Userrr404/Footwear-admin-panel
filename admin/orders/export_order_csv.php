<?php
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = intval($_GET['id']);

// Fetch order items
$itemsStmt = $connection->prepare("
    SELECT oi.*, p.product_name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=order_$order_id.csv");

$output = fopen('php://output', 'w');
fputcsv($output, ['Product Name', 'Quantity', 'Price', 'Total']);

while ($item = $items->fetch_assoc()) {
    fputcsv($output, [
        $item['product_name'],
        $item['quantity'],
        number_format($item['price'], 2),
        number_format($item['quantity'] * $item['price'], 2)
    ]);
}
fclose($output);
exit;
?>
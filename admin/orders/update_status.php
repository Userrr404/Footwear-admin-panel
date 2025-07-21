<?php
require_once '../includes/db_connections.php';

$orderId = intval($_POST['order_id'] ?? 0);
$newStatus = $_POST['order_status'] ?? '';

$validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];

if ($orderId && in_array($newStatus, $validStatuses)) {
    $stmt = $connection->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);
    $stmt->execute();
}

header("Location: view.php?order_id=$orderId");
exit;
?>
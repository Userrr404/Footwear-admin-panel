<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1,intval($_GET['page'] ?? 1));
$limit = 5; // same as orders/list.php
$offset = ($page - 1) * $limit;
$where = "WHERE 1";

if ($search !== '') {
  $safe = $connection->real_escape_string($search);
  $where .= " AND (users.username LIKE '%$safe%' OR orders.order_status LIKE '%$safe%')";
}

if ($status !== '') {
  $safe = $connection->real_escape_string($status);
  $where .= " AND orders.order_status = '$safe'";
}

$results = $connection->query("
  SELECT orders.*, users.username 
  FROM orders 
  JOIN users ON orders.user_id = users.user_id 
  $where 
  ORDER BY orders.order_id DESC
  LIMIT $limit OFFSET $offset
");

// Send headers to download CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=orders_export.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Order ID', 'Username', 'Total', 'Order Status', 'Payment Status', 'payment Method', 'Date']);

while ($row = $results->fetch_assoc()) {
  fputcsv($output, [
    "\t" . $row['order_id'],
    "\t" . $row['username'],
    "\t" . $row['total_amount'],
    ucfirst("\t" . $row['order_status']),
    ucfirst("\t" . $row['payment_status']),
    ucfirst("\t" . $row['payment_method']),
    '="' . date('d-m-Y H:i', strtotime($row['placed_at'])) . '"'
  ]);
}

fclose($output);
exit;
?>

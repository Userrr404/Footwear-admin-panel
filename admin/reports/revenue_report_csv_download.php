<?php
require_once '../includes/db_connections.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="revenue_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Total Revenue']);

$query = $connection->query("
    SELECT DATE(placed_at) as date, SUM(total_amount) as revenue
    FROM orders
    WHERE order_status = 'delivered' AND placed_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(placed_at)
");

while ($row = $query->fetch_assoc()) {
    fputcsv($output, [$row['date'], $row['revenue']]);
}
fclose($output);
exit;
?>
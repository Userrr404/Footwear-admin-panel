<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Input sanitation
$startDate   = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate     = $_GET['end'] ?? date('Y-m-d');
$productId   = $_GET['product_id'] ?? '';
$categoryId  = $_GET['category_id'] ?? '';

// Build WHERE clause
$where = "WHERE DATE(o.placed_at) BETWEEN ? AND ? AND o.order_status = 'delivered'";
$params = [$startDate, $endDate];
$types = "ss";

if (!empty($productId)) {
    $where .= " AND oi.product_id = ?";
    $params[] = (int)$productId;
    $types .= "i";
}
if (!empty($categoryId)) {
    $where .= " AND p.category_id = ?";
    $params[] = (int)$categoryId;
    $types .= "i";
}

// SQL with parameter placeholders
$sql = "
    SELECT 
        p.product_name AS 'Product Name',
        c.category_name AS 'Category Name',
        SUM(oi.quantity) AS 'QuantitySold',
        ROUND(SUM(oi.quantity * oi.price), 2) AS 'Total Revenue (₹)'
    FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id
    JOIN categories c ON c.category_id = p.category_id
    JOIN orders o ON o.order_id = oi.order_id
    $where
    GROUP BY oi.product_id
    ORDER BY QuantitySold DESC
";

// Prepare statement
$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Write headers
if ($result->num_rows > 0) {
    $headers = array_keys($result->fetch_assoc());
    fputcsv($output, $headers);
    $result->data_seek(0); // reset pointer
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No records found for selected criteria.']);
}

fclose($output);
exit;
?>
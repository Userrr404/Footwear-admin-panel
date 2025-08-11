<?php
require_once '../includes/auth_check.php';
require_once '../config.php';
require_once '../includes/db_connections.php';

// Set headers to download file as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=products_stock_' . date('Y-m-d_H-i-s') . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ['Product ID', 'Product Name', 'Brand', 'Category', 'description', 'Stock', 'Cost Price', 'Selling Price', 'Status']);

// Fetch data securely
$sql = "SELECT p.*, b.brand_name, c.category_name,
               CASE WHEN p.is_active = 1 THEN 'Active' ELSE 'Inactive' END AS status
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        ORDER BY p.product_name ASC";

$result = $connection->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Write each row into CSV
        fputcsv($output, [
            $row['product_id'],
            $row['product_name'],
            $row['brand_name'],
            $row['category_name'],
            $row['description'],
            $row['stock'],
            $row['cost_price'],
            number_format($row['price'], 2),
            $row['status']
        ]);
    }
}

fclose($output);
exit;
?>

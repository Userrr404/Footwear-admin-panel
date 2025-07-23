<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$startDate   = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate     = $_GET['end'] ?? date('Y-m-d');
$productId   = $_GET['product_id'] ?? '';
$categoryId  = $_GET['category_id'] ?? '';

// Build SQL WHERE clause
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

// SQL Query
$sql = "
    SELECT 
        p.product_name AS 'Product Name',
        c.category_name AS 'Category',
        SUM(oi.quantity) AS 'Quantity Sold',
        ROUND(SUM(oi.quantity * oi.price), 2) AS 'Total Revenue'
    FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id
    JOIN categories c ON c.category_id = p.category_id
    JOIN orders o ON o.order_id = oi.order_id
    $where
    GROUP BY oi.product_id
    ORDER BY `Quantity Sold` DESC
";

// Fetch data
$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Set headers to open "print-to-PDF" view
header("Content-Type: text/html");
header("Content-Disposition: inline; filename=sales_report.html");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; padding: 20px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .no-data { text-align: center; color: #888; padding: 20px; }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>

<h2>Sales Report<br>
<small><?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?></small></h2>

<button onclick="window.print()">🖨️ Print or Save as PDF</button>

<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Category</th>
            <th>Quantity Sold</th>
            <th>Total Revenue (₹)</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Product Name']) ?></td>
                    <td><?= htmlspecialchars($row['Category']) ?></td>
                    <td><?= (int)$row['Quantity Sold'] ?></td>
                    <td>₹<?= number_format($row['Total Revenue'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="no-data">No data available for selected filters.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

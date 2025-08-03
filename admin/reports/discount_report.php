<?php
require_once '../config.php';
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

// Fetch discount report data
$query = "
    SELECT 
        d.discount_id,
        d.discount_name,
        d.discount_percent,
        d.start_date,
        d.end_date,
        d.status,
        COUNT(DISTINCT oi.order_id) AS total_orders,
        SUM((oi.price * (d.discount_percent/100)) * oi.quantity) AS total_discount_amount
    FROM discounts d
    LEFT JOIN products p ON FIND_IN_SET(p.product_id, d.applicable_products)
    LEFT JOIN order_items oi ON oi.product_id = p.product_id
    GROUP BY d.discount_id
    ORDER BY d.start_date DESC;
";
$result = $connection->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Report</title>
    <style>
        #main{
            margin-top: 30px;
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
          darkMode: 'class',
          theme: {
            extend: {
              colors: {
                primary: '#2563eb',
                success: '#16a34a',
                info: '#0ea5e9',
                neutralDark: '#1f2937',
                cardLight: '#ffffff',
                cardDark: '#1e293b'
              }
            }
          }
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-900">
<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>

<div id="main" class="ml-64 p-6">
    <h1 class="text-4xl font-bold mb-6">Discount Report</h1>

    <div class="overflow-x-auto bg-white p-4 rounded-lg shadow">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2 bg-gray-200">#</th>
                    <th class="px-4 py-2 bg-gray-200">Discount Name</th>
                    <th class="px-4 py-2 bg-gray-200">Percent(%)</th>
                    <th class="px-4 py-2 bg-gray-200">Start Date</th>
                    <th class="px-4 py-2 bg-gray-200">End Date</th>
                    <th class="px-4 py-2 bg-gray-200">Status</th>
                    <th class="px-4 py-2 bg-gray-200">Total Orders</th>
                    <th class="px-4 py-2 bg-gray-200">Discount Given (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= $row['discount_id']; ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['discount_name']); ?></td>
                            <td class="px-4 py-2"><?= $row['discount_percent']; ?>%</td>
                            <td class="px-4 py-2"><?= date('d M Y', strtotime($row['start_date'])); ?></td>
                            <td class="px-4 py-2"><?= date('d M Y', strtotime($row['end_date'])); ?></td>
                            <td class="px-4 py-2">
                                <?php if($row['status'] == 'active'): ?>
                                    <span class="text-green-700 font-semibold">Active</span>
                                <?php else: ?>
                                    <span class="text-red-600 font-semibold">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2"><?= $row['total_orders'] ?? 0; ?></td>
                            <td class="px-4 py-2">₹<?= number_format($row['total_discount_amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No Discount Data Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>

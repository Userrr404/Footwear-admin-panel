/* admin/includes/sidebar.php 
    To dynamically highlight the active sidebar link based on the current page
    */
<?php
$currentPage = $_SERVER['PHP_SELF']; // get current file path like /admin/products/list.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }
        #sidebar {
            width: 250px;
            height: 100%;
            background-color: #f8f9fa;
            position: fixed;
            margin-top: 25px; /* Adjust for header height */
            transition: transform 0.3s ease-in-out;
        }
        #sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        #sidebar ul a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
        }
        </style>
</head>
<body>
    <div id="sidebar" class="fixed top-14 left-0 w-60 h-full bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-700 z-40 transition-transform transform slide-in">
        <ul class="list-unstyled p-3">
            <a href="../dashboard/index.php"
                class="block py-2 px-4 rounded 
                <?= str_contains($currentPage, 'dashboard') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                📊 Dashboard
            </a>

            <a href="sales_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'sales_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               📈 Sales Report</a>
        
            <a href="inventory_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'inventory_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               📦 Inventory Report
            </a>
        
            <a href="revenue_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'revenue_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               💰 Revenue Report
            </a>
        
            <a href="order_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'order_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               🧾 Order Report
            </a>
        
            <a href="customer_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'customer_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               👤 Customer Report
            </a>
        
            <a href="top_products.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'top_products') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               🔥 Top Products
            </a>

            <a href="returns_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'returns_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               ↩️ Returns Report
            </a>

            <a href="discount_report.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'discount_report') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               🏷️ Discount Report
            </a>
    </ul>
</div>


</body>
</html>
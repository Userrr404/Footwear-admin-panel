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

            <a href="../products/list.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'products') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               🧦 Products</a>
        
            <a href="../orders/list.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'orders') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               📦 Orders
            </a>
        
            <a href="../users/list.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'users') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               👤 Users
            </a>
        
            <a href="../coupons/list.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'coupons') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               🎟️ Coupons
            </a>
        
            <a href="../reports/sales.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'reports') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               📈 Reports
            </a>
        
            <a href="../settings/index.php"
               class="block py-2 px-4 rounded 
               <?= str_contains($currentPage, 'settings') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
               ⚙️ Settings
            </a>
        
            <a href="../auth/logout.php"
           class="block py-2 px-4 rounded hover:bg-red-100 text-red-600">
           🚪 Logout
        </a>
    </ul>
</div>


</body>
</html>
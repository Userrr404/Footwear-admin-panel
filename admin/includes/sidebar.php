<?php
/* admin/includes/sidebar.php 
    To dynamically highlight the active sidebar link based on the current page
    */
$currentPage = $_SERVER['PHP_SELF']; // get current file path like /admin/products/list.php
?>

<div id="sidebar" class="fixed mt-5 top-14 left-0 w-60 h-full bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-700 z-40 transition-transform transform slide-in">
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
        
        <a href="../reports/report_list.php"
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

<style>
    @media (max-width: 424px){
    #sidebar {
        margin-top: 0px;
        border-right: none !important;
        border-bottom: 1px solid #e3e3e3; /* nice bottom divider (optional) */
        z-index: 10;
        width : 70% !important;
    }
    #main {
        margin-left: 0 !important;       /* so content doesn’t push right */
        /* margin-top: 10px;                gap */
    }
}

    @media(min-width : 425px) and (max-width: 767px){
        #sidebar {
        margin-top: 0px;
        border-right: none !important;
        border-bottom: 1px solid #e3e3e3; /* nice bottom divider (optional) */
        z-index: 10;
    }
    #main {
        margin-left: 0 !important;       /* so content doesn’t push right */
        /* margin-top: 10px;                gap */
    }
    }
</style>


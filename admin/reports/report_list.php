<?php
include '../includes/auth_check.php';
include '../includes/db_connections.php';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report List</title>
    <style>
    #main{
      margin-top:30px;
    }
  </style>
  <!-- Tailwind CSS 
    Without this js sidebar and main content of this page not toggle and also menuToggle.js important -->
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
    };
  </script>
</head>
<body>
    <div id="main" class="ml-60 transition-all duration-300 p-6">
        <main>
            <div class="container mt-5">
    <h2 class="mb-4">📈 Reports Dashboard</h2>

    <div class="row">
        <?php
        $reports = [
            ['title' => 'Sales Report', 'file' => 'sales_report.php', 'icon' => 'fa-chart-line'],
            ['title' => 'Inventory Report', 'file' => 'inventory_report.php', 'icon' => 'fa-warehouse'],
            ['title' => 'Revenue Report', 'file' => 'revenue_report.php', 'icon' => 'fa-coins'],
            ['title' => 'Order Report', 'file' => 'order_report.php', 'icon' => 'fa-shopping-bag'],
            ['title' => 'Customer Report', 'file' => 'customer_report.php', 'icon' => 'fa-users'],
            ['title' => 'Top Products', 'file' => 'top_products.php', 'icon' => 'fa-fire'],
            ['title' => 'Returns Report', 'file' => 'returns_report.php', 'icon' => 'fa-undo'],
            ['title' => 'Discount Report', 'file' => 'discount_report.php', 'icon' => 'fa-tags'],
        ];

        foreach ($reports as $report) {
            echo '
            <div class="col-md-3 mb-4">
                <a href="'.$report['file'].'" class="text-decoration-none">
                    <div class="card shadow-sm text-center border-0 hover-shadow">
                        <div class="card-body">
                            <i class="fas '.$report['icon'].' fa-2x mb-3 text-primary"></i>
                            <h6 class="card-title">'.$report['title'].'</h6>
                        </div>
                    </div>
                </a>
            </div>';
        }
        ?>
    </div>
</div>
        </main>
    </div>
    

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>


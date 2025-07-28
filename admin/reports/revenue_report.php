<?php
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

// Total revenue, tax, profit, COGS
$totalRevenueQuery = $connection->prepare("
    SELECT 
        SUM(total_amount) as revenue,
        SUM(tax_amount) as tax
    FROM orders
    WHERE order_status = 'delivered' AND placed_at BETWEEN ? AND ?
");
$totalRevenueQuery->bind_param("ss", $startDate, $endDate);
$totalRevenueQuery->execute();
$totals = $totalRevenueQuery->get_result()->fetch_assoc();
$totalRevenue = $totals['revenue'] ?? 0;
$totalTax = $totals['tax'] ?? 0;

// Revenue per category
$categoryRevenue = $connection->query("
    SELECT c.category_name, SUM(o.total_amount) AS revenue
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE o.order_status = 'delivered' AND o.placed_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY c.category_name
");

// Revenue by date (for chart)
$dailyRevenue = $connection->query("
    SELECT DATE(placed_at) as date, SUM(total_amount) as revenue
    FROM orders
    WHERE order_status = 'delivered' AND placed_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(placed_at)
");

// Revenue by payment method
$paymentBreakdown = $connection->query("
    SELECT payment_method, SUM(total_amount) AS revenue
    FROM orders
    WHERE order_status = 'delivered' AND placed_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY payment_method
");

// Gross Profit & COGS
$profitQuery = $connection->query("
    SELECT 
        SUM(oi.quantity * (p.price - p.cost_price)) AS profit,
        SUM(oi.quantity * p.cost_price) AS cogs
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_status = 'delivered' AND o.placed_at BETWEEN '$startDate' AND '$endDate'
");
$profitData = $profitQuery->fetch_assoc();

// Top customers
$topCustomers = $connection->query("
    SELECT u.username, u.user_email, SUM(o.total_amount) as total_spent
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_status = 'delivered' AND o.placed_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY o.user_id
    ORDER BY total_spent DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Revenue Report</title>
  <style>
    #main{
      margin-top:30px;
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<body class="bg-gray-100 text-gray-900 dark:bg-neutralDark dark:text-gray-100">

<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>

<div id="main" class="ml-60 p-6 transition-all duration-300">
  <h1 class="text-4xl font-bold mb-6">📊 Revenue Report</h1>

  <!-- Filter + Export -->
  <form id="filterForm" class="flex flex-wrap gap-4 items-center mb-6">
    <input type="date" name="start_date" value="<?= $startDate ?>" class="border px-3 py-2 rounded">
    <input type="date" name="end_date" value="<?= $endDate ?>" class="border px-3 py-2 rounded">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
    <a href="revenue_report_csv_download.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="no-underline bg-green-600 text-white px-4 py-2 rounded">Export CSV</a>
  </form>

  <div class="grid lg:grid-cols-4 gap-6 mb-8">
  <!-- Total Revenue -->
  <div class="bg-white dark:bg-cardDark rounded-xl shadow-lg p-4">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-sm text-gray-500">Total Revenue</p>
        <h2 class="text-2xl font-bold text-green-600">₹<?= number_format($totalRevenue, 2) ?></h2>
      </div>
      <div class="text-green-600 text-xl">
        💰
      </div>
    </div>
  </div>

  <!-- Tax -->
  <div class="bg-white dark:bg-cardDark rounded-xl shadow-lg p-4">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-sm text-gray-500">Total Tax</p>
        <h2 class="text-2xl font-bold text-blue-600">₹<?= number_format($totalTax, 2) ?></h2>
      </div>
      <div class="text-blue-600 text-xl">
        🧾
      </div>
    </div>
  </div>

  <!-- Gross Profit -->
  <div class="bg-white dark:bg-cardDark rounded-xl shadow-lg p-4">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-sm text-gray-500">Gross Profit</p>
        <h2 class="text-2xl font-bold text-green-700">₹<?= number_format($profitData['profit'], 2) ?></h2>
      </div>
      <div class="text-green-700 text-xl">
        📈
      </div>
    </div>
  </div>

  <!-- COGS -->
  <div class="bg-white dark:bg-cardDark rounded-xl shadow-lg p-4">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-sm text-gray-500">COGS</p>
        <h2 class="text-2xl font-bold text-red-500">₹<?= number_format($profitData['cogs'], 2) ?></h2>
      </div>
      <div class="text-red-500 text-xl">
        🏷️
      </div>
    </div>
  </div>
</div>


  <!-- Category-wise Revenue -->
  <div class="bg-white dark:bg-cardDark p-4 rounded shadow mb-6">
  <h2 class="text-lg font-semibold mb-3">Revenue by Category</h2>
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php while($row = $categoryRevenue->fetch_assoc()): ?>
      <div class="border rounded px-3 py-2 bg-gray-50 dark:bg-gray-800">
        <span class="text-sm text-gray-600 dark:text-gray-300"><?= $row['category_name'] ?></span>
        <p class="text-lg font-bold text-green-600">₹<?= number_format($row['revenue'], 2) ?></p>
      </div>
    <?php endwhile; ?>
  </div>
</div>


  <!-- Payment Methods -->
  <div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2">Revenue by Payment Method</h2>
    <ul class="list-disc pl-6">
      <?php while($row = $paymentBreakdown->fetch_assoc()): ?>
        <li><?= $row['payment_method'] ?>: ₹<?= number_format($row['revenue'], 2) ?></li>
      <?php endwhile; ?>
    </ul>
  </div>

  <!-- Top Customers -->
  <div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2">Top Customers</h2>
    <ul class="list-disc pl-6">
      <?php while($row = $topCustomers->fetch_assoc()): ?>
        <li><?= $row['username'] ?> (<?= $row['user_email'] ?>): ₹<?= number_format($row['total_spent'], 2) ?></li>
      <?php endwhile; ?>
    </ul>
  </div>

  <!-- Chart -->
  <div class="bg-white dark:bg-cardDark p-4 rounded shadow mb-6">
  <div class="flex justify-between items-center mb-3">
    <h2 class="text-lg font-semibold">Revenue Over Time</h2>
  </div>
  <canvas id="revenueChart" height="100"></canvas>
</div>
</div>

<!-- Chart -->
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [
      <?php
      mysqli_data_seek($dailyRevenue, 0);
      while ($row = $dailyRevenue->fetch_assoc()) {
        echo '"' . $row['date'] . '",';
      }
      ?>
    ],
    datasets: [{
      label: 'Revenue (₹)',
      data: [
        <?php
        mysqli_data_seek($dailyRevenue, 0);
        while ($row = $dailyRevenue->fetch_assoc()) {
          echo $row['revenue'] . ',';
        }
        ?>
      ],
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.3,
      fill: true
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'top'
      },
      tooltip: {
        mode: 'index',
        intersect: false
      }
    },
    interaction: {
      mode: 'nearest',
      axis: 'x',
      intersect: false
    },
    scales: {
      y: {
        beginAtZero: true
      }
    }
  }
});
</script>

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>

<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Default filters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate   = $_GET['end_date'] ?? date('Y-m-d');
$productId = $_GET['product_id'] ?? '';
$categoryId = $_GET['category_id'] ?? '';

// Fetch products
$products = $connection->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch categories
$categories = $connection->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);

// Build query
$where = "WHERE DATE(o.placed_at) BETWEEN '$startDate' AND '$endDate' AND o.order_status = 'delivered'";
if (!empty($productId)) {
    $where .= " AND oi.product_id = " . intval($productId);
}
if (!empty($productId)) {
    $where .= " AND oi.product_id = " . intval($productId);
}
if (!empty($categoryId)) {
    $where .= " AND p.category_id = " . intval($categoryId);
}

$sql = "
    SELECT 
        p.product_name,
        SUM(oi.quantity) AS total_quantity,
        SUM(oi.quantity * oi.price) AS total_revenue
    FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id
    JOIN orders o ON o.order_id = oi.order_id
    $where
    GROUP BY oi.product_id
    ORDER BY total_quantity DESC
";
$result = $connection->query($sql);
$salesData = $result->fetch_all(MYSQLI_ASSOC);

// Summary calculations
$totalRevenue = array_sum(array_column($salesData, 'total_revenue'));
$totalUnits   = array_sum(array_column($salesData, 'total_quantity'));

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body class="bg-gray-100 text-gray-900 dark:bg-neutralDark dark:text-gray-100 transition-colors duration-300">

<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>

<div id="main" class="ml-60 transition-all duration-300 p-6">
    <main class="space-y-8">
  <header>
    <h1 class="text-4xl font-bold tracking-tight">📈 Sales Report</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400">Overview of delivered product sales.</p>
  </header>

  <!-- Filter Section -->
  <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-white dark:bg-cardDark p-6 rounded shadow">
    <div>
      <label class="block text-sm font-medium mb-1">Date From</label>
      <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full p-2 rounded border dark:bg-gray-800 dark:border-gray-700">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Date To</label>
      <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full p-2 rounded border dark:bg-gray-800 dark:border-gray-700">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Product</label>
      <select name="product_id" class="w-full p-2 rounded border dark:bg-gray-800 dark:border-gray-700">
        <option value="">All Products</option>
        <?php foreach ($products as $product): ?>
          <option value="<?= $product['product_id'] ?>" <?= $product['product_id'] == $productId ? 'selected' : '' ?>>
            <?= htmlspecialchars($product['product_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Category</label>
      <select name="category_id" class="w-full p-2 rounded border dark:bg-gray-800 dark:border-gray-700">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $categoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end">
      <button class="w-full bg-primary text-white py-2 rounded hover:bg-blue-700 transition">Apply Filters</button>
    </div>
  </form>

  <!-- KPI Cards -->
  <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-cardDark p-6 rounded shadow text-center">
      <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Revenue</h2>
      <p class="text-4xl font-bold text-success mt-2">₹<?= number_format($totalRevenue, 2) ?></p>
    </div>
    <div class="bg-white dark:bg-cardDark p-6 rounded shadow text-center">
      <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Units Sold</h2>
      <p class="text-4xl font-bold text-info mt-2"><?= number_format($totalUnits) ?></p>
    </div>
  </section>

  <!-- Sales Table -->
  <section class="bg-white dark:bg-cardDark p-6 rounded shadow overflow-x-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">📦 Product-wise Sales</h3>
      <div class="flex gap-4">
        <a href="export_csv.php?start=<?= $startDate ?>&end=<?= $endDate ?>&product_id=<?= $productId ?>&category_id=<?= $categoryId ?>" class="text-green-600 hover:underline">📤 CSV</a>
        <a href="export_pdf.php?start=<?= $startDate ?>&end=<?= $endDate ?>&product_id=<?= $productId ?>&category_id=<?= $categoryId ?>" class="text-red-500 hover:underline">🖨️ PDF</a>
      </div>
    </div>
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100 dark:bg-gray-800 text-left">
        <tr>
          <th class="p-3 font-medium">Product</th>
          <th class="p-3 font-medium">Quantity Sold</th>
          <th class="p-3 font-medium">Total Revenue (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($salesData)): ?>
          <?php foreach ($salesData as $row): ?>
            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="p-3"><?= htmlspecialchars($row['product_name']) ?></td>
              <td class="p-3"><?= $row['total_quantity'] ?></td>
              <td class="p-3">₹<?= number_format($row['total_revenue'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" class="p-4 text-center text-gray-500">No sales data found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Chart -->
  <section class="bg-white dark:bg-cardDark p-6 rounded shadow">
    <canvas id="salesChart" height="100"></canvas>
  </section>
</main>
</div>

<script src="../assets/js/menuToggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($salesData, 'product_name')) ?>,
      datasets: [{
        label: 'Revenue (₹)',
        data: <?= json_encode(array_column($salesData, 'total_revenue')) ?>,
        backgroundColor: 'rgba(34, 197, 94, 0.7)',
        borderColor: 'rgba(34, 197, 94, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => `₹${value}`
          }
        }
      }
    }
  });
</script>

</body>
</html>

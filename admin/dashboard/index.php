<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// KPIs
$totalProducts = $connection->query("SELECT COUNT(*) FROM products")->fetch_row()[0] ?? 0;
$totalOrders = $connection->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0;
$totalUsers = $connection->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
$totalRevenue = $connection->query("SELECT SUM(total_amount) FROM orders WHERE order_status='delivered'")->fetch_row()[0] ?? 0;

// New Orders
$newOrders = $connection->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetch_row()[0] ?? 0;

// Year filter
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$allYears = $connection->query("SELECT DISTINCT YEAR(placed_at) AS year FROM orders ORDER BY year DESC");
$years = [];
while ($row = $allYears->fetch_assoc()) {
    $years[] = $row['year'];
}

// Monthly data
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$orderCounts = [];
$revenueCounts = [];
for ($m = 1; $m <= 12; $m++) {
    $stmt1 = $connection->prepare("SELECT COUNT(*) FROM orders WHERE MONTH(placed_at)=? AND YEAR(placed_at)=?");
    $stmt1->bind_param("ii", $m, $currentYear);
    $stmt1->execute();
    $orderCounts[] = $stmt1->get_result()->fetch_row()[0] ?? 0;

    $stmt2 = $connection->prepare("SELECT SUM(total_amount) FROM orders WHERE MONTH(placed_at)=? AND YEAR(placed_at)=? AND order_status='delivered'");
    $stmt2->bind_param("ii", $m, $currentYear);
    $stmt2->execute();
    $revenueCounts[] = round($stmt2->get_result()->fetch_row()[0] ?? 0, 2);
}

// Popular Products
/*
$popularProducts = $connection->query("
  SELECT 
    p.product_id,
    p.product_name,
    p.price,
    pi.image_url,
    COUNT(oi.product_id) AS total_orders,
    SUM(oi.quantity) AS units_sold,
    SUM(oi.price * oi.quantity) AS revenue,
    COUNT(DISTINCT o.user_id) AS unique_buyers,
    MAX(o.placed_at) AS last_ordered,
    ROUND(AVG(r.rating), 1) AS avg_rating
  FROM order_items oi
  JOIN products p ON p.product_id = oi.product_id
  JOIN orders o ON o.order_id = oi.order_id
  LEFT JOIN reviews r ON r.product_id = p.product_id
  LEFT JOIN product_images pi ON pi.product_id = p.product_id
  WHERE o.order_status = 'delivered'
  GROUP BY oi.product_id
  ORDER BY total_orders DESC
  LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
*/

/* PROBLEM with above query:
1. The reason your “Orders” count is inflated (e.g., showing 8 orders for BoldFit instead of 2 actual orders, and 6 for Nivia instead of 1)
    This counts every row in order_items for the product — so if a product is ordered multiple times in the same order (e.g., quantity > 1 or split into multiple rows), it adds each row, even if it's from the same order_id.

    FROM 
        COUNT(oi.product_id) AS total_orders
    TO
        COUNT(DISTINCT o.order_id) AS total_orders


2. The “Units Sold” is also inflated because it counts every row in order_items, not just distinct orders.
   The issue with incorrect units_sold values (like 12 for BoldFit Running Shoes and 18 for Nivia) is due to data duplication in the order_items table, especially multiple entries for the same product in the same order.

   That indicates these products are being counted multiple times per order, possibly due to duplicate image rows or join over multiple image entries.
    FROM
        SUM(oi.quantity) AS units_sold
    TO
        SUM(oi.quantity) AS units_sold
*/

// solution query of popular products
$popularProducts = $connection->query("
SELECT 
  p.product_id,
  p.product_name,
  p.price,
  -- Get only 1 image per product to avoid row multiplication
  (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) AS image_url,
  COUNT(DISTINCT oi.order_id) AS total_orders,
  SUM(oi.quantity) AS units_sold,
  SUM(oi.price * oi.quantity) AS revenue,
  COUNT(DISTINCT o.user_id) AS unique_buyers,
  MAX(o.placed_at) AS last_ordered,
  ROUND(AVG(DISTINCT r.rating), 1) AS avg_rating
FROM order_items oi
JOIN products p ON p.product_id = oi.product_id
JOIN orders o ON o.order_id = oi.order_id
LEFT JOIN reviews r ON r.product_id = p.product_id
WHERE o.order_status = 'delivered'
GROUP BY p.product_id
ORDER BY total_orders DESC
LIMIT 5;
")->fetch_all(MYSQLI_ASSOC);
  
// Monthly Revenue Target
// Assuming a target of ₹10000 per month
$monthlyTarget = 10000; 
$targetAchieved = array_map(fn($rev) => min(100, round(($rev / $monthlyTarget) * 100)), $revenueCounts);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Footwear</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans text-gray-800 dark:text-white">

<!-- Header -->
<header class="bg-white dark:bg-gray-800 shadow fixed w-full z-10">
  <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-blue-600 dark:text-blue-400">👟 Footwear Admin Dashboard</h1>
    <div class="flex items-center gap-4">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        Welcome, <span class="font-semibold text-gray-800 dark:text-white"><?= $_SESSION['admin_name'] ?></span>
      </div>
      <button onclick="toggleDarkMode()" class="hover:text-blue-600 text-xl" title="Toggle Dark Mode">🌓</button>
    </div>
  </div>
</header>

<!-- Sidebar + Main -->
<div class="flex pt-20">
  <!-- Sidebar -->
  <aside class="w-64 bg-white dark:bg-gray-800 h-screen shadow-lg fixed">
    <nav class="p-6 space-y-2 text-sm">
      <a href="#" class="block py-2 px-4 rounded bg-blue-100 text-blue-600 font-semibold">📊 Dashboard</a>
      <a href="../products/list.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">🧦 Products</a>
      <a href="../orders/list.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">📦 Orders</a>
      <a href="../users/list.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">👤 Users</a>
      <a href="../coupons/list.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">🎟️ Coupons</a>
      <a href="../reports/sales.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">📈 Reports</a>
      <a href="../settings/index.php" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-700">⚙️ Settings</a>
      <a href="../auth/logout.php" class="block py-2 px-4 hover:bg-red-100 text-red-600">🚪 Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 w-full p-8">
    <?php if ($newOrders > 0): ?>
      <div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded">
        <p class="font-bold">🛒 New Orders</p>
        <p><?= $newOrders ?> pending order(s). <a href="../orders/list.php" class="underline">View Orders</a></p>
      </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500 dark:text-gray-300">🛍️ Total Products</p>
        <h2 class="text-3xl font-extrabold text-blue-600 dark:text-blue-400"><?= $totalProducts ?></h2>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500 dark:text-gray-300">📦 Total Orders</p>
        <h2 class="text-3xl font-extrabold text-green-600"><?= $totalOrders ?></h2>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500 dark:text-gray-300">👤 Registered Users</p>
        <h2 class="text-3xl font-extrabold text-yellow-500"><?= $totalUsers ?></h2>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500 dark:text-gray-300">💸 Total Revenue</p>
        <h2 class="text-3xl font-extrabold text-purple-600">₹<?= number_format($totalRevenue, 2) ?></h2>
      </div>
    </div>

    <!-- Quick Actions -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
  <a href="../products/add.php" class="bg-blue-50 dark:bg-gray-800 p-4 rounded-lg shadow hover:bg-blue-100 flex items-center gap-4">
    <span class="text-2xl">➕</span>
    <div>
      <p class="text-sm text-gray-600 dark:text-gray-300">Add New Product</p>
      <p class="font-bold text-blue-600 dark:text-blue-400">Product</p>
    </div>
  </a>
  <a href="../orders/refunds.php" class="bg-red-50 dark:bg-gray-800 p-4 rounded-lg shadow hover:bg-red-100 flex items-center gap-4">
    <span class="text-2xl">💰</span>
    <div>
      <p class="text-sm text-gray-600 dark:text-gray-300">Pending Refunds</p>
      <p class="font-bold text-red-600 dark:text-red-400">Refunds</p>
    </div>
  </a>
  <a href="../support/index.php" class="bg-yellow-50 dark:bg-gray-800 p-4 rounded-lg shadow hover:bg-yellow-100 flex items-center gap-4">
    <span class="text-2xl">🛠️</span>
    <div>
      <p class="text-sm text-gray-600 dark:text-gray-300">Support Queries</p>
      <p class="font-bold text-yellow-600 dark:text-yellow-400">Support</p>
    </div>
  </a>
  <a href="../reports/stock.php" class="bg-purple-50 dark:bg-gray-800 p-4 rounded-lg shadow hover:bg-purple-100 flex items-center gap-4">
    <span class="text-2xl">📉</span>
    <div>
      <p class="text-sm text-gray-600 dark:text-gray-300">Low Stock Alerts</p>
      <p class="font-bold text-purple-600 dark:text-purple-400">Inventory</p>
    </div>
  </a>
</div>


    <!-- Revenue Target Achievement Bar -->
    <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mb-10">
      <h2 class="text-lg font-semibold text-gray-700 dark:text-white mb-4">🎯 Monthly Target Achievement</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($months as $i => $month): ?>
        <div>
          <p class="text-sm text-gray-600 dark:text-gray-300"><?= $month ?></p>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded h-4 mt-1">
            <div class="bg-green-500 h-4 rounded text-xs text-white text-center" style="width: <?= $targetAchieved[$i] ?>%">
              <?= $targetAchieved[$i] ?>%
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Orders Chart -->
    <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mb-10">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700 dark:text-white">📈 Monthly Orders - <?= $currentYear ?></h2>
        <div class="flex items-center gap-2">
          <form method="get">
            <select name="year" class="border px-3 py-1 rounded text-sm dark:bg-gray-700 dark:text-white" onchange="this.form.submit()">
              <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <button onclick="scrollChart(-1)" class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded hover:bg-indigo-200">⬅</button>
          <button onclick="scrollChart(1)" class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded hover:bg-indigo-200">➡</button>
        </div>
      </div>
      <canvas id="ordersChart" height="100"></canvas>
    </section>

    <!-- Revenue Chart -->
    <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700 dark:text-white">💰 Monthly Revenue (₹) - <?= $currentYear ?></h2>
        <div class="flex gap-2">
          <button onclick="scrollRevenue(-1)" class="bg-green-100 text-green-800 px-2 py-1 rounded">⬅</button>
          <button onclick="scrollRevenue(1)" class="bg-green-100 text-green-800 px-2 py-1 rounded">➡</button>
        </div>
      </div>
      <canvas id="revenueChart" height="100"></canvas>
      <div class="flex justify-end gap-3 mt-4">
  <button onclick="exportRevenueToCSV()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm shadow">📥 Export CSV</button>
  <button onclick="exportRevenueToPDF()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm shadow">📄 Export PDF</button>
</div>
    </section>

    <!-- Popular Products -->
    <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mt-10">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold text-gray-700 dark:text-white">🔥 Top 5 Popular Products</h2>
    <a href="../products/list.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All Products →</a>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left">
      <thead>
        <tr class="border-b text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700">
          <th class="py-2 px-3">Image</th>
          <th class="py-2 px-3">Product</th>
          <th class="py-2 px-3">Product Price</th>
          <th class="py-2 px-3">Orders</th>
          <th class="py-2 px-3">Units Sold</th>
          <th class="py-2 px-3">Revenue (₹)</th>
          <th class="py-2 px-3">Buyers</th>
          <th class="py-2 px-3">Avg. Rating</th>
          <th class="py-2 px-3">Last Ordered</th>
        </tr>
      </thead>
      <tbody class="text-gray-700 dark:text-gray-200">
        <?php foreach ($popularProducts as $prod): ?>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <td class="py-2 px-3">
            <img src="../uploads/products/<?= htmlspecialchars($prod['image_url']) ?>" alt="Product" class="w-12 h-12 object-cover rounded-md shadow">
          </td>
          <td class="py-2 px-3 font-medium"><?= htmlspecialchars($prod['product_name']) ?></td>
          <td class="py-2 px-3">₹<?= number_format($prod['price']) ?></td>
          <td class="py-2 px-3"><?= $prod['total_orders'] ?></td>
          <td class="py-2 px-3"><?= $prod['units_sold'] ?></td>
          <td class="py-2 px-3">₹<?= number_format($prod['revenue'], 2) ?></td>
          <td class="py-2 px-3"><?= $prod['unique_buyers'] ?></td>
          <td class="py-2 px-3"><?= $prod['avg_rating'] ? $prod['avg_rating'] . ' ⭐' : 'N/A' ?></td>
          <td class="py-2 px-3"><?= date("d M Y", strtotime($prod['last_ordered'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>


  </main>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($months) ?>;
const orderData = <?= json_encode($orderCounts) ?>;
const revenueData = <?= json_encode($revenueCounts) ?>;
let orderChart, revenueChart;
let orderStart = 0, revenueStart = 0;

function setInitialStart() {
  const currentMonth = new Date().getMonth();
  const init = currentMonth - 5;
  orderStart = revenueStart = init < 0 ? 0 : init;
}

function renderChart(ctxId, data, start, color, labelText) {
  const ctx = document.getElementById(ctxId).getContext('2d');
  const viewLabels = labels.slice(start, start + 6);
  const viewData = data.slice(start, start + 6);
  if (ctxId === 'ordersChart' && orderChart) orderChart.destroy();
  if (ctxId === 'revenueChart' && revenueChart) revenueChart.destroy();

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: viewLabels,
      datasets: [{
        label: labelText,
        data: viewData,
        backgroundColor: color.bg,
        borderColor: color.border,
        borderWidth: 1,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#4B5563' },
          grid: { color: '#E5E7EB', borderDash: [6] }
        },
        x: {
          ticks: { color: '#6B7280' },
          grid: { display: false }
        }
      }
    }
  });

  return chart;
}

function scrollChart(dir) {
  const max = labels.length - 6;
  orderStart += dir;
  if (orderStart < 0) orderStart = 0;
  if (orderStart > max) orderStart = max;
  orderChart = renderChart('ordersChart', orderData, orderStart, { bg: 'rgba(79,70,229,0.7)', border: 'rgba(79,70,229,1)' }, 'Orders');
}

function scrollRevenue(dir) {
  const max = labels.length - 6;
  revenueStart += dir;
  if (revenueStart < 0) revenueStart = 0;
  if (revenueStart > max) revenueStart = max;
  revenueChart = renderChart('revenueChart', revenueData, revenueStart, { bg: 'rgba(16,185,129,0.6)', border: 'rgba(5,150,105,1)' }, 'Revenue');
}

function exportRevenueToCSV() {
  let csv = 'Month,Revenue\n';
  for (let i = 0; i < labels.length; i++) {
    csv += `${labels[i]},${revenueData[i].toFixed(2)}\n`;
  }

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  link.setAttribute("href", URL.createObjectURL(blob));
  link.setAttribute("download", "monthly_revenue_<?= $currentYear ?>.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function exportRevenueToPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'A4' });

  doc.setFontSize(18);
  doc.text("📊 Monthly Revenue Report", 40, 40);

  doc.setFontSize(12);
  doc.setTextColor(100);
  doc.text(`Year: <?= $currentYear ?>`, 40, 60);

  const headers = [["Month", "Revenue (₹)"]];
  const rows = labels.map((label, i) => [label, revenueData[i].toFixed(2)]);

  if (doc.autoTable) {
    doc.autoTable({
      head: headers,
      body: rows,
      startY: 80,
      theme: 'striped',
      headStyles: { fillColor: [52, 58, 64], textColor: [255, 255, 255] },
      bodyStyles: { fontSize: 10 },
      alternateRowStyles: { fillColor: [245, 245, 245] },
      margin: { left: 40, right: 40 },
    });
  }

  doc.save(`Revenue_Report_<?= $currentYear ?>.pdf`);
}

// Dark mode toggle persistence
    function toggleDarkMode() {
      document.documentElement.classList.toggle('dark');
      localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }
    (function(){ if(localStorage.getItem('theme')==='dark') document.documentElement.classList.add('dark'); })();

window.onload = () => {
  setInitialStart();
  orderChart = renderChart('ordersChart', orderData, orderStart, { bg: 'rgba(79,70,229,0.7)', border: 'rgba(79,70,229,1)' }, 'Orders');
  revenueChart = renderChart('revenueChart', revenueData, revenueStart, { bg: 'rgba(16,185,129,0.6)', border: 'rgba(5,150,105,1)' }, 'Revenue');
};
</script>
</body>
</html>

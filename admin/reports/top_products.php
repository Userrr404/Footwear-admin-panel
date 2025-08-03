<?php
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date   = date('Y-m-d');
}

$stmt = $connection->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        -- Get only 1 image per product to avoid row multiplication
      (SELECT image_url FROM product_images WHERE product_id = p.product_id ORDER BY is_default DESC, image_id ASC LIMIT 1) AS product_image,
        COUNT(DISTINCT oi.order_id) AS total_orders,
        SUM(oi.quantity) AS total_quantity_sold,
        SUM(oi.quantity * oi.price) AS total_sales_amount,
        COUNT(DISTINCT o.user_id) AS unique_buyers,
        MAX(o.placed_at) AS last_sold,
        ROUND(AVG(DISTINCT r.rating), 1) AS avg_rating
    FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id
    JOIN orders o    ON oi.order_id = o.order_id
    LEFT JOIN reviews r ON r.product_id = p.product_id
    WHERE o.order_status = 'delivered'
      AND o.placed_at BETWEEN ? AND ?
    GROUP BY p.product_id
    ORDER BY total_quantity_sold DESC
    LIMIT 20
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$top_products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Top Products Report</title>
  <style>
    #main{
        margin-top: 30px;
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
    }
  </script>
</head>
<body class="bg-gray-100 text-gray-900 dark:bg-neutralDark dark:text-white">
  <?php include('../includes/header.php'); ?>
  <?php include('../includes/reports_nav.php'); ?>

  <div id="main" class="ml-60 p-6 transition-all duration-300">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <h1 class="text-3xl font-bold">📦 Top Products Report</h1>
      <button onclick="exportTableToCSV()" class="bg-success text-white px-4 py-2 rounded hover:bg-green-700">⬇ Export CSV</button>
    </div>

    <form method="get" class="flex flex-wrap items-end gap-4 bg-white dark:bg-cardDark p-4 rounded shadow mb-6">
      <div class="flex flex-col text-sm">
        <label for="start_date">From:</label>
        <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="px-3 py-2 border rounded dark:bg-cardDark dark:border-gray-600" required>
      </div>
      <div class="flex flex-col text-sm">
        <label for="end_date">To:</label>
        <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="px-3 py-2 border rounded dark:bg-cardDark dark:border-gray-600" required>
      </div>
      <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700">🔍 Filter</button>
    </form>

    <div class="overflow-x-auto bg-white dark:bg-cardDark rounded shadow">
      <table id="topProductsTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-200 dark:bg-gray-800 text-sm font-medium">
          <tr>
            <th class="px-4 py-3 text-left">Image</th>
            <th class="px-4 py-3 text-left">Product Name</th>
            <th class="px-4 py-3 text-left">Product Price</th>
            <th class="px-4 py-3 text-left">Orders</th>
            <th class="px-4 py-3 text-left">Total Sold</th>
            <th class="px-4 py-3 text-left">Total Revenue (₹)</th>
            <th class="px-4 py-3 text-left">Buyers</th>
            <th class="px-4 py-3 text-left">Avg. Rating</th>
            <th class="px-4 py-3 text-left">Last Sold</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
          <?php if (empty($top_products)): ?>
            <tr>
              <td colspan="4" class="text-center py-6">No data found for selected range.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($top_products as $product): ?>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3">
                  <img src="../uploads/products/<?= htmlspecialchars($product['product_image']) ?>" class="w-16 h-16 rounded border object-cover">
                </td>
                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($product['product_name']) ?></td>
                <td class="px-4 py-3"><?= $product['price'] ?></td>
                <td class="py-2 px-3"><?= $product['total_orders'] ?></td>
                <td class="px-4 py-3"><?= $product['total_quantity_sold'] ?></td>
                <td class="px-4 py-3 text-green-600 dark:text-green-400">₹<?= number_format($product['total_sales_amount'], 2) ?></td>
                <td class="px-4 py-3"><?= $product['unique_buyers'] ?></td>
                <td class="px-4 py-3"><?= $product['avg_rating'] ? $product['avg_rating'] . ' ⭐' : 'N/A' ?></td>
                <td class="px-4 py-3"><?= date("d M Y", strtotime($product['last_sold'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    

    <?php if (!empty($top_products)): ?>
    <div class="mt-10 bg-white dark:bg-cardDark p-6 rounded shadow">
      <h2 class="text-xl font-semibold mb-4">📊 Revenue Comparison</h2>
      <canvas id="topProductsChart" height="100"></canvas>
    </div>
    <?php endif; ?>
  </div>

  <script>
    function exportTableToCSV() {
      const rows = [...document.querySelectorAll("#topProductsTable tr")];
      const csv = rows.map(row => {
        const cells = row.querySelectorAll("th, td");
        return [...cells].map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(",");
      }).join("\n");

      const blob = new Blob([csv], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "top_products_<?= $start_date ?>_to_<?= $end_date ?>.csv";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    <?php if (!empty($top_products)): ?>
    const chartLabels = <?= json_encode(array_column($top_products, 'product_name')) ?>;
    const chartData   = <?= json_encode(array_column($top_products, 'total_sales_amount')) ?>;

    new Chart(document.getElementById('topProductsChart'), {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Revenue (₹)',
          data: chartData,
          backgroundColor: '#2563eb',
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => `₹${ctx.raw.toLocaleString()}`
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: value => `₹${value}`
            }
          }
        }
      }
    });
    <?php endif; ?>
  </script>

  <script src="../assets/js/menuToggle.js"></script>
</body>
</html>

<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Fetch KPIs
$totalProducts = $connection->query("SELECT COUNT(*) FROM products")->fetch_row()[0] ?? 0;
$totalOrders   = $connection->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0;
$totalUsers    = $connection->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;

// New orders alert
$newOrders = $connection->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetch_row()[0] ?? 0;

// Orders per month (last 6 months)
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun"];
$orderCounts = [];
foreach (range(1, 6) as $monthNum) {
    $sql = "SELECT COUNT(*) FROM orders WHERE MONTH(placed_at) = $monthNum";
    $count = $connection->query($sql)->fetch_row()[0] ?? 0;
    $orderCounts[] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Footwear</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex">

  <!-- Sidebar -->
  <aside class="w-64 h-screen bg-white shadow-md fixed">
    <div class="p-6">
      <h2 class="text-2xl font-bold text-blue-600">👟 Footwear Admin</h2>
      <p class="text-sm text-gray-500 mt-1">Welcome, <?= $_SESSION['admin_name'] ?></p>
    </div>
    <nav class="mt-6">
      <a href="../dashboard/index.php" class="block py-2.5 px-4 bg-blue-100 text-blue-600 font-semibold">Dashboard</a>
      <a href="../products/list.php" class="block py-2.5 px-4 hover:bg-blue-50">Products</a>
      <a href="../orders/list.php" class="block py-2.5 px-4 hover:bg-blue-50">Orders</a>
      <a href="../users/list.php" class="block py-2.5 px-4 hover:bg-blue-50">Users</a>
      <a href="../coupons/list.php" class="block py-2.5 px-4 hover:bg-blue-50">Coupons</a>
      <a href="../auth/logout.php" class="block py-2.5 px-4 hover:bg-red-100 text-red-600">Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 w-full p-10">

    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- New Orders Alert -->
    <?php if ($newOrders > 0): ?>
      <div class="mb-6 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
        <p class="font-bold">New Orders</p>
        <p>You have <?= $newOrders ?> pending order(s). <a class="underline" href="../orders/list.php">View Orders</a></p>
      </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-lg shadow hover:shadow-md">
        <h2 class="text-gray-500 text-sm">Total Products</h2>
        <p class="text-2xl font-bold text-blue-600"><?= $totalProducts ?></p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow hover:shadow-md">
        <h2 class="text-gray-500 text-sm">Total Orders</h2>
        <p class="text-2xl font-bold text-green-600"><?= $totalOrders ?></p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow hover:shadow-md">
        <h2 class="text-gray-500 text-sm">Total Users</h2>
        <p class="text-2xl font-bold text-yellow-600"><?= $totalUsers ?></p>
      </div>
    </div>

    <!-- Monthly Orders Chart -->
    <div class="mt-10 bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-xl font-bold mb-4">Monthly Orders (Last 6 Months)</h2>
      <canvas id="ordersChart" height="100"></canvas>
    </div>

  </main>
</div>

<!-- Chart Script -->
<script>
  const ctx = document.getElementById('ordersChart').getContext('2d');
  const ordersChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
        label: 'Orders',
        data: <?= json_encode($orderCounts) ?>,
        backgroundColor: 'rgba(59, 130, 246, 0.6)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 10 }
        }
      }
    }
  });
</script>

</body>
</html>

<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Setup filters and pagination
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// SQL WHERE conditions
$where = "WHERE 1";
if ($search !== '') {
  $safe = $connection->real_escape_string($search);
  $where .= " AND (users.username LIKE '%$safe%' OR orders.order_status LIKE '%$safe%')";
}
if ($statusFilter !== '') {
  $safe = $connection->real_escape_string($statusFilter);
  $where .= " AND orders.order_status = '$safe'";
}

// Total count for pagination
$totalCount = $connection->query("SELECT COUNT(*) as count FROM orders JOIN users ON orders.user_id = users.user_id $where")->fetch_assoc()['count'];
$totalPages = ceil($totalCount / $limit);

// Orders query
$orders = $connection->query("SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.user_id $where ORDER BY orders.placed_at DESC LIMIT $limit OFFSET $offset");
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders List</title>
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
      darkMode: 'class'
    }
  </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white transition-colors duration-300">
  <?php include('../includes/header.php'); ?>
  <?php include('../includes/sidebar.php'); ?>

  <!-- Main Content -->
  <div id="main" class="ml-60 transition-all duration-300 p-6">
    <main>
      <h1 class="text-3xl font-bold mb-6 text-gray-800">🧾 All Orders</h1>

      <!-- Search & Filter Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
    <form class="flex gap-2" method="GET">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="px-3 py-2 border rounded w-64" placeholder="Search user or status...">
      <select name="status" class="px-3 py-2 border rounded">
        <option value="">All Status</option>
        <?php foreach (['pending','shipped','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
    </form>
    <div>
      <a href="export_csv.php?search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="text-sm bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">⬇️ Export CSV</a>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="overflow-x-auto bg-white shadow-md rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Order ID</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">User</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Total</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Order Status</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Payment</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Placed At</th>
          <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 text-sm">
        <?php while($row = $orders->fetch_assoc()): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-3 text-gray-800 font-medium">#<?= $row['order_id'] ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($row['username']) ?></td>
            <td class="px-6 py-3">₹<?= number_format($row['total_amount'], 2) ?></td>
            <td class="px-6 py-3">
              <?php
                $status = $row['order_status'] ?? 'unknown';
                $color = match ($status) {
                  'pending' => 'bg-yellow-100 text-yellow-700',
                  'shipped' => 'bg-blue-100 text-blue-700',
                  'delivered' => 'bg-green-100 text-green-700',
                  'cancelled' => 'bg-red-100 text-red-700',
                  default => 'bg-gray-100 text-gray-700'
                };
              ?>
              <span class="px-2 py-1 rounded text-xs font-semibold <?= $color ?>">
                <?= ucfirst($status) ?>
              </span>
            </td>
            <td class="px-6 py-3">
              <?php
                $payment = strtolower($row['payment_status'] ?? 'unpaid');
                $pcolor = $payment === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
              ?>
              <span class="px-2 py-1 rounded text-xs font-semibold <?= $pcolor ?>">
                <?= ucfirst($payment) ?>
              </span>
            </td>
            <td class="px-6 py-3"><?= date('d M Y, h:i A', strtotime($row['placed_at'])) ?></td>
            <td class="px-6 py-3 text-center">
              <a class="text-blue-600 hover:underline mr-2" href="view.php?order_id=<?= $row['order_id'] ?>">View</a>
              <a class="text-red-600 hover:underline" href="delete.php?order_id=<?= $row['order_id'] ?>" onclick="return confirm('Are you sure you want to delete this order?');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-6 gap-2">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
           class="px-3 py-1 border rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
    </main>

  </div>
  <!-- Scrips -->
   <script src="../assets/js/menuToggle.js"></script>
</body>
</html>

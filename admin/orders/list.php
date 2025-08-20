<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Setup filters & pagination
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE 1";
if ($search !== '') {
  $safe = $connection->real_escape_string($search);
  $where .= " AND (users.username LIKE '%$safe%' OR orders.order_status LIKE '%$safe%')";
}
if ($statusFilter !== '') {
  $safe = $connection->real_escape_string($statusFilter);
  $where .= " AND orders.order_status = '$safe'";
}

// Count total orders for pagination
$totalCount = $connection->query("
  SELECT COUNT(*) as count 
  FROM orders 
  JOIN users ON orders.user_id = users.user_id 
  $where
")->fetch_assoc()['count'];
$totalPages = ceil($totalCount / $limit);

// Fetch paginated orders
$orders = $connection->query("
  SELECT orders.*, users.username 
  FROM orders 
  JOIN users ON orders.user_id = users.user_id 
  $where 
  ORDER BY orders.placed_at DESC 
  LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="en">
<?php include('../includes/head.php'); ?>
<style>

  #export_icon{
    color: #16a34a;
  }

  .dark #export_icon{
    color: #4ade80;
  }

@media (max-width: 479px){
  #form_search{
    display:grid;
  }
}

@media (min-width: 480px) and (max-width: 639px){
  #btn_search{
    width: 200px;
  }
}
</style>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors duration-300">
  <?php include('../includes/header.php'); ?>
  <?php include('../includes/sidebar.php'); ?>

  <!-- Main Content -->
  <div id="main" class="ml-60 transition-all duration-300 p-6">
    <main>
      <!-- Page Title -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
          🧾 Orders
        </h1>
        <a href="export_csv.php?search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $page ?>" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 transition text-sm">
           <i class="fa-solid fa-file-csv" id="export_icon"></i>
           Export CSV
        </a>
      </div>

      <!-- Search & Filters -->
      <form id="form_search" class="flex sm:flex-row sm:items-center gap-3 mb-6" method="GET">
        <input type="text" name="search" 
               value="<?= htmlspecialchars($search) ?>" 
               placeholder="🔍 Search user or status..." 
               class="px-4 py-2 border rounded-lg w-full sm:w-64 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 text-base">
        
        <select name="status" 
                class="px-4 py-2 border rounded-lg w-full sm:w-auto bg-gray-50 dark:bg-gray-800 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 text-base">
          <option value="">All Status</option>
          <?php foreach (['pending','shipped','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button id="btn_search" type="submit" 
                class="bg-blue-600 text-white px-5 py-3 rounded-lg shadow hover:bg-blue-700 transition w-full sm:w-auto text-base font-medium">
          Search
        </button>
      </form>

      <!-- Orders Table -->
      <!-- <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-md rounded-xl">
        <div class="min-w-[1175px]">
          <table class="min-w-full table-fixed divide-y divide-gray-200 dark:divide-gray-700 text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left font-semibold">Order ID</th>
              <th class="px-6 py-3 text-left font-semibold">User</th>
              <th class="px-6 py-3 text-left font-semibold">Total</th>
              <th class="px-6 py-3 text-left font-semibold">Status</th>
              <th class="px-6 py-3 text-left font-semibold">Payment</th>
              <th class="px-6 py-3 text-left font-semibold">Placed At</th>
              <th class="px-6 py-3 text-center font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php if ($orders->num_rows > 0): ?>
              <?php while($row = $orders->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                  <td class="px-6 py-3 font-medium">#<?= $row['order_id'] ?></td>
                  <td class="px-6 py-3"><?= htmlspecialchars($row['username']) ?></td>
                  <td class="px-6 py-3">₹<?= number_format($row['total_amount'], 2) ?></td>
                  <td class="px-6 py-3">
                    <?php
                      $status = $row['order_status'] ?? 'unknown';
                      $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-200',
                        'shipped' => 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200',
                        'delivered' => 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200',
                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200',
                      ];
                      $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                    ?>
                    <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $color ?>">
                      <?= ucfirst($status) ?>
                    </span>
                  </td>
                  <td class="px-6 py-3">
                    <?php
                      $payment = strtolower($row['payment_status'] ?? 'unpaid');
                      $pcolor = $payment === 'paid' 
                        ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200' 
                        : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200';
                    ?>
                    <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $pcolor ?>">
                      <?= ucfirst($payment) ?>
                    </span>
                  </td>
                  <td class="px-6 py-3"><?= date('d M Y, h:i A', strtotime($row['placed_at'])) ?></td>
                  <td class="flex justify-center gap-3">
                    <a href="view.php?order_id=<?= $row['order_id'] ?>" 
                       class="text-blue-600 hover:underline">View</a>
                    <a href="delete.php?order_id=<?= $row['order_id'] ?>" 
                       class="text-red-600 hover:underline"
                       onclick="return confirm('Are you sure you want to delete this order?');">
                      Delete
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                  🚫 No orders found
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div> -->

      <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-md rounded-xl">
        <div class="min-w-[1177px]">
          <table class="min-w-full table-fixed divide-y divide-gray-200 dark:divide-gray-700 text-sm hidden sm:table">
            <thead class="bg-gray-100 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left font-semibold w-[100px]">Order ID</th>
                <th class="px-4 py-3 text-left font-semibold w-[160px]">User</th>
                <th class="px-4 py-3 text-left font-semibold w-[110px]">Total</th>
                <th class="px-4 py-3 text-left font-semibold w-[120px]">Status</th>
                <th class="px-4 py-3 text-left font-semibold w-[130px]">Payment</th>
                <th class="px-4 py-3 text-left font-semibold w-[130px]">Payment Method</th>
                <th class="px-4 py-3 text-left font-semibold w-[200px]">Placed At</th>
                <th class="px-4 py-3 text-center font-semibold w-[140px]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <?php foreach ($orders as $row): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                  <td class="px-4 py-3"><?= htmlspecialchars($row['order_id']) ?></td>
                  <td class="px-4 py-3"><?= htmlspecialchars($row['username']) ?></td>
                  <td class="px-4 py-3">₹<?= number_format($row['total_amount'], 2) ?></td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                      <?= match($row['order_status']) {
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'shipped' => 'bg-blue-100 text-blue-700',
                        'delivered' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700'
                      } ?>">
                      <?= ucfirst($row['order_status']) ?>
                    </span>
                  </td>
                  <td class="px-6 py-3">
                    <?php
                      $payment = strtolower($row['payment_status'] ?? 'unpaid');
                      $pcolor = $payment === 'paid' 
                        ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200' 
                        : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200';
                    ?>
                    <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $pcolor ?>">
                      <?= ucfirst($payment) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <?php if($row['payment_status'] === 'paid'): ?>
                      <?= htmlspecialchars($row['payment_method']) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?> 
                  </td>
                  <td class="px-4 py-3"><?= date('d M Y, H:i', strtotime($row['placed_at'])) ?></td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex justify-center gap-3">
                      <a href="view.php?order_id=<?= $row['order_id'] ?>" 
                        class="text-blue-600 hover:underline">View</a>
                      <a href="delete.php?order_id=<?= $row['order_id'] ?>" 
                        class="text-red-600 hover:underline"
                        onclick="return confirm('Are you sure you want to delete this order?');">
                        Delete
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Cards (visible only below 640px) -->
      <div class="space-y-4 sm:hidden">
        <?php foreach ($orders as $row): ?>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-3">
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                Order #<?= htmlspecialchars($row['order_id']) ?>
              </h3>
              <span class="px-2 py-1 rounded-full text-xs font-medium
                <?= match($row['order_status']) {
                  'pending' => 'bg-yellow-100 text-yellow-700',
                  'shipped' => 'bg-blue-100 text-blue-700',
                  'delivered' => 'bg-green-100 text-green-700',
                  'cancelled' => 'bg-red-100 text-red-700',
                  default => 'bg-gray-100 text-gray-700'
                } ?>">
                <?= ucfirst($row['order_status']) ?>
              </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div class="text-gray-500">User</div>
              <div class="text-gray-900 dark:text-gray-100"><?= htmlspecialchars($row['username']) ?></div>
        
              <div class="text-gray-500">Total</div>
              <div class="text-gray-900 dark:text-gray-100">₹<?= number_format($row['total_amount'], 2) ?></div>
        
              <div class="text-gray-500">Payment</div>
              <div class="text-gray-900 dark:text-gray-100">
                <?php
                  $payment = strtolower($row['payment_status'] ?? 'unpaid');
                  $pcolor = $payment === 'paid' 
                    ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200' 
                    : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200';
                ?>
                <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $pcolor ?>">
                  <?= ucfirst($payment) ?>
                </span>

                <?php if ($row['payment_status'] === 'paid'): ?>
                  <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    <?= htmlspecialchars($row['payment_method']) ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- <div class="text-gray-900 dark:text-gray-100"><?= htmlspecialchars($row['payment_method']) ?></div> -->
        
              <div class="text-gray-500">Placed At</div>
              <div class="text-gray-900 dark:text-gray-100"><?= date('d M Y, H:i', strtotime($row['placed_at'])) ?></div>
            </div>
            <div class="mt-3 flex justify-end gap-3">
              <a href="view.php?order_id=<?= $row['order_id'] ?>" 
                class="text-blue-600 hover:underline text-sm">View</a>
              <a href="delete.php?order_id=<?= $row['order_id'] ?>" 
                class="text-red-600 hover:underline text-sm"
                onclick="return confirm('Are you sure you want to delete this order?');">
                Delete
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>


      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-6 gap-2 flex-wrap">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                class="px-3 py-1 rounded-lg border transition 
                <?= $i == $page 
                  ? 'bg-blue-600 text-white border-blue-600' 
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <!-- Scripts -->
  <script src="../assets/js/menuToggle.js"></script>
</body>
</html>

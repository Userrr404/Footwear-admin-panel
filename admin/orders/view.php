<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$orderId) {
  echo "<h2 class='text-red-600 text-xl font-semibold px-6 py-4'>❌ Invalid Order ID.</h2>";
  exit;
}

$order = $connection->query("SELECT * FROM orders WHERE order_id = $orderId")->fetch_assoc();

if (!$order) {
  echo "<h2 class='text-red-600 text-xl font-semibold px-6 py-4'>❌ Order not found for ID #$orderId.</h2>";
  exit;
}

$items = $connection->query("
  SELECT oi.*, p.product_name 
  FROM order_items oi 
  JOIN products p ON oi.product_id = p.product_id 
  WHERE order_id = $orderId
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?= htmlspecialchars($orderId) ?> | Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

<div class="max-w-6xl mx-auto px-4 py-8">
  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <a href="list.php" class="inline-flex items-center text-sm text-blue-600 hover:underline">
      ← Back to Orders List
    </a>
    <h1 class="text-2xl font-bold text-gray-800">🧾 Order #<?= $orderId ?> Details</h1>
  </div>

  <!-- Order Summary -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <?php
      $status = strtolower($order['order_status'] ?? 'unknown');
      $badge = match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'shipped' => 'bg-blue-100 text-blue-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-600',
      };
    ?>
    <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
      <p class="text-sm text-gray-500">Status</p>
      <span class="inline-block mt-1 px-3 py-1 rounded text-sm font-semibold <?= $badge ?>">
        <?= ucfirst($status) ?>
      </span>
    </div>
    <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
      <p class="text-sm text-gray-500">Total Amount</p>
      <p class="mt-1 text-lg font-semibold text-gray-800">₹<?= number_format($order['total_amount'] ?? 0, 2) ?></p>
    </div>
    <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
      <p class="text-sm text-gray-500">Order Date</p>
      <p class="mt-1 text-base text-gray-800"><?= date('d M Y, h:i A', strtotime($order['placed_at'] ?? 'now')) ?></p>
    </div>
  </div>

  <!-- Ordered Items -->
  <div class="mb-10">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">📦 Ordered Items</h2>
    <div class="overflow-x-auto bg-white border border-gray-200 shadow rounded-lg">
      <table class="min-w-full text-sm text-gray-700">
        <thead class="bg-gray-100">
          <tr>
            <th class="text-left px-6 py-3 font-semibold">Product</th>
            <th class="text-left px-6 py-3 font-semibold">Quantity</th>
            <th class="text-left px-6 py-3 font-semibold">Price</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($item = $items->fetch_assoc()): ?>
            <tr class="border-t hover:bg-gray-50">
              <td class="px-6 py-4"><?= htmlspecialchars($item['product_name']) ?></td>
              <td class="px-6 py-4"><?= intval($item['quantity']) ?></td>
              <td class="px-6 py-4">₹<?= number_format($item['price'], 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

      <!-- Update Status and Invoice Section -->
    <div class="bg-white border border-gray-200 p-6 rounded-lg shadow flex flex-col md:flex-row items-center justify-between gap-4">
      <form action="update_status.php" method="POST" class="flex items-center gap-4 flex-wrap">
        <input type="hidden" name="order_id" value="<?= $orderId ?>">

        <label for="order_status" class="text-sm font-medium text-gray-700">Change Order Status:</label>
        <select name="order_status" id="order_status" class="px-3 py-2 border rounded-md bg-white text-sm shadow-sm focus:ring-2 focus:ring-blue-500">
          <?php
            $statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
            foreach ($statuses as $s):
              $selected = strtolower($order['order_status']) === strtolower($s) ? 'selected' : '';
          ?>
            <option value="<?= $s ?>" <?= $selected ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded text-sm font-semibold transition">
          💾 Save Status
        </button>
      </form>

      <a href="generate_invoice.php?order_id=<?= $orderId ?>"
         class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded text-sm font-semibold transition">
        🧾 Download Invoice
      </a>
    </div>

</div>
</body>
</html>

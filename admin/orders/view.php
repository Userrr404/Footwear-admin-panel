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
<body class="bg-gray-50 p-8 font-sans text-gray-800">

  <div class="max-w-5xl mx-auto">
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-800 mb-2">🧾 Order #<?= $orderId ?> Details</h2>
      <div class="space-y-1 text-sm text-gray-700">
        <p><strong>Status:</strong>
          <?php
            $status = strtolower($order['order_status'] ?? 'unknown');
            $badge = match ($status) {
              'pending' => 'bg-yellow-100 text-yellow-700',
              'shipped' => 'bg-blue-100 text-blue-700',
              'delivered' => 'bg-green-100 text-green-700',
              'cancelled' => 'bg-red-100 text-red-700',
              default => 'bg-gray-200 text-gray-600',
            };
          ?>
          <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?= $badge ?>">
            <?= ucfirst($status) ?>
          </span>
        </p>
        <p><strong>Total:</strong> ₹<?= number_format($order['total_amount'] ?? 0, 2) ?></p>
        <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['placed_at'] ?? 'now')) ?></p>
      </div>
    </div>

    <div class="mb-10">
      <h3 class="text-2xl font-semibold mb-4">📦 Ordered Items</h3>
      <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 text-left">
            <tr>
              <th class="px-6 py-3 font-medium">Product</th>
              <th class="px-6 py-3 font-medium">Quantity</th>
              <th class="px-6 py-3 font-medium">Price</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
              <tr class="border-t">
                <td class="px-6 py-3"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="px-6 py-3"><?= intval($item['quantity']) ?></td>
                <td class="px-6 py-3">₹<?= number_format($item['price'], 2) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div>
      <h3 class="text-xl font-semibold mb-2">🔧 Update Order Status</h3>
      <form method="POST" action="update_status.php" class="flex items-center gap-4 mt-2">
        <input type="hidden" name="order_id" value="<?= $orderId ?>">
        <select name="status" class="border rounded px-4 py-2 w-60 focus:ring focus:ring-blue-200">
          <?php foreach (['pending','shipped','delivered','cancelled'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $order['order_status'] === $opt ? 'selected' : '' ?>>
              <?= ucfirst($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Update Status
        </button>
      </form>
    </div>
  </div>

</body>
</html>

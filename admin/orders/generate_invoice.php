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
  <title>Invoice #<?= $orderId ?> | Footwear Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body class="bg-white text-gray-800 font-sans p-6">
  <div class="max-w-4xl mx-auto bg-white border rounded-lg shadow-lg p-8">
    
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">🧾 Invoice</h1>
        <p class="text-sm text-gray-500">Order ID: <strong>#<?= $orderId ?></strong></p>
        <p class="text-sm text-gray-500">Date: <?= date('d M Y, h:i A', strtotime($order['placed_at'])) ?></p>
      </div>
      <div class="text-right">
        <h2 class="text-xl font-semibold text-gray-700">👟 Footwear Pro</h2>
        <p class="text-sm text-gray-500">admin@footwearpro.com</p>
      </div>
    </div>

    <hr class="mb-6">

    <h3 class="text-lg font-semibold mb-2">📦 Order Summary</h3>
    <table class="min-w-full mb-6 border text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-2 border">Product</th>
          <th class="px-4 py-2 border text-center">Quantity</th>
          <th class="px-4 py-2 border text-right">Price</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $subtotal = 0;
        while ($item = $items->fetch_assoc()):
          $itemTotal = $item['quantity'] * $item['price'];
          $subtotal += $itemTotal;
        ?>
        <tr>
          <td class="px-4 py-2 border"><?= htmlspecialchars($item['product_name']) ?></td>
          <td class="px-4 py-2 border text-center"><?= $item['quantity'] ?></td>
          <td class="px-4 py-2 border text-right">₹<?= number_format($itemTotal, 2) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
      <tfoot>
        <tr class="bg-gray-50 font-semibold">
          <td class="px-4 py-2 border text-right" colspan="2">Subtotal:</td>
          <td class="px-4 py-2 border text-right">₹<?= number_format($subtotal, 2) ?></td>
        </tr>
        <tr class="bg-gray-100 font-bold">
          <td class="px-4 py-2 border text-right" colspan="2">Grand Total:</td>
          <td class="px-4 py-2 border text-right">₹<?= number_format($order['total_amount'], 2) ?></td>
        </tr>
      </tfoot>
    </table>

    <div class="mt-6">
      <p><strong>Order Status:</strong>
        <?php
        $status = strtolower($order['order_status']);
        $badge = match ($status) {
          'pending' => 'bg-yellow-100 text-yellow-700',
          'shipped' => 'bg-blue-100 text-blue-700',
          'delivered' => 'bg-green-100 text-green-700',
          'cancelled' => 'bg-red-100 text-red-700',
          default => 'bg-gray-100 text-gray-700',
        };
        ?>
        <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?= $badge ?>">
          <?= ucfirst($status) ?>
        </span>
      </p>
    </div>

    <div class="mt-10 flex justify-between items-center no-print">
      <a href="list.php" class="text-blue-600 hover:underline">&larr; Back to Orders</a>
      <button onclick="window.print()" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-900">
        🖨️ Print Invoice
      </button>
    </div>
  </div>
</body>
</html>

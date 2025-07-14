<?php
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID.");
}

$order_id = intval($_GET['id']);

// Fetch order and user
$orderStmt = $connection->prepare("SELECT o.*, u.username, u.user_email 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.user_id 
                                   WHERE o.order_id = ?");
$orderStmt->bind_param("i", $order_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Fetch ordered items with product name and image
$itemsStmt = $connection->prepare("
    SELECT oi.*, p.product_name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Invoice #<?= $order_id ?></title>
  <style>
    body { font-family: Arial; padding: 40px; color: #000; }
    .invoice-box {
      max-width: 800px;
      margin: auto;
      border: 1px solid #eee;
      padding: 30px;
      background: #fff;
    }
    table {
      width: 100%;
      line-height: 1.5;
      border-collapse: collapse;
    }
    th, td {
      padding: 8px;
      border-bottom: 1px solid #ddd;
    }
    h2, h3 {
      text-align: center;
      margin-bottom: 10px;
    }
    .text-right {
      text-align: right;
    }
    .no-print {
      margin-top: 20px;
      text-align: center;
    }
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body>

<div class="invoice-box">
  <h2>Footwear Co.</h2>
  <h3>Invoice</h3>
  <p><strong>Order ID:</strong> <?= $order_id ?></p>
  <p><strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?> (<?= $order['user_email'] ?>)</p>
  <p><strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['placed_at'])) ?></p>

  <hr>

  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price (₹)</th>
        <th>Total (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($item = $items->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= number_format($item['price'], 2) ?></td>
          <td><?= number_format($item['quantity'] * $item['price'], 2) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h4 class="text-right">Grand Total: ₹<?= number_format($order['total_amount'], 2) ?></h4>

  <div class="no-print">
    <button onclick="window.print()">🖨️ Print Invoice</button>
  </div>
</div>

</body>
</html>

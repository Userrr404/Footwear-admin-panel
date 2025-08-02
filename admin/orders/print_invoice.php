<?php
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID.");
}

$order_id = intval($_GET['id']);

// Fetch order + user details
$orderStmt = $connection->prepare("
    SELECT o.*, u.username, u.full_name, u.user_email, u.user_phone
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$orderStmt->bind_param("i", $order_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Fetch address
$addressStmt = $connection->prepare("SELECT * FROM addresses WHERE address_id = ?");
$addressStmt->bind_param("i", $order['address_id']);
$addressStmt->execute();
$address = $addressStmt->get_result()->fetch_assoc();

// Fetch items
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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= $order['order_id'] ?> - Footwear Co.</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      color: #333;
      margin: 40px;
      background: #fff;
    }

    .invoice-container {
      max-width: 800px;
      margin: auto;
      border: 1px solid #ddd;
      padding: 30px;
    }

    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #eee;
      padding-bottom: 20px;
    }

    .invoice-header h2 {
      margin: 0;
    }

    .company-info {
      text-align: right;
    }

    .section {
      margin-top: 30px;
    }

    .section h3 {
      margin-bottom: 10px;
      font-size: 18px;
      border-bottom: 1px solid #ccc;
      padding-bottom: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    table th, table td {
      padding: 10px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }

    table th {
      background-color: #f5f5f5;
    }

    .text-right {
      text-align: right;
    }

    .total-row {
      font-weight: bold;
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

<div class="invoice-container">
  <div class="invoice-header">
    <div>
      <h2>Footwear Co.</h2>
      <p>www.footwearco.com<br>contact@footwearco.com<br>+91 98765 43210</p>
    </div>
    <div class="company-info">
      <h3>INVOICE</h3>
      <p><strong>Order ID:</strong> <?= $order['order_id'] ?><br>
         <strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['placed_at'])) ?>
      </p>
    </div>
  </div>

  <div class="section">
    <h3>Customer Details</h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['full_name']) ?><br>
       <strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?><br>
       <strong>Phone:</strong> <?= htmlspecialchars($order['user_phone']) ?><br>
    </p>
  </div>

  <?php if ($address): ?>
  <div class="section">
    <h3>Shipping Address</h3>
    <p>
      <?= htmlspecialchars($address['full_name']) ?><br>
      <?= htmlspecialchars($address['address_line']) ?><br>
      <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> - <?= htmlspecialchars($address['pincode']) ?><br>
      <?= htmlspecialchars($address['phone']) ?>
    </p>
  </div>
  <?php endif; ?>

  <div class="section">
    <h3>Order Items</h3>
    <table>
      <thead>
        <tr>
          <th style="width: 45%">Product</th>
          <th style="width: 15%">Qty</th>
          <th style="width: 20%">Unit Price (₹)</th>
          <th style="width: 20%">Total (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $subtotal = 0;
        while ($item = $items->fetch_assoc()):
          $line_total = $item['quantity'] * $item['price'];
          $subtotal += $line_total;
        ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= number_format($item['price'], 2) ?></td>
            <td><?= number_format($line_total, 2) ?></td>
          </tr>
        <?php endwhile; ?>
        <tr class="total-row">
          <td colspan="3" class="text-right">Grand Total</td>
          <td>₹<?= number_format($order['total_amount'], 2) ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="section">
    <h3>Payment</h3>
    <p><strong>Method:</strong> <?= htmlspecialchars($order['payment_method']) ?><br>
       <strong>Status:</strong> <?= htmlspecialchars(ucfirst($order['payment_status'])) ?></p>
  </div>

  <div class="no-print">
    <button onclick="window.print()">🖨️ Print Invoice</button>
  </div>
</div>

</body>
</html>

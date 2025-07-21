<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = intval($_GET['id']);

// Fetch order details
$orderStmt = $connection->prepare("SELECT o.*, u.username, u.user_email 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.user_id 
                                   WHERE o.order_id = ?");
$orderStmt->bind_param("i", $order_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Fetch ordered items with product name and image
$itemsStmt = $connection->prepare("
    SELECT 
        oi.*, 
        p.product_name, 
        pi.image_url 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id
    WHERE oi.order_id = ?
    GROUP BY oi.item_id
");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Order #<?= $order['order_id'] ?> | Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .table td, .table th {
      vertical-align: middle;
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .order-header h3 {
      margin: 0;
    }
  </style>
</head>
<body class="container mt-4">

  <div class="order-header mb-3">
    <h3>🧾 Order #<?= $order['order_id'] ?> Details</h3>
    <div>
      <a href="../users/view_user.php?id=<?= $order['user_id'] ?>" class="btn btn-secondary me-2">← Back to User</a>
      <a href="print_invoice.php?id=<?= $order['order_id'] ?>" target="_blank" class="btn btn-outline-dark">
        🖨️ Print Invoice
      </a>
      <a href="export_order_csv.php?id=<?= $order['order_id'] ?>" class="btn btn-outline-primary me-2">📄 Export CSV</a>
        <a href="update_status_page.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success">🛠️ Update Status</a>
    </div>
  </div>

  <!-- Order Summary -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">Order Summary</div>
    <div class="card-body">
      <p><strong>Order ID:</strong> <?= $order['order_id'] ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?> (<?= htmlspecialchars($order['user_email']) ?>)</p>
      <p><strong>Status:</strong> 
        <?php
          $badge = match(strtolower($order['order_status'])) {
            'pending' => 'warning',
            'shipped' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
          };
        ?>
        <span class="badge bg-<?= $badge ?>"><?= ucfirst($order['order_status']) ?></span>
      </p>
      <p><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?></p>
      <p><strong>Placed At:</strong> <?= date("d M Y, h:i A", strtotime($order['placed_at'])) ?></p>
    </div>
  </div>

  <!-- Ordered Items -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">Ordered Items</div>
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40%">Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
              <td>
                <?php if (!empty($item['image_url'])): ?>
                  <img src="../uploads/products/<?= htmlspecialchars($item['image_url']) ?>" 
                       width="50" height="50" class="me-2 rounded border">
                <?php else: ?>
                  <img src="../assets/no-image.png" width="50" height="50" class="me-2 rounded border">
                <?php endif; ?>
                <a href="../products/view_product.php?id=<?= $item['product_id'] ?>" class="text-decoration-none">
                  <?= htmlspecialchars($item['product_name']) ?>
                </a>
              </td>
              <td><?= $item['quantity'] ?></td>
              <td>₹<?= number_format($item['price'], 2) ?></td>
              <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>

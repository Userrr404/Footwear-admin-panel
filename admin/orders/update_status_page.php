<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Fetch order details
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order ID.");
}

$order_id = intval($_GET['order_id']);

$stmt = $connection->prepare("SELECT o.order_id, o.order_status, u.username, o.total_amount, o.placed_at 
                              FROM orders o 
                              JOIN users u ON o.user_id = u.user_id 
                              WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    $allowed = ['pending', 'shipped', 'delivered', 'cancelled'];

    if (in_array($new_status, $allowed)) {
        $update = $connection->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $update->bind_param("si", $new_status, $order_id);
        $update->execute();

        header("Location: view_order.php?id=" . $order_id);
        exit();
    } else {
        $error = "Invalid status selected.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Update Order Status</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f8;
    }
    .card {
      max-width: 600px;
      margin: auto;
      margin-top: 50px;
    }
  </style>
</head>
<body>

<div class="card shadow">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Update Order #<?= $order['order_id'] ?> Status</h4>
  </div>
  <div class="card-body">
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?></p>
    <p><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?></p>
    <p><strong>Current Status:</strong> 
      <span class="badge bg-secondary"><?= ucfirst($order['order_status']) ?></span>
    </p>
    <p><strong>Placed At:</strong> <?= date("d M Y, h:i A", strtotime($order['placed_at'])) ?></p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
      <div class="mb-3">
        <label for="status" class="form-label">Change Status</label>
        <select name="status" id="status" class="form-select" required>
          <option value="">-- Select Status --</option>
          <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="d-flex justify-content-between">
        <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-success">✅ Update Status</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>

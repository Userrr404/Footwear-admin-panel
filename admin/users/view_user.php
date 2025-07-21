<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);
$stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Details - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card + .card {
      margin-top: 1rem;
    }
  </style>
</head>
<body class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-person-circle"></i> User Profile</h3>
    <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to User List</a>
  </div>

  <!-- Profile Overview -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="bi bi-person-lines-fill me-2"></i><?= htmlspecialchars($user['username']) ?></span>
      <?php if (!empty($user['profile_img'])): ?>
        <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_img']) ?>" class="rounded-circle border" width="50" height="50">
      <?php endif; ?>
    </div>
    <div class="card-body row g-4">
      <div class="col-md-6">
        <p><strong>User ID:</strong> <?= $user['user_id'] ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['user_email']) ?></p>
        <p><strong>Phone:</strong> <?= $user['user_phone'] ?? '<span class="text-muted">Not Provided</span>' ?></p>
        <p><strong>Role:</strong> <span class="badge bg-info text-dark"><?= ucfirst($user['role']) ?></span></p>
        <p><strong>Status:</strong> 
          <?php
            $badge = match ($user['status']) {
              'active' => 'success',
              'banned' => 'danger',
              'pending' => 'warning',
              default => 'secondary'
            };
          ?>
          <span class="badge bg-<?= $badge ?>"><?= ucfirst($user['status']) ?></span>
        </p>
      </div>
      <div class="col-md-6">
        <p><strong>2FA Enabled:</strong> 
          <?= $user['twofa_enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
        </p>
        <p><strong>Account Active:</strong> 
          <?= $user['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?>
        </p>
        <p><strong>Profile Image:</strong> 
          <?= $user['profile_img'] ? '<span class="text-success">Uploaded</span>' : '<span class="text-muted">Not Provided</span>' ?>
        </p>
        <p><strong>Created At:</strong> 
          <?= $user['created_at'] ? date("d M Y, h:i A", strtotime($user['created_at'])) : 'N/A' ?>
        </p>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
  <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-warning">
    <i class="bi bi-pencil-square"></i> Edit
  </a>
  <a href="delete_user.php?id=<?= $user['user_id'] ?>" 
     onclick="return confirm('Are you sure you want to delete this user?')" 
     class="btn btn-danger">
    <i class="bi bi-trash"></i> Delete
  </a>

  <?php if (strtolower($user['status']) !== 'banned'): ?>
    <a href="ban_user.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-danger">
      <i class="bi bi-slash-circle"></i> Ban
    </a>
  <?php endif; ?>
</div>

  </div>

  <!-- Recent Orders -->
  <?php
  $orderStmt = $connection->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT 5");
  $orderStmt->bind_param("i", $user_id);
  $orderStmt->execute();
  $orders = $orderStmt->get_result();
  ?>
  <?php if ($orders->num_rows > 0): ?>
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white"><i class="bi bi-box-seam"></i> Recent Orders</div>
      <div class="card-body p-0">
        <table class="table mb-0 table-hover">
          <thead class="table-light">
            <tr>
              <th>Order ID</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
              <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                <td>
                  <?php
                    $orderStatus = strtolower($order['order_status']);
                    $statusColor = match ($orderStatus) {
                      'pending' => 'warning',
                      'shipped' => 'info',
                      'delivered' => 'success',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($orderStatus) ?></span>
                </td>
                <td><?= date("d M Y, h:i A", strtotime($order['placed_at'])) ?></td>
                <td><a href="../orders/view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Activity Logs -->
  <?php
  $logStmt = $connection->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY log_time DESC LIMIT 10");
  $logStmt->bind_param("i", $user_id);
  $logStmt->execute();
  $logs = $logStmt->get_result();
  ?>
  <div class="card shadow-sm mt-4">
    <div class="card-header bg-secondary text-white"><i class="bi bi-activity"></i> User Activity Logs</div>
    <div class="card-body p-0">
      <?php if ($logs->num_rows > 0): ?>
        <table class="table mb-0">
          <thead class="table-light">
            <tr>
              <th>Action</th>
              <th>IP</th>
              <th>User Agent</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($log = $logs->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($log['action_type'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($log['ip_address'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($log['user_agent'] ?? 'Unknown') ?></td>
                <td><?= $log['log_time'] ? date("d M Y, h:i A", strtotime($log['log_time'])) : 'N/A' ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="p-3 text-muted">No recent user activity found.</div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>

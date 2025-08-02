<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}
$user_id = intval($_GET['id']);

// Fetch user
$stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    die("User not found.");
}

// Recent orders (limit 5)
$orderStmt = $connection->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT 5");
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orders = $orderStmt->get_result();

// Activity logs (last 10)
$logStmt = $connection->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY log_time DESC LIMIT 10");
$logStmt->bind_param("i", $user_id);
$logStmt->execute();
$logs = $logStmt->get_result();

// Helper for badge classes
function statusBadge($status) {
    return match (strtolower($status)) {
        'active' => 'success',
        'pending' => 'warning',
        'banned', 'deleted' => 'danger',
        default => 'secondary'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Details - Admin Panel</title>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f1f5f9; }
    .card + .card { margin-top: 1rem; }
    .profile-label { font-weight: 600; }
    .small-badge { font-size: 0.65rem; padding: 0.35em 0.6em; }
  </style>
</head>
<body class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-person-circle"></i> User Profile</h3>
    <div>
      <a href="list.php" class="btn btn-secondary me-2"><i class="bi bi-arrow-left"></i> Back to List</a>
      <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Edit</a>
    </div>
  </div>

  <!-- Profile Overview -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <div>
        <i class="bi bi-person-lines-fill me-2"></i>
        <?= htmlspecialchars($user['username']) ?> (ID: <?= $user['user_id'] ?>)
      </div>
      <div class="d-flex align-items-center gap-3">
        <?php if (!empty($user['profile_img'])): ?>
          <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_img']) ?>" class="rounded-circle border" width="60" height="60" alt="Profile">
        <?php else: ?>
          <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center" style="width:60px;height:60px;">
            <i class="bi bi-person-fill"></i>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <div class="row gy-3">
        <div class="col-md-4">
          <p><span class="profile-label">Full Name:</span> <?= htmlspecialchars($user['full_name']) ?></p>
          <p><span class="profile-label">Email:</span> <?= htmlspecialchars($user['user_email']) ?></p>
          <p><span class="profile-label">Phone:</span> <?= !empty($user['user_phone']) ? htmlspecialchars($user['user_phone']) : '<span class="text-muted">Not Provided</span>' ?></p>
          <p><span class="profile-label">Role:</span> <span class="badge bg-info"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></p>
          <p><span class="profile-label">Status:</span> <span class="badge bg-<?= statusBadge($user['status']) ?>"><?= htmlspecialchars(ucfirst($user['status'])) ?></span></p>
          <p><span class="profile-label">Account Active:</span> 
            <?= $user['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?>
          </p>
        </div>
        <div class="col-md-4">
          <p><span class="profile-label">2FA Enabled:</span> 
            <?= $user['twofa_enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
          </p>
          <p><span class="profile-label">Location:</span> 
            <?= htmlspecialchars(implode(', ', array_filter([$user['user_address'], $user['city'], $user['state'], $user['country']]))) ?: '<span class="text-muted">N/A</span>' ?>
          </p>
          <p><span class="profile-label">Created At:</span> <?= !empty($user['created_at']) ? date("d M Y, h:i A", strtotime($user['created_at'])) : 'N/A' ?></p>
          <p><span class="profile-label">Last Login At:</span> <?= !empty($user['last_login_at']) ? date("d M Y, h:i A", strtotime($user['last_login_at'])) : 'N/A' ?></p>
          <p><span class="profile-label">Last Login IP:</span> <?= htmlspecialchars($user['last_login_ip'] ?? 'N/A') ?></p>
        </div>
        <div class="col-md-4">
          <p><span class="profile-label">Device Type:</span> <?= !empty($user['device_type']) ? htmlspecialchars(ucfirst($user['device_type'])) : 'N/A' ?></p>
          <p><span class="profile-label">Traffic Source:</span> <?= !empty($user['traffic_source']) ? htmlspecialchars($user['traffic_source']) : 'N/A' ?></p>
          <p><span class="profile-label">Referral Code:</span> <?= !empty($user['referral_code']) ? htmlspecialchars($user['referral_code']) : 'N/A' ?></p>
          <p><span class="profile-label">Loyalty Tier:</span> <span class="badge bg-warning text-dark"><?= htmlspecialchars($user['loyalty_tier'] ?? 'Silver') ?></span></p>
          <p><span class="profile-label">CLTV:</span> ₹<?= number_format($user['cltv'] ?? 0, 2) ?></p>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex gap-2 flex-wrap">
      <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-trash"></i> Delete
      </a>
      <?php if (strtolower($user['status']) !== 'banned'): ?>
        <a href="ban_user.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-slash-circle"></i> Ban
        </a>
      <?php else: ?>
        <a href="unsuspend_user.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-success btn-sm">
          <i class="bi bi-check-circle"></i> Unsuspend
        </a>
      <?php endif; ?>
      <a href="reset_password.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-key"></i> Reset Password
      </a>
      <a href="send_email.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-envelope"></i> Send Email
      </a>
      <a href="grant_coupon.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-gift"></i> Grant Coupon
      </a>
      <a href="view_chat.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chat-dots"></i> Support History
      </a>
    </div>
  </div>

  <!-- Recent Orders -->
  <?php if ($orders->num_rows > 0): ?>
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-dark text-white">
        <i class="bi bi-box-seam"></i> Recent Orders
      </div>
      <div class="card-body p-0">
        <table class="table mb-0 table-hover">
          <thead class="table-light">
            <tr>
              <th>Order ID</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th>Payment Method</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
              <?php
                $orderStatus = strtolower($order['order_status'] ?? '');
                $statusColor = match ($orderStatus) {
                  'pending' => 'warning',
                  'shipped' => 'info',
                  'delivered' => 'success',
                  'cancelled' => 'danger',
                  default => 'secondary'
                };
              ?>
              <tr>
                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                <td>₹<?= number_format($order['total_amount'] ?? 0, 2) ?></td>
                <td><span class="badge bg-<?= $statusColor ?>"><?= ucfirst($orderStatus) ?></span></td>
                <td><?= !empty($order['placed_at']) ? date("d M Y, h:i A", strtotime($order['placed_at'])) : 'N/A' ?></td>
                <td><?= htmlspecialchars($order['payment_method'] ?? '-') ?></td>
                <td>
                  <a href="../orders/view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Activity Logs -->
  <div class="card shadow-sm mt-4">
    <div class="card-header bg-secondary text-white">
      <i class="bi bi-activity"></i> User Activity Logs
    </div>
    <div class="card-body p-0">
      <?php if ($logs->num_rows > 0): ?>
        <table class="table mb-0">
          <thead class="table-light">
            <tr>
              <th>Action</th>
              <th>IP</th>
              <th>User Agent</th>
              <th>Date</th>
              <th>Success</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($log = $logs->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($log['action_type'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($log['ip_address'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($log['user_agent'] ?? 'Unknown') ?></td>
                <td><?= $log['log_time'] ? date("d M Y, h:i A", strtotime($log['log_time'])) : 'N/A' ?></td>
                <td>
                  <?php if (isset($log['success'])): ?>
                    <?= $log['success'] ? '<span class="badge bg-success small-badge">Yes</span>' : '<span class="badge bg-danger small-badge">No</span>' ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
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

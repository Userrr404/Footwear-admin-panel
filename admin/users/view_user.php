<?php
require_once '../includes/auth_check.php'; // ensure admin login
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);
$stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>View User - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h2 class="mb-4">User Details</h2>

<a href="list.php" class="btn btn-secondary mb-3">← Back to User List</a>

<div class="card shadow">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?= htmlspecialchars($user['username']) ?>'s Profile</h5>
    <?php if (!empty($user['profile_img'])): ?>
      <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_img']) ?>" 
           alt="Profile Image" width="50" height="50" class="rounded-circle border">
    <?php endif; ?>
  </div>

  <div class="card-body">
    <p><strong>User ID:</strong> <?= $user['user_id'] ?></p>
    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['user_email']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['user_phone'] ?? 'Not provided') ?></p>
    
    <p><strong>Profile Image:</strong> 
      <?php if (!empty($user['profile_img'])): ?>
        <span class="text-success">Uploaded</span>
      <?php else: ?>
        <span class="text-muted">Not provided</span>
      <?php endif; ?>
    </p>

    <p><strong>2FA Enabled:</strong> 
      <?= $user['twofa_enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
    </p>

    <p><strong>Account Status:</strong> 
      <?= $user['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?>
    </p>

    <p><strong>Role:</strong> 
      <span class="badge bg-info text-dark"><?= ucfirst($user['role'] ?? 'Customer') ?></span>
    </p>

    <p><strong>Status:</strong> 
      <?php
        $status = $user['status'] ?? 'active';
        $badge = match($status) {
            'active' => 'success',
            'banned' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
      ?>
      <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
    </p>

    <p><strong>Created At:</strong> 
        <?php
            if(!empty($user['created_at'])){
                echo date("d M Y, h:i A", strtotime($user['created_at']));
            } else {
                echo 'Not available';
            }
        ?>    
    </p>
  </div>

  <div class="card-footer">
    <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-warning">Edit</a>
    <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
    <a href="ban_user.php?id=<?= $user['user_id'] ?>" class="btn btn-outline-danger">Ban</a>
  </div>
</div>


<!-- Optional: Recent Orders (if orders table exists) -->
<?php
$orderStmt = $connection->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT 5");
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orders = $orderStmt->get_result();
?>

<?php if ($orders->num_rows > 0): ?>
  <h4 class="mt-5">Recent Orders</h4>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Order ID</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>View</th>
            </tr>
        </thead>
    <tbody>
        <?php while ($order = $orders->fetch_assoc()): ?>
        <tr>
            <td><?= $order['order_id'] ?></td>
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
            <td>
                <?= !empty($order['placed_at']) ? date("d M Y, h:i A", strtotime($order['placed_at'])) : 'Not available' ?>
            </td>
            <td>
                <a href="../orders/view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
    </table>
<?php endif; ?>

<?php
$logStmt = $connection->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY log_time DESC LIMIT 10");
$logStmt->bind_param("i", $user_id);
$logStmt->execute();
$logs = $logStmt->get_result();
?>

<?php if ($logs->num_rows > 0): ?>
  <h4 class="mt-5">Recent User Activity</h4>
  <table class="table table-striped">
    <thead class="table-light">
      <tr>
        <th>Action</th>
        <th>IP Address</th>
        <th>User Agent</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($log = $logs->fetch_assoc()): ?>
        <tr>
          <td>
            <?php
              if(empty($log['action_type'])){
                echo '<span class="text-muted">No action recorded</span>';
              }else{
                echo htmlspecialchars($log['action_type']);
              }
            ?>
          </td>
          <td>
            <?php
              if(empty($log['ip_address'])){
                echo '<span class="text-muted">Unknown</span>';
              }else{
                echo htmlspecialchars($log['ip_address']);
              }
            ?>
          </td>
          <td>
            <?php
                if(empty($log['user_agent'])){
                  echo '<span class="text-muted">Unknown</span>';
                }else{
                  echo htmlspecialchars($log['user_agent']);
                }
            ?>
          </td>
          <td>
            <?php
              if(!empty($log['log_time'])){
                echo date("d M Y, h:i A", strtotime($log['log_time']));
              } else {
                echo '<span class="text-muted">Not available</span>';
              }
            ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="text-muted mt-4">No recent activity found.</p>
<?php endif; ?>

</body>
</html>

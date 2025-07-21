<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid user ID.");
$user_id = intval($_GET['id']);

// Fetch user data
$stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found.");

// Fetch order stats
$statsStmt = $connection->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = ?");
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

$errors = [];
$success = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['user_email']);
    $phone = trim($_POST['user_phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $twofa_enabled = isset($_POST['twofa_enabled']) ? 1 : 0;

    if (empty($username)) $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";

    if (empty($errors)) {
        $update = $connection->prepare("
            UPDATE users 
            SET username = ?, user_email = ?, user_phone = ?, role = ?, status = ?, is_active = ?, twofa_enabled = ?
            WHERE user_id = ?
        ");
        $update->bind_param("ssssssii", $username, $email, $phone, $role, $status, $is_active, $twofa_enabled, $user_id);
        $update->execute();

        $success = "✅ User updated successfully.";
        $stmt->execute(); // Refresh data
        $user = $stmt->get_result()->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f5f8fa; }
    .card { max-width: 800px; margin: 50px auto; }
    .profile-img { border-radius: 50%; width: 80px; height: 80px; object-fit: cover; }
  </style>
</head>
<body>

<div class="card shadow">
  <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Edit User: <?= htmlspecialchars($user['username']) ?></h4>
    <?php if (!empty($user['profile_img'])): ?>
      <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_img']) ?>" class="profile-img" alt="Profile">
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Order Stats -->
    <div class="mb-4 border rounded p-3 bg-light">
      <h5 class="text-secondary">📊 User Purchase Stats</h5>
      <p><strong>Total Orders:</strong> <?= $stats['total_orders'] ?></p>
      <p><strong>Total Revenue:</strong> ₹<?= number_format($stats['total_spent'], 2) ?></p>
    </div>

    <form method="POST">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($user['user_email']) ?>" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="user_phone" class="form-control" value="<?= !empty($user['user_phone']) ? htmlspecialchars($user['user_phone']) : 'Not Available' ?>">
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
          </select>
        </div>

        <div class="col-md-2 mb-3 form-check pt-4">
          <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_active">Active</label>
        </div>

        <div class="col-md-2 mb-3 form-check pt-4">
          <input class="form-check-input" type="checkbox" name="twofa_enabled" id="twofa_enabled" <?= $user['twofa_enabled'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="twofa_enabled">2FA</label>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="list.php" class="btn btn-secondary">← Back to List</a>
        <button type="submit" class="btn btn-success">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>

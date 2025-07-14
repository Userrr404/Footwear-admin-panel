<?php
require_once '../includes/auth_check.php'; // Admin authentication
require_once '../includes/db_connections.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['user_email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    // Validation
    if (empty($username)) $errors[] = "⚠️ Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "⚠️ Valid email is required.";
    if (strlen($password) < 6) $errors[] = "⚠️ Password must be at least 6 characters.";

    // Check for duplicate email
    $checkEmail = $connection->prepare("SELECT user_id FROM users WHERE user_email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        $errors[] = "❌ This email is already registered.";
    }

    // (Optional) Check for duplicate username
    // $checkUsername = $connection->prepare("SELECT user_id FROM users WHERE username = ?");
    // $checkUsername->bind_param("s", $username);
    // $checkUsername->execute();
    // if ($checkUsername->get_result()->num_rows > 0) {
    //     $errors[] = "❌ This username is already taken.";
    // }

    // Insert only if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare("
            INSERT INTO users (username, user_email, user_password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $role, $status);
        $stmt->execute();

        $success = "✅ User added successfully.";

        // Optionally redirect or clear form
        header("Location: list.php?added=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add New User</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>➕ Add New User</h2>
    <a href="list.php" class="btn btn-secondary">← Back to User List</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= $e ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <form method="POST" class="card shadow p-4">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($_POST['user_email'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Role</label>
      <select name="role" class="form-select" required>
        <option value="customer" <?= ($_POST['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </div>

    <div class="mb-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select" required>
        <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="banned" <?= ($_POST['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
      </select>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary btn-lg">💾 Add User</button>
    </div>
  </form>

</body>
</html>

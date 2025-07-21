<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['user_email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    if (empty($username)) $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

    $checkEmail = $connection->prepare("SELECT user_id FROM users WHERE user_email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        $errors[] = "This email is already registered.";
    }

    // Check for duplicate username
    $checkUsername = $connection->prepare("SELECT user_id FROM users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    if ($checkUsername->get_result()->num_rows > 0) {
        $errors[] = "❌ This username is already taken.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $connection->prepare("
            INSERT INTO users (username, user_email, user_password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $role, $status);
        $stmt->execute();

        header("Location: list.php?added=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add New User</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#1d4ed8',
            danger: '#dc2626',
            success: '#16a34a',
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white min-h-screen p-6">

  <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">➕ Add New User</h1>
      <a href="list.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">← Back to User List</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4">
        <ul class="list-disc pl-5 space-y-1">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-sm font-medium">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required
          class="w-full mt-1 px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="user_email" value="<?= htmlspecialchars($_POST['user_email'] ?? '') ?>" required
          class="w-full mt-1 px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium">Password</label>
        <input type="password" name="password" required
          class="w-full mt-1 px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium">Role</label>
        <select name="role" required
          class="w-full mt-1 px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="customer" <?= ($_POST['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
          <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Status</label>
        <select name="status" required
          class="w-full mt-1 px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="banned" <?= ($_POST['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
        </select>
      </div>

      <div class="pt-4">
        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg text-sm font-semibold transition duration-300">
          💾 Add User
        </button>
      </div>
    </form>
  </div>

</body>
</html>

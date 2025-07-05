<?php
session_start();
require_once '../config.php'; // Include configuration file
// require_once '../includes/auth_check.php'; // Include authentication check
require_once INCLUDES_PATH . 'db_connections.php'; // Include DB connection

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_email    = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];

    $stmt = $connection->prepare("SELECT * FROM admin_users WHERE admin_email = ?");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($admin_password, $admin['admin_password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['admin_name'];
        $_SESSION['admin_role'] = $admin['role'];
        header("Location: ../dashboard/index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!-- HTML FORM -->
<!DOCTYPE html>
<html>
<head>
  <title>Admin Login</title>
  <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
    <form method="POST">
      <input type="email" name="admin_email" placeholder="Email" required /><br/>
      <input type="password" name="admin_password" placeholder="Password" required /><br/>
      <button type="submit">Login</button>
      <p style="color:red;"><?= $error ?></p>
    </form>
  </div>
</body>
</html>

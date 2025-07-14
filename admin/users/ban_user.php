<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID");
}

$user_id = intval($_GET['id']);

// Check if logged-in admin is superadmin (from admin_users table session)
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? null;

// Prevent banning if not logged in as superadmin
if ($admin_role !== 'superadmin') {
    die("⛔ Only a superadmin can perform this action.");
}

// Prevent banning yourself if the user to be banned has same email
// (Optional security layer if your `users` and `admin_users` emails overlap)
if ($admin_id && $_GET['id'] == $admin_id) {
    die("⛔ You cannot ban yourself.");
}

// Fetch target user
$stmt = $connection->prepare("SELECT role, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Don't ban if already banned
if ($user['status'] === 'banned') {
    header("Location: list.php?banned=already");
    exit();
}

// Don't allow banning other admins unless you're superadmin (already enforced above)
if ($user['role'] === 'admin' && $admin_role !== 'superadmin') {
    die("You do not have permission to ban another admin.");
}

// Proceed with banning
$ban = $connection->prepare("UPDATE users SET status = 'banned' WHERE user_id = ?");
$ban->bind_param("i", $user_id);
$ban->execute();

header("Location: list.php?banned=1");
exit();
?>

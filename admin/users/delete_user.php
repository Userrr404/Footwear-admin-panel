<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Invalid User ID.");
}

$user_id = intval($_GET['id']);

// Prevent deleting self or a primary admin (if merged someday)
if ($user_id == $_SESSION['admin_id']) {
    die("❌ You cannot delete yourself.");
}

// Optional: prevent deleting any admin from `users` table (if admins also exist there)
$stmt = $connection->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("⚠️ User not found.");
}

if ($user['role'] === 'admin') {
    die("⚠️ Cannot delete another admin.");
}

// ✅ SOFT DELETE: set status = 'deleted' (recommended)
$softDelete = $connection->prepare("UPDATE users SET status = 'deleted', is_active = 0 WHERE user_id = ?");
$softDelete->bind_param("i", $user_id);
$softDelete->execute();

// ✅ Optional: log action or redirect
header("Location: list.php?deleted=1");
exit();
?>

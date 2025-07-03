<?php
require_once '../includes/db_connections.php';

$name = "Admin Master";
$email = "admin@nextGen.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "superadmin";

$stmt = $connection->prepare("INSERT INTO admin_users (admin_name, admin_email, admin_password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $password, $role);

if ($stmt->execute()) {
    echo "Admin created successfully.";
} else {
    echo "Failed: " . $stmt->error;
}
$stmt->close();
$connection->close();
?>
<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['order_id'];
  $status = $_POST['status'];

  $stmt = $connection->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
  $stmt->bind_param("si", $status, $id);
  $stmt->execute();

  header("Location: view.php?order_id=$id");
  exit();
}

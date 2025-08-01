<?php
require_once '../config.php';
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="customer_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Username', 'Full Name', 'Email', 'Join Date', 'Total Orders', 'Total Spent']);

$search = $_GET['search'] ?? '';
$searchTerm = "%$search%";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "
    SELECT u.username, u.full_name, u.user_email, u.created_at,
           COUNT(o.order_id) AS total_orders,
           COALESCE(SUM(o.total_amount), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE u.full_name LIKE ? OR u.user_email LIKE ?
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $connection->prepare($query);
$stmt->bind_param('ssii', $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['username'],
        $row['full_name'],
        $row['user_email'],
        date('d M Y', strtotime($row['created_at'])),
        $row['total_orders'],
        number_format($row['total_spent'], 2)
    ]);
}

fclose($output);
exit;
?>
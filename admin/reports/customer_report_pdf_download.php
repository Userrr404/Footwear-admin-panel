<?php
require_once '../config.php';
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

$search = $_GET['search'] ?? '';
$searchTerm = "%$search%";

// You can skip pagination in a PDF download if you want full export.
// Or keep it like this:
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10; // Increased limit for full PDF listing
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
?>

<!DOCTYPE html>
<html>
<head>
  <title>Customer Report PDF</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    th, td {
      border: 1px solid #333;
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #f4f4f4;
    }
    .no-print {
      margin-top: 20px;
      text-align: center;
    }
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body>
  <h2>Customer Report</h2>

  <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Join Date</th>
          <th>Total Orders</th>
          <th>Total Spent</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['user_email']) ?></td>
            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td><?= $row['total_orders'] ?></td>
            <td>₹<?= number_format($row['total_spent'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center;">No customer data found for your search.</p>
  <?php endif; ?>

  <div class="no-print">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>
</body>
</html>

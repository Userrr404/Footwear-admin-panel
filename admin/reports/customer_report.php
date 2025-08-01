<?php
require_once '../config.php';
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

// Filters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$countQuery = "
    SELECT COUNT(DISTINCT u.user_id) as total
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE u.full_name LIKE ? OR u.user_email LIKE ?
";
$countStmt = $connection->prepare($countQuery);
$searchTerm = "%$search%";
$countStmt->bind_param('ss', $searchTerm, $searchTerm);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalUsers = $countResult['total'];
$totalPages = ceil($totalUsers / $limit);

// Fetch users with total orders and spending
$query = "
    SELECT u.user_id, u.username, u.full_name, u.user_email, u.created_at,
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
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Customer Report</title>
  <style>
    #main{
        margin-top:30px;
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#2563eb',
            success: '#16a34a',
            info: '#0ea5e9',
            neutralDark: '#1f2937',
            cardLight: '#ffffff',
            cardDark: '#1e293b'
          }
        }
      }
    };
  </script>
</head>
<body class="bg-gray-100 dark:bg-neutralDark text-gray-900 dark:text-white transition-colors duration-300">
  <?php include('../includes/header.php'); ?>
  <?php include('../includes/reports_nav.php'); ?>

  <div id="main" class="ml-60 transition-all duration-300 p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-4xl font-bold">Customer Report</h1>
      <form method="GET" class="flex space-x-2">
        <input type="text" name="search" placeholder="Search by name or email"
               class="px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring focus:border-primary"
               value="<?= htmlspecialchars($search) ?>" />
        <button type="submit"
                class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">
          Search
        </button>
      </form>
      
    </div>

    <div class="overflow-x-auto shadow rounded-lg bg-white dark:bg-cardDark">
      <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
        <thead class="bg-gray-200 dark:bg-gray-800 uppercase text-xs">
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3">Full Name</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Join Date</th>
            <th class="px-4 py-3">Total Orders</th>
            <th class="px-4 py-3">Total Spent</th>
            <th class="px-4 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): 
          $i = 1;
          while($row = $result->fetch_assoc()): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
              <td class="px-4 py-3"><?= $i++ ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['username']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['full_name']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['user_email']) ?></td>
              <td class="px-4 py-3"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
              <td class="px-4 py-3"><?= $row['total_orders'] ?></td>
              <td class="px-4 py-3">₹<?= number_format($row['total_spent'], 2) ?></td>
              <td class="px-4 py-3">
  <a href="../users/view_user.php?id=<?= $row['user_id'] ?>"
     class="text-white bg-blue-600 hover:bg-blue-800 px-3 py-1 rounded text-xs no-underline">
     View
  </a>
</td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="6" class="px-4 py-4 text-center">No customers found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
     <?php if ($totalPages > 1): ?>
<div class="flex justify-center mt-4 space-x-2">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?search=<?= urlencode($search) ?>&page=<?= $p ?>"
       class="px-3 py-2 rounded border <?= $p == $page ? 'bg-primary text-white' : 'bg-white dark:bg-cardDark text-gray-800 dark:text-white' ?>">
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<div class="mt-6 flex gap-4">
  <a href="customer_report_csv_download.php?search=<?= urlencode($search) ?>&page=<?= $page ?>"
     class="no-underline bg-success text-white px-4 py-2 rounded hover:bg-green-700">Export CSV</a>
  
  <a href="customer_report_pdf_download.php?search=<?= urlencode($search) ?>&page=<?= $page ?>"
     class="no-underline bg-info text-white px-4 py-2 rounded hover:bg-blue-600">Export PDF</a>
</div>

  </div>

  <script src="../assets/js/menuToggle.js"></script>
</body>
</html>

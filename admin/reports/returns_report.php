<?php
require_once '../config.php';
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $connection->prepare("
    SELECT 
        r.*, 
        p.product_name,
        pi.image_url,
        u.username,
        o.placed_at AS order_date
    FROM returns r
    JOIN products p ON r.product_id = p.product_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id
    JOIN orders o ON r.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    WHERE r.requested_at BETWEEN ? AND ?
    ORDER BY r.requested_at DESC
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$results = $stmt->get_result();
$returns = $results->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Returns Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    #main{
        margin-top: 30px;
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
            warning: '#facc15',
            danger: '#dc2626',
            neutralDark: '#1f2937',
            cardDark: '#1e293b'
          }
        }
      }
    };
  </script>
</head>
<body class="bg-gray-100 dark:bg-neutralDark text-gray-900 dark:text-white">
<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>

<div id="main" class="ml-60 transition-all duration-300 p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">🔁 Returns & Refund Report</h1>
    <button onclick="window.print()" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 hidden md:block">🖨️ Print</button>
  </div>

  <form method="get" class="flex flex-wrap gap-4 items-end mb-6 bg-white dark:bg-cardDark p-4 rounded shadow">
    <div>
      <label class="text-sm block mb-1">From:</label>
      <input type="date" name="start_date" value="<?= $start_date ?>" class="px-3 py-2 rounded border dark:bg-cardDark dark:border-gray-600">
    </div>
    <div>
      <label class="text-sm block mb-1">To:</label>
      <input type="date" name="end_date" value="<?= $end_date ?>" class="px-3 py-2 rounded border dark:bg-cardDark dark:border-gray-600">
    </div>
    <button type="submit" class="bg-success text-white px-4 py-2 rounded hover:bg-green-700">Filter</button>
  </form>

  <div class="overflow-x-auto bg-white dark:bg-cardDark rounded shadow">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
      <thead class="bg-gray-200 dark:bg-gray-800">
        <tr>
          <th class="px-4 py-3 text-left">Product</th>
          <th class="px-4 py-3 text-left">User</th>
          <th class="px-4 py-3 text-left">Reason</th>
          <th class="px-4 py-3 text-left">Refund (₹)</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Requested At</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php if (empty($returns)): ?>
          <tr><td colspan="6" class="text-center py-6">No returns in selected range.</td></tr>
        <?php else: ?>
          <?php foreach ($returns as $r): ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-4 py-3 flex items-center gap-2">
                <img src="../uploads/products/<?= htmlspecialchars($r['image_url'] ?? 'no-image.png') ?>" class="w-10 h-10 rounded border object-cover">
                <span><?= htmlspecialchars($r['product_name']) ?></span>
              </td>
              <td class="px-4 py-3"><?= htmlspecialchars($r['username']) ?></td>
              <td class="px-4 py-3 text-wrap max-w-xs"><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
              <td class="px-4 py-3 text-green-600 dark:text-green-400">₹<?= number_format($r['refund_amount'], 2) ?></td>
              <td class="px-4 py-3">
                <?php
                  $color = match($r['status']) {
                    'Pending'  => 'bg-warning text-black',
                    'Approved' => 'bg-success text-white',
                    'Rejected' => 'bg-danger text-white',
                    default    => 'bg-gray-400 text-white'
                  };
                ?>
                <span class="px-2 py-1 text-xs font-semibold rounded <?= $color ?>"><?= $r['status'] ?></span>
              </td>
              <td class="px-4 py-3"><?= date("d M Y, h:i A", strtotime($r['requested_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>

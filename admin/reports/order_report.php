<?php
require_once '../includes/db_connections.php';
require_once '../includes/auth_check.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Pagination setup
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

$where = "DATE(o.placed_at) BETWEEN ? AND ?";
$params = [$startDate, $endDate];
$types = "ss";

if (!empty($search)) {
  $where .= " AND (o.order_id LIKE ? OR u.username LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $types .= "ss";
}

// Fetch orders with pagination and search
$sql = "SELECT o.order_id, o.user_id, u.username, o.total_amount, o.payment_method, o.placed_at, o.order_status
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE $where
        ORDER BY o.placed_at DESC
        LIMIT $limit OFFSET $offset";
$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Total rows for pagination
$countSql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.user_id WHERE $where";
$countStmt = $connection->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$paymentSummary = $connection->query("SELECT payment_method, SUM(total_amount) as total
                                      FROM orders
                                      WHERE DATE(placed_at) BETWEEN '$startDate' AND '$endDate'
                                      GROUP BY payment_method");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders Report</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 dark:bg-neutralDark dark:text-gray-100">
<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>
<div id="main" class="ml-60 transition-all duration-300 p-6">
<div class="container-fluid mt-4">
<h2 class="mb-4">Orders Report</h2>
<form method="get" class="row g-3">
  <div class="col-md-3">
    <label class="form-label">Start Date</label>
    <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">End Date</label>
    <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Search</label>
    <input type="text" name="search" class="form-control" placeholder="Order ID or Customer" value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-md-3 align-self-end">
    <button class="btn btn-primary">Filter</button>
    <button type="button" onclick="printReport()" class="btn btn-secondary ms-2">Print / PDF</button>
  </div>
</form>
<hr>
<h4>Payment Summary</h4>
<div style="max-width: 800px">
  <canvas id="paymentChart"></canvas>
</div>
<hr>
<h4>Orders List (<?= $startDate ?> to <?= $endDate ?>)</h4>
<div id="reportArea">
<table class="table table-bordered table-hover sortable">
  <thead class="table-dark">
  <tr>
    <th onclick="sortTable(0)">Order ID</th>
    <th onclick="sortTable(1)">Customer</th>
    <th onclick="sortTable(2)">Amount (₹)</th>
    <th onclick="sortTable(3)">Payment Method</th>
    <th onclick="sortTable(4)">Status</th>
    <th onclick="sortTable(5)">Order Date</th>
  </tr>
  </thead>
  <tbody>
  <?php while($order = $orders->fetch_assoc()): ?>
    <tr>
      <td><?= $order['order_id'] ?></td>
      <td><?= htmlspecialchars($order['username']) ?></td>
      <td><?= number_format($order['total_amount'], 2) ?></td>
      <td><?= $order['payment_method'] ?></td>
      <td><?= ucfirst($order['order_status']) ?></td>
      <td><?= date('d-M-Y', strtotime($order['placed_at'])) ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>
<nav>
<ul class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
    <a class="page-link" href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
  </li>
<?php endfor; ?>
</ul>
</nav>
</div>
</div>
<script>
const ctx = document.getElementById('paymentChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [
      <?php while($row = $paymentSummary->fetch_assoc()) echo "'{$row['payment_method']}',"; ?>
    ],
    datasets: [{
      label: 'Revenue (₹)',
      data: [
        <?php
        $paymentSummary->data_seek(0);
        while($row = $paymentSummary->fetch_assoc()) echo "{$row['total']},";
        ?>
      ],
      backgroundColor: [
        'rgba(75, 192, 192, 0.6)',
        'rgba(255, 206, 86, 0.6)',
        'rgba(255, 99, 132, 0.6)',
        'rgba(153, 102, 255, 0.6)'
      ]
    }]
  },
  options: {
    responsive: true
  }
});

function sortTable(n) {
  const table = document.querySelector(".sortable");
  let switching = true, dir = "asc", switchcount = 0;
  while (switching) {
    switching = false;
    const rows = table.rows;
    for (let i = 1; i < rows.length - 1; i++) {
      let shouldSwitch = false;
      let x = rows[i].getElementsByTagName("TD")[n];
      let y = rows[i + 1].getElementsByTagName("TD")[n];
      if ((dir === "asc" && x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) ||
          (dir === "desc" && x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase())) {
        shouldSwitch = true;
        break;
      }
    }
    if (shouldSwitch) {
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      switchcount++;
    } else {
      if (switchcount === 0 && dir === "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}

function printReport() {
  const element = document.getElementById('reportArea');
  html2canvas(element).then(canvas => {
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
    const imgProps = pdf.getImageProperties(imgData);
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
    pdf.save('orders_report.pdf');
  });
}
</script>
<script src="../assets/js/menuToggle.js"></script>
</body>
</html>

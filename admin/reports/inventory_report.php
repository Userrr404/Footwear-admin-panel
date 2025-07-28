<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Fetch filters
$brands     = $connection->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
$categories = $connection->query("SELECT category_id, category_name FROM categories ORDER BY category_name");

// Read filter values
$brandId     = $_GET['brand_id']     ?? '';
$categoryId  = $_GET['category_id']  ?? '';
$stockStatus = $_GET['stock_status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Pagination
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit       = 10;
$offset      = ($page - 1) * $limit;

$subquery = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category_id,
        p.brand_id,
        c.category_name,
        b.brand_name,
        p.stock,
        p.stock_hold,
        CASE 
            WHEN p.stock = 0 THEN 'Out of Stock'
            WHEN p.stock <= p.stock_hold THEN 'Low Stock'
            ELSE 'In Stock'
        END AS stock_status
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    JOIN brands b ON p.brand_id = b.brand_id
";

// SQL WHERE builder
$where = "WHERE 1";
$params = [];
$types = "";

if ($brandId) {
    $where .= " AND brand_id = ?";
    $params[] = (int)$brandId;
    $types .= "i";
}
if ($categoryId) {
    $where .= " AND category_id = ?";
    $params[] = (int)$categoryId;
    $types .= "i";
}

if ($stockStatus) {
    $where .= " AND stock_status = ?";
    $params[] = $stockStatus;
    $types .= "s";
}

if ($search !== '') {
    $where .= " AND product_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// Wrap subquery
$filteredSql = "SELECT * FROM ($subquery) as inventory $where ORDER BY stock ASC, product_name ASC";

// Count query (for total pagination rows)
$countSql = "SELECT COUNT(*) as total FROM ($filteredSql) as count_table";
$countStmt = $connection->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);


// Final paginated SQL
$filteredSql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";


// Execute
$stmt = $connection->prepare($filteredSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch records and Count status
$summary = ['total' => 0, 'in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
    $summary['total']++;
    $summary[strtolower(str_replace(' ', '_', $row['stock_status']))]++;
}

$colors = [
  'total' => 'blue',
  'in_stock' => 'green',
  'low_stock' => 'yellow',
  'out_of_stock' => 'red',
];

// In-build Add CSV and PDF download logic of inventory_report.php
if (isset($_GET['download'])) {
    $filename = "inventory_report_" . date('Ymd_His');

    if ($_GET['download'] === 'csv') {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$filename}.csv");

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product ID', 'Product Name', 'Category', 'Brand', 'Stock', 'Stock Status']);

        foreach ($products as $p) {
            fputcsv($output, [
                $p['product_id'],
                $p['product_name'],
                $p['category_name'],
                $p['brand_name'],
                $p['stock'],
                $p['stock_status']
            ]);
        }

        fclose($output);
        exit;
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Inventory Report</title>
    <style>
    #main{
      margin-top:30px;
    }
  </style>
  <!-- Tailwind CSS 
    Without this js sidebar and main content of this page not toggle and also menuToggle.js important -->
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
<body class="bg-gray-100 text-gray-900 dark:bg-neutralDark dark:text-gray-100 transition-colors duration-300">

<?php include('../includes/header.php'); ?>
<?php include('../includes/reports_nav.php'); ?>

<div id="main" class="ml-60 transition-all duration-300 p-6">
    <h1 class="text-4xl font-bold mb-4">📦 Inventory Report</h1>

    <!-- 🔹 Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php
            // $colors = ['total' => 'blue', 'in_stock' => 'green', 'low_stock' => 'yellow', 'out_of_stock' => 'red'];
            foreach ($summary as $key => $value):
        ?>
        <div class="p-4 rounded-lg shadow bg-white border-l-4 border-<?= $colors[$key] ?>-500">
            <h4 class="text-sm text-gray-500 uppercase"><?= ucwords(str_replace('_', ' ', $key)) ?></h4>
            <p class="text-2xl font-semibold text-<?= $colors[$key] ?>-600"><?= $value ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 🔹 Filters -->
    <form method="GET" class="flex flex-wrap gap-4 mb-6">
        <select name="brand_id" class="px-4 py-2 border rounded w-48">
            <option value="">All Brands</option>
            <?php while ($brand = $brands->fetch_assoc()): ?>
                <option value="<?= $brand['brand_id'] ?>" <?= $brandId == $brand['brand_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brand['brand_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="category_id" class="px-4 py-2 border rounded w-48">
            <option value="">All Categories</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['category_id'] ?>" <?= $categoryId == $cat['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="stock_status" class="px-4 py-2 border rounded w-48">
            <option value="">All Stock Status</option>
            <option value="In Stock" <?= $stockStatus == 'In Stock' ? 'selected' : '' ?>>In Stock</option>
            <option value="Low Stock" <?= $stockStatus == 'Low Stock' ? 'selected' : '' ?>>Low Stock</option>
            <option value="Out of Stock" <?= $stockStatus == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>

        <input
  type="text"
  name="search"
  placeholder="Search by Product Name"
  value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
  class="px-4 py-2 border rounded w-64"
/>


        <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Apply Filters</button>

        <div class="flex gap-2">
    <a href="?<?= http_build_query(array_merge($_GET, ['download' => 'csv'])) ?>"
       class="no-underline bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
       📁 Export CSV
    </a>
    <a href="inventory_report_pdf_download.php?<?= http_build_query($_GET) ?>"
       class="no-underline bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
       🧾 Export PDF
    </a>
</div>

    </form>

    <!-- 🔹 Product Table -->
    <div class="overflow-auto shadow rounded-lg">
        <table class="min-w-full text-sm text-left bg-white">
            <thead class="bg-gray-100 uppercase text-gray-600">
                <tr>
                    <th class="px-6 py-3">Product ID</th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Category</th>
                    <th class="px-6 py-3">Brand</th>
                    <th class="px-6 py-3">Stock</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products): foreach ($products as $p): ?>
                <tr class="border-t <?= $p['stock_status'] === 'Out of Stock' ? 'bg-red-50' : ($p['stock_status'] === 'Low Stock' ? 'bg-yellow-50' : '') ?>">
                    <td class="px-6 py-3"><?= $p['product_id'] ?></td>
                    <td class="px-6 py-3"><?= htmlspecialchars($p['product_name']) ?></td>
                    <td class="px-6 py-3"><?= htmlspecialchars($p['category_name']) ?></td>
                    <td class="px-6 py-3"><?= htmlspecialchars($p['brand_name']) ?></td>
                    <td class="px-6 py-3"><?= $p['stock'] ?></td>
                    <td class="px-6 py-3 font-semibold"><?= $p['stock_status'] ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="px-6 py-6 text-center">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
        <!-- Pagination -->
  <div class="mt-6 flex justify-center">
    <nav class="inline-flex space-x-1">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
          class="px-3 py-1 border rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </nav>
  </div>
</div>

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>

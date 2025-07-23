<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Fetch filter options
$brands = $connection->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
$categories = $connection->query("SELECT category_id, category_name FROM categories ORDER BY category_name");

// Filters
$brandId = $_GET['brand_id'] ?? '';
$categoryId = $_GET['category_id'] ?? '';

$where = "WHERE 1";
$params = [];
$types = "";

if (!empty($brandId)) {
    $where .= " AND p.brand_id = ?";
    $params[] = (int)$brandId;
    $types .= "i";
}

if (!empty($categoryId)) {
    $where .= " AND p.category_id = ?";
    $params[] = (int)$categoryId;
    $types .= "i";
}

// Inventory Query
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
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
    $where
    ORDER BY p.stock ASC, p.product_name ASC
";

$stmt = $connection->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory report</title>
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
<body>
    <!-- Inventory Report UI -->
<div id="main" class="ml-60 transition-all duration-300 p-6">
    <main>
        <div class="container-fluid px-4">
    <h3 class="mt-4">Inventory Report</h3>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Inventory Overview</li>
    </ol>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Filter by Brand</label>
            <select name="brand_id" class="form-select">
                <option value="">All Brands</option>
                <?php while ($brand = $brands->fetch_assoc()): ?>
                    <option value="<?= $brand['brand_id']; ?>" <?= ($brand['brand_id'] == $brandId) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($brand['brand_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Filter by Category</label>
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id']; ?>" <?= ($cat['category_id'] == $categoryId) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-warehouse me-1"></i> Current Stock
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['stock_status'] == 'Out of Stock' ? 'table-danger' : ($row['stock_status'] == 'Low Stock' ? 'table-warning' : '') ?>">
                            <td><?= $row['product_id']; ?></td>
                            <td><?= htmlspecialchars($row['product_name']); ?></td>
                            <td><?= htmlspecialchars($row['category_name']); ?></td>
                            <td><?= htmlspecialchars($row['brand_name']); ?></td>
                            <td><?= $row['stock']; ?></td>
                            <td><strong><?= $row['stock_status']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No inventory data found for the selected filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </main>
</div>

<script src="../assets/js/menuToggle.js"></script>

</body>
</html>
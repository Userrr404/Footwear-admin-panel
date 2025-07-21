<?php
require_once '../includes/auth_check.php';
require_once '../config.php';
require_once '../includes/db_connections.php';

// Filters
$filterBrand = $_GET['brand'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// WHERE conditions
$where = "WHERE 1";
if ($filterBrand !== '') $where .= " AND p.brand_id = " . intval($filterBrand);
if ($filterCategory !== '') $where .= " AND p.category_id = " . intval($filterCategory);
if ($filterSearch !== '') $where .= " AND p.product_name LIKE '%" . $connection->real_escape_string($filterSearch) . "%'";

// Fetch total for pagination
$totalRows = $connection->query("SELECT COUNT(*) as count FROM products p $where")->fetch_assoc()['count'];
$totalPages = ceil($totalRows / $limit);

// Fetch brands & categories
$brands = $connection->query("SELECT brand_id, brand_name FROM brands");
$categories = $connection->query("SELECT category_id, category_name FROM categories");

// Fetch paginated products
$result = $connection->query("
  SELECT p.*, pi.image_url, b.brand_name, c.category_name
  FROM products p 
  LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_default = 1
  LEFT JOIN brands b ON p.brand_id = b.brand_id
  LEFT JOIN categories c ON p.category_id = c.category_id
  $where
  ORDER BY p.product_id DESC
  LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Tailwind CSS 
    Without this js sidebar and main content of this page not toggle and also menuToggle.js important -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>
  <style>
    #main{
      margin-top: 60px;
    }
    .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: .4rem; }
    .truncate { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    td input, td textarea { width: 100%; resize: vertical; }
    .editable:hover { background: #eef !important; cursor: pointer; }
    .alert-fixed {
      position: fixed;
      top: 10px;
      right: 10px;
      z-index: 9999;
      display: none;
    }
  </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white transition-colors duration-300">

<?php include('../includes/header.php'); ?>
  <?php include('../includes/sidebar.php'); ?>

  <!-- Main Content -->
  <div id="main" class="ml-60 transition-all duration-300 p-6">
  <div class="alert alert-success alert-fixed" id="successAlert">✅ Saved</div>

    <!-- <h1 class="text-2xl font-semibold mb-6">Welcome to the Admin Dashboard</h1> -->

    <main>
      <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold">🛍️ Manage Products</h3>
    <a href="add.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Add Product</a>
  </div>

  <!-- Filter -->
  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search product..." value="<?= htmlspecialchars($filterSearch) ?>"></div>
    <div class="col-md-3">
      <select name="brand" class="form-select">
        <option value="">All Brands</option>
        <?php while ($b = $brands->fetch_assoc()): ?>
          <option value="<?= $b['brand_id'] ?>" <?= $filterBrand == $b['brand_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['brand_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="category" class="form-select">
        <option value="">All Categories</option>
        <?php while ($c = $categories->fetch_assoc()): ?>
          <option value="<?= $c['category_id'] ?>" <?= $filterCategory == $c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
  </form>

  <!-- Table -->
  <div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-bordered align-middle text-center" id="productTable">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Image</th>
          <th>Name</th>
          <th>Brand</th>
          <th>Category</th>
          <th>Description</th>
          <th>Price (₹)</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['product_id'] ?></td>
          <td>
            <?php if ($row['image_url']): ?>
              <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($row['image_url']) ?>" class="product-img">
            <?php else: ?>
              <span class="text-muted">No Image</span>
            <?php endif; ?>
          </td>
          <td><input type="text" class="form-control form-control-sm update-field" data-id="<?= $row['product_id'] ?>" data-field="product_name" value="<?= htmlspecialchars($row['product_name']) ?>"></td>
          <td><?= htmlspecialchars($row['brand_name']) ?></td>
          <td><?= htmlspecialchars($row['category_name']) ?></td>
          <td><textarea class="form-control form-control-sm update-field" rows="2" data-id="<?= $row['product_id'] ?>" data-field="description"><?= htmlspecialchars($row['description']) ?></textarea></td>
          <td><input type="number" class="form-control form-control-sm update-field" data-id="<?= $row['product_id'] ?>" data-field="price" value="<?= $row['price'] ?>"></td>
          <td><input type="number" class="form-control form-control-sm update-field" data-id="<?= $row['product_id'] ?>" data-field="stock" value="<?= $row['stock'] ?>"></td>
          <td>
            <div class="form-check form-switch d-inline-block">
              <input class="form-check-input toggle-active" type="checkbox" data-id="<?= $row['product_id'] ?>" <?= $row['is_active'] ? 'checked' : '' ?>>
            </div>
          </td>
          <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
          <td>
            <a href="edit.php?id=<?= $row['product_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i></a>
            <a href="delete.php?id=<?= $row['product_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

    </main>
  </div>

  <script src="../assets/js/menuToggle.js"></script>
  <!-- JS -->
<script>
  const showAlert = (message = '✅ Saved') => {
    const alert = document.getElementById("successAlert");
    alert.innerText = message;
    alert.style.display = 'block';
    setTimeout(() => alert.style.display = 'none', 1500);
  };

  document.querySelectorAll('.update-field').forEach(input => {
    input.addEventListener('blur', function () {
      const id = this.dataset.id;
      const field = this.dataset.field;
      const value = this.value;

      fetch('update_field.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&field=${field}&value=${encodeURIComponent(value)}`
      })
      .then(res => res.text())
      .then(res => showAlert(res));
    });
  });

  document.querySelectorAll('.toggle-active').forEach(switchEl => {
    switchEl.addEventListener('change', function () {
      const id = this.dataset.id;
      const value = this.checked ? 1 : 0;

      fetch('update_field.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&field=is_active&value=${value}`
      })
      .then(res => res.text())
      .then(res => showAlert(res));
    });
  });
</script>
</body>
</html>

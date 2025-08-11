<?php
require_once '../includes/auth_check.php';
require_once '../config.php';
require_once '../includes/db_connections.php';

$page_title = 'Manage Products';

// Filters
$filterBrand = $_GET['brand'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

// WHERE conditions
$where = "WHERE 1";
if ($filterBrand !== '') $where .= " AND p.brand_id = " . intval($filterBrand);
if ($filterCategory !== '') $where .= " AND p.category_id = " . intval($filterCategory);
if ($filterSearch !== '') $where .= " AND p.product_name LIKE '%" . $connection->real_escape_string($filterSearch) . "%'";

// Total count
$totalRows = $connection->query("SELECT COUNT(*) as count FROM products p $where")->fetch_assoc()['count'];
$totalPages = ceil($totalRows / $limit);

// Data fetch
$brands = $connection->query("SELECT brand_id, brand_name FROM brands");
$categories = $connection->query("SELECT category_id, category_name FROM categories");

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
<?php include('../includes/head.php'); ?>
<style>
.alert-fixed {
  position: fixed;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: none;
  background: #d4edda;
  color: #155724;
  padding: 10px 20px;
  border-radius: 5px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
}
.product-card {
  background: var(--card-bg, #fff);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.product-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}
.product-card-content {
  flex: 1;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.product-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}
.product-meta {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.25rem;
}
.product-price {
  font-size: 1.1rem;
  font-weight: bold;
  color: #2563eb;
  margin: 0.5rem 0;
}
.product-stock {
  font-size: 0.9rem;
  margin-bottom: 0.50rem;
}
.switch-container {
  display: inline-block;
  position: relative;
}
.switch-container .switch-slider {
  width: 44px;
  height: 24px;
  background-color: #e5e7eb;
  border-radius: 9999px;
  position: relative;
  transition: background-color 0.3s;
}
.switch-container .switch-slider::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  transition: transform 0.3s;
}
.switch-container input:checked + .switch-slider {
  background-color: #22c55e;
}
.switch-container input:checked + .switch-slider::after {
  transform: translateX(20px);
}
.product-actions {
  display: flex;
  gap: 0.5rem;
}
.product-actions a {
  flex: 1;
  padding: 0.4rem;
  border-radius: 8px;
  text-align: center;
  font-size: 0.9rem;
}
.action-view { background: #e0f2fe; color: #0369a1; }
.action-edit { background: #fef9c3; color: #854d0e; }
.action-del { background: #fee2e2; color: #991b1b; }
@media (max-width: 767px) {
  .products-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}
</style>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100">

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div id="main" class="ml-60 transition-all p-6">

  <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
    <h1 class="text-2xl font-semibold">🛍️ Manage Products</h1>
    <div class="flex gap-2">
      <a href="export.php?<?= http_build_query(array_merge($_GET, ['type' => 'csv'])) ?>" 
        class="px-4 py-2 rounded-lg flex items-center gap-2 shadow-sm 
              border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 
              dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 
              transition-colors duration-200">
        <i class="fa-solid fa-file-csv text-green-600 dark:text-green-400"></i>
        CSV
      </a>
      <a href="add_product.php" 
         class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Add Product
      </a>
    </div>
  </div>

  <!-- Filters -->
  <form class="flex flex-wrap gap-2 mb-6" method="GET">
    <input type="text" name="search" placeholder="Search product..."
      value="<?= htmlspecialchars($filterSearch) ?>"
      class="flex-1 min-w-[160px] border px-3 py-2 rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200" />

    <select name="brand" class="flex-1 min-w-[140px] border px-3 py-2 rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">
      <option value="">All Brands</option>
      <?php while ($b = $brands->fetch_assoc()): ?>
        <option value="<?= $b['brand_id'] ?>" <?= $filterBrand == $b['brand_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['brand_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <select name="category" class="flex-1 min-w-[140px] border px-3 py-2 rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">
      <option value="">All Categories</option>
      <?php while ($c = $categories->fetch_assoc()): ?>
        <option value="<?= $c['category_id'] ?>" <?= $filterCategory == $c['category_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['category_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
      <i class="fa fa-search"></i> Filter
    </button>
  </form>

  <!-- Products Grid -->
  <div class="products-grid">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($product = $result->fetch_assoc()): ?>
        <div class="product-card dark:bg-gray-800 dark:border-gray-700">
          <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($product['image_url']) ?>"
               alt="<?= htmlspecialchars($product['product_name']) ?>">
          <div class="product-card-content">
            <div>
              <div class="product-title"><?= htmlspecialchars($product['product_name']) ?></div>
              <div class="product-meta"><?= htmlspecialchars($product['brand_name']) ?> • <?= htmlspecialchars($product['category_name']) ?></div>
              <div class="product-meta">Added on: <?= date('d M Y', strtotime($product['created_at'])) ?></div>
              <div class="switch-container">
                <label class="inline-flex items-center cursor-pointer">
                  <input type="checkbox" 
                        class="sr-only peer toggle-active" 
                        data-id="<?= $product['product_id'] ?>"
                        <?= $product['is_active'] == '1' ? 'checked' : '' ?>>
                  <div class="switch-slider"></div>
                </label>
              </div>
              <div class="product-price dark:text-blue-400">₹<?= htmlspecialchars($product['price']) ?></div>
              <div class="product-stock flex items-center gap-2 mt-1">
                <?php 
                  $stock = intval($product['stock']);
                  if ($stock > 5) {
                    $stockColor = 'bg-green-100 text-green-800';
                    $stockIcon  = 'fa-check-circle';
                    $stockLabel = 'In Stock';
                  } elseif ($stock > 0) {
                    $stockColor = 'bg-yellow-100 text-yellow-800';
                    $stockIcon  = 'fa-exclamation-circle';
                    $stockLabel = 'Low Stock';
                  } else {
                    $stockColor = 'bg-red-100 text-red-800';
                    $stockIcon  = 'fa-times-circle';
                    $stockLabel = 'Out of Stock';
                  }
                ?>
                <span class="flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $stockColor ?>">
                  <i class="fa <?= $stockIcon ?> mr-1"></i> 
                  <?= $stockLabel ?> (<?= $stock ?>)
                </span>
              </div>
            </div>
            <div class="product-actions">
              <a href="view.php?id=<?= $product['product_id'] ?>" class="action-view dark:bg-blue-900 dark:text-blue-300"><i class="fa-solid fa-eye"></i></a>
              <a href="edit.php?id=<?= $product['product_id'] ?>" class="action-edit dark:bg-yellow-900 dark:text-yellow-300"><i class="fa-solid fa-pen"></i></a>
              <a href="delete.php?id=<?= $product['product_id'] ?>" onclick="return confirm('Delete this product?')" class="action-del dark:bg-red-900 dark:text-red-300"><i class="fa-solid fa-trash"></i></a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="col-span-full text-center text-gray-500">No products found.</p>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <nav class="mt-6 flex justify-center gap-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
         class="px-3 py-1 rounded-md border <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-black' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </nav>

</div>
<script src="../assets/js/menuToggle.js"></script>
<script>
function showAlert(message) {
  const alertBox = document.createElement('div');
  alertBox.className = 'alert-fixed';
  alertBox.textContent = message;
  document.body.appendChild(alertBox);
  alertBox.style.display = 'block';
  setTimeout(() => {
    alertBox.style.display = 'none';
    document.body.removeChild(alertBox);
  }, 3000);
}

// Toggle active state
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
    .then(() => {
      if (value === 1) {
        showAlert('Status is active');
      } else {
        showAlert('Status is inactive');
      }
    });
  });
});
</script>
</body>
</html>

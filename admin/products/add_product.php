<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';
require_once '../config.php';

$errors = [];
$success = false;

// Fetch dropdown data
$brands = $connection->query("SELECT * FROM brands");
$categories = $connection->query("SELECT * FROM categories");
$sizes = $connection->query("SELECT * FROM sizes");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $brand_id    = intval($_POST['brand']);
    $category_id = intval($_POST['category']);
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $selectedSizes = $_POST['sizes'] ?? [];
$sizeStocks = $_POST['size_stock'] ?? [];

// Validate size stocks
foreach ($selectedSizes as $size_id) {
    if (!isset($sizeStocks[$size_id]) || $sizeStocks[$size_id] === '') {
        $errors[] = "Stock must be entered for selected size ID: $size_id.";
    } elseif (!is_numeric($sizeStocks[$size_id]) || $sizeStocks[$size_id] < 0) {
        $errors[] = "Stock for size ID $size_id must be a number and not negative.";
    }
}

    $defaultImageIndex = intval($_POST['default_image'] ?? 0);

    // Validate price and stock
    if ($price < 0) $errors[] = "Price cannot be negative.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";

    // Validate image count
    $imageCount = count($_FILES['images']['name']);
    if ($imageCount < 4 || $imageCount > 7) {
        $errors[] = "You must upload between 4 to 7 images.";
    }

    // Check if product already exists
    $checkStmt = $connection->prepare("SELECT product_id FROM products WHERE product_name = ? AND brand_id = ? AND category_id = ?");
    $checkStmt->bind_param("sii", $name, $brand_id, $category_id);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors[] = "This product already exists in the selected brand and category.";
    }
    $checkStmt->close();

    if (empty($errors)) {
        // Insert product
        $stmt = $connection->prepare("INSERT INTO products (product_name, brand_id, category_id, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiids", $name, $brand_id, $category_id, $price, $stock, $description);

        if ($stmt->execute()) {
            $productId = $connection->insert_id;

            // Upload and insert images
            foreach ($_FILES['images']['tmp_name'] as $i => $tmpPath) {
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $imageName = ($i === $defaultImageIndex)
                    ? "{$productId}.{$ext}"
                    : "{$productId}-" . ($i + 1) . ".{$ext}";

                $targetPath = "../uploads/products/" . $imageName;
                move_uploaded_file($tmpPath, $targetPath);

                $isDefault = ($i === $defaultImageIndex) ? 1 : 0;

                $stmtImg = $connection->prepare("INSERT INTO product_images (product_id, image_url, is_default) VALUES (?, ?, ?)");
                $stmtImg->bind_param("isi", $productId, $imageName, $isDefault);
                $stmtImg->execute();
            }

            // Insert product sizes and their stock
foreach ($selectedSizes as $size_id) {
    $size_stock = intval($sizeStocks[$size_id]);
    $stmtSize = $connection->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
    $stmtSize->bind_param("iii", $productId, $size_id, $size_stock);
    $stmtSize->execute();
}



            $success = true;
        } else {
            $errors[] = "Failed to insert product.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Product - Footwear Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f9f9f9;
    }
    .form-section {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .preview-img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border: 1px solid #ccc;
    }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="form-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">➕ Add New Product</h3>
      <a href="list.php" class="btn btn-outline-secondary btn-sm">← Back to List</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php elseif ($success): ?>
      <div class="alert alert-success">✅ Product successfully added!</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3 form-floating">
        <input type="text" name="name" id="name" class="form-control" placeholder="Product Name" required>
        <label for="name">Product Name</label>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating mb-3">
            <select name="brand" class="form-select" id="brand" required>
              <option value="">-- Select Brand --</option>
              <?php while ($b = $brands->fetch_assoc()): ?>
                <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
              <?php endwhile; ?>
            </select>
            <label for="brand">Brand</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating mb-3">
            <select name="category" class="form-select" id="category" required>
              <option value="">-- Select Category --</option>
              <?php while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
              <?php endwhile; ?>
            </select>
            <label for="category">Category</label>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating mb-3">
            <input type="number" step="0.01" name="price" id="price" class="form-control" placeholder="Price" required>
            <label for="price">Price (₹)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating mb-3">
            <input type="number" name="stock" id="stock" class="form-control" placeholder="Stock" required>
            <label for="stock">Total Stock</label>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Available Sizes & Stock</label>
        <div class="row">
          <?php while ($s = $sizes->fetch_assoc()): ?>
            <div class="col-md-4 mb-2">
              <div class="form-check d-flex align-items-center">
                <input class="form-check-input me-2" type="checkbox" id="size_<?= $s['size_id'] ?>" name="sizes[]" value="<?= $s['size_id'] ?>" onchange="toggleStockInput(this)">
                <label class="form-check-label me-2" for="size_<?= $s['size_id'] ?>"><?= htmlspecialchars($s['size_value']) ?></label>
                <input type="number" name="size_stock[<?= $s['size_id'] ?>]" class="form-control form-control-sm" placeholder="Qty" min="0" style="width: 80px;" disabled>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Product Images (4–7)</label>
        <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple required>
        <div id="preview-container" class="mt-3 row"></div>
      </div>

      <div class="mb-3 form-floating">
        <textarea name="description" class="form-control" placeholder="Product description" id="description" style="height: 100px;"></textarea>
        <label for="description">Description</label>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">🚀 Add Product</button>
    </form>
  </div>
</div>

<script>
  // Include Bootstrap JS for better styling -->
  // for images preview -->
  document.getElementById('images').addEventListener('change', function (e) {
    const files = e.target.files;
    const container = document.getElementById('preview-container');
    container.innerHTML = '';

    if (files.length < 4 || files.length > 7) {
      alert("Upload between 4 and 7 images.");
      e.target.value = '';
      return;
    }

    [...files].forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = function (event) {
        const col = document.createElement('div');
        col.className = 'col-md-3 mb-2 d-flex align-items-center gap-2';
        col.innerHTML = `
          <img src="${event.target.result}" class="preview-img" />
          <label class="form-check-label">
            <input type="radio" name="default_image" value="${index}" ${index === 0 ? 'checked' : ''}> Default
          </label>
        `;
        container.appendChild(col);
      };
      reader.readAsDataURL(file);
    });
  });

  // Toggle stock input based on checkbox -->
  // This script enables/disables the stock input field based on the checkbox state -->
  // for each size -->
  function toggleStockInput(checkbox) {
    const stockInput = checkbox.closest('.form-check').querySelector('input[type="number"]');
    stockInput.disabled = !checkbox.checked;
    if (!checkbox.checked) stockInput.value = '';
  }

  // Form validation for stock inputs -->
  // This script checks if the stock input for selected sizes is valid before form submission -->
  document.querySelector('form').addEventListener('submit', function (e) {
    let isValid = true;
    document.querySelectorAll('input[type="checkbox"][name^="sizes"]').forEach(checkbox => {
      if (checkbox.checked) {
        const stockInput = checkbox.closest('.form-check').querySelector('input[type="number"]');
        if (stockInput.value.trim() === '' || parseInt(stockInput.value) < 0) {
          stockInput.classList.add('is-invalid');
          isValid = false;
        } else {
          stockInput.classList.remove('is-invalid');
        }
      }
    });
    if (!isValid) {
      alert('Please enter valid stock for selected sizes.');
      e.preventDefault();
    }
  });
</script>
</body>
</html>

 


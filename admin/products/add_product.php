<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';
require_once '../config.php';

$errors = [];
$success = false;

// Fetch dropdown data (fail gracefully)
$brands     = $connection->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name") ?: new ArrayObject([]);
$categories = $connection->query("SELECT category_id, category_name FROM categories ORDER BY category_name") ?: new ArrayObject([]);
$sizes      = $connection->query("SELECT size_id, size_value FROM sizes ORDER BY size_value") ?: new ArrayObject([]);

// Helper to safely read POST vars
function post($key, $default = '') { return isset($_POST[$key]) ? trim($_POST[$key]) : $default; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = post('name');
    $brand_id      = intval(post('brand'));
    $category_id   = intval(post('category'));
    $cost_price    = is_numeric(post('cost_price')) ? floatval(post('cost_price')) : -1;
    $profit_price         = is_numeric(post('profit_price')) ? floatval(post('profit_price')) : -1;
    $stock         = is_numeric(post('stock')) ? intval(post('stock')) : -1;
    $description   = post('description');
    $selectedSizes = $_POST['sizes'] ?? [];
    $sizeStocks    = $_POST['size_stock'] ?? [];
    $defaultImageIndex = intval($_POST['default_image'] ?? 0);

    $total_price = $cost_price + $profit_price;

    // Validate basics
    if ($name === '') { $errors[] = 'Product name is required.'; }
    if ($brand_id <= 0) { $errors[] = 'Please select a brand.'; }
    if ($category_id <= 0) { $errors[] = 'Please select a category.'; }
    if ($cost_price < 0) { $errors[] = 'Cost price must be a positive number.'; }
    if (!($profit_price > 0 && $profit_price > $cost_price)) { $errors[] = 'Price must be positive and greater than cost price.'; }
    if ($stock < 0) { $errors[] = 'Total stock must be a positive number.'; }

    // Validate size stocks
    foreach ($selectedSizes as $size_id) {
        if (!isset($sizeStocks[$size_id]) || $sizeStocks[$size_id] === '') {
            $errors[] = "Stock must be entered for selected size ID: $size_id.";
        } elseif (!is_numeric($sizeStocks[$size_id]) || $sizeStocks[$size_id] < 0) {
            $errors[] = "Stock for size ID $size_id must be a number and not negative.";
        }
    }

    // Validate images
    $imageCount = isset($_FILES['images']['name']) ? count(array_filter($_FILES['images']['name'])) : 0;
    if ($imageCount < 4 || $imageCount > 7) {
        $errors[] = 'You must upload between 4 to 7 images.';
    }

    // Duplicate check
    $checkStmt = $connection->prepare("SELECT product_id FROM products WHERE product_name = ? AND brand_id = ? AND category_id = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("sii", $name, $brand_id, $category_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) { $errors[] = 'This product already exists in the selected brand and category.'; }
        $checkStmt->close();
    }

    if (empty($errors)) {
        // Insert product
        $stmt = $connection->prepare("INSERT INTO products (product_name, brand_id, category_id, cost_price, profit_price, selling_price, stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = 'Failed to prepare product insert.';
        } else {
            $stmt->bind_param("siiiiids", $name, $brand_id, $category_id, $cost_price, $profit_price, $total_price, $stock, $description);
            if ($stmt->execute()) {
                $productId = $connection->insert_id;

                // Upload and insert images
                $allowed = ['jpg','jpeg','png','webp'];
                $uploadDir = realpath(__DIR__ . '/../uploads/products');
                if (!$uploadDir) { $errors[] = 'Upload directory not found.'; }

                foreach ($_FILES['images']['tmp_name'] as $i => $tmpPath) {
                    if (!is_uploaded_file($tmpPath)) continue;
                    $origName = $_FILES['images']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed, true)) { $errors[] = "Unsupported image type: $origName"; continue; }

                    $imageName = ($i === $defaultImageIndex)
                        ? "{$productId}.{$ext}"
                        : "{$productId}-" . ($i + 1) . ".{$ext}";

                    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
                    if (!@move_uploaded_file($tmpPath, $targetPath)) {
                        $errors[] = "Failed to upload image: $origName";
                        continue;
                    }

                    $isDefault = ($i === $defaultImageIndex) ? 1 : 0;
                    $stmtImg = $connection->prepare("INSERT INTO product_images (product_id, image_url, is_default) VALUES (?, ?, ?)");
                    if ($stmtImg) {
                        $stmtImg->bind_param("isi", $productId, $imageName, $isDefault);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }

                // Insert product sizes
                foreach ($selectedSizes as $size_id) {
                    $size_stock = intval($sizeStocks[$size_id]);
                    $stmtSize = $connection->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
                    if ($stmtSize) {
                        $stmtSize->bind_param("iii", $productId, $size_id, $size_stock);
                        $stmtSize->execute();
                        $stmtSize->close();
                    }
                }

                if (empty($errors)) { $success = true; }
            } else {
                $errors[] = '❌ Failed to add product!';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Add Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    :root{
      --bg:#f5f7fb; --card:#fff; --text:#0f172a; --muted:#475569; --border:#e2e8f0;
      --input:#fff; --input-border:#cbd5e1; --primary:#4f46e5; --primary-contrast:#fff;
      --accent:#22c55e; --danger:#ef4444; --shadow:0 8px 24px rgba(2,6,23,.06);
    }
    [data-theme="dark"]{
      --bg:#0f1115; --card:#151922; --text:#e5e7eb; --muted:#9aa4b2; --border:#2a2f3a;
      --input:#1a1f2b; --input-border:#374151; --primary:#6366f1; --primary-contrast:#fff;
      --accent:#22c55e; --danger:#f87171; --shadow:0 12px 28px rgba(0,0,0,.45);
    }
    html,body{height:100%}
    body{background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;}

    /* General styles */
    .card-elevated{background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow);}
    .section-title{font-weight:700; letter-spacing:.2px;}
    .form-floating>.form-control,.form-floating>.form-select,textarea.form-control,input.form-control{
      background:var(--input); border-color:var(--input-border); color:var(--text);}
    .form-control:focus,.form-select:focus{box-shadow:0 0 0 .25rem rgba(79,70,229,.15); border-color:var(--primary);}
    .quentity_label{height:auto; width:15px;}
    .btn-primary{background:var(--primary); border-color:var(--primary); color:var(--primary-contrast);}
    .btn-outline-secondary{color:var(--text); border-color:var(--input-border);}
    .alert-success{background: color-mix(in oklab, var(--accent) 18%, var(--card)); color:var(--text);
      border-color: color-mix(in oklab, var(--accent) 40%, var(--card));}
    .alert-danger{background: color-mix(in oklab, var(--danger) 12%, var(--card)); color:var(--text);
      border-color: color-mix(in oklab, var(--danger) 40%, var(--card));}
    .req::after{content:' *'; color:var(--danger);}

    /* Upload previews */
    .dropzone{border:2px dashed var(--input-border); background: color-mix(in oklab, var(--input) 92%, transparent);
      border-radius:14px; padding:18px; text-align:center; cursor:pointer; transition:border .2s ease;}
    .dropzone.dragover{border-color:var(--primary);}
    .preview-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:12px;}
    @media (min-width:576px){.preview-grid{grid-template-columns:repeat(3,1fr);}}
    @media (min-width:992px){.preview-grid{grid-template-columns:repeat(5,1fr);}}
    .preview-tile{position:relative; border:1px solid var(--border); border-radius:12px; overflow:hidden; background:var(--input);}
    .preview-tile img{display:block; width:100%; aspect-ratio:1/1; object-fit:cover;}
    .preview-meta{display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px;}
    .default-chip{font-size:.75rem; padding:2px 8px; border-radius:999px; border:1px solid var(--input-border);}
    .help{color:var(--muted); font-size:.875rem;}
  </style>
</head>
<body>

<?php
  // Provide a subtitle to the header and include it
  $page_subtitle = 'Add Product';
  include ('../includes/sub_header.php');
?>

<main class="container my-4 my-lg-5">
  <div class="card-elevated p-3 p-md-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
      <h2 class="section-title m-0">Add New Product</h2>
      <div class="help">Fill the details below. Images: <strong>4–7</strong>, choose one as default.</div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" id="msg-alert" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php elseif ($success): ?>
      <div class="alert alert-success" id="msg-alert" role="alert">✅ Product successfully added!</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="productForm" novalidate>
      <!-- Basic info -->
      <div class="row g-3">
        <div class="col-12">
          <div class="form-floating">
            <input type="text" name="name" id="name" class="form-control" placeholder="Product Name" required />
            <label for="name" class="req">Product Name</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <select name="brand" id="brand" class="form-select" required>
              <option value="">-- Select Brand --</option>
              <?php if ($brands instanceof mysqli_result): while ($b = $brands->fetch_assoc()): ?>
                <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
              <?php endwhile; endif; ?>
            </select>
            <label for="brand" class="req">Brand<span class="req"></span></label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <select name="category" id="category" class="form-select" required>
              <option value="">-- Select Category --</option>
              <?php if ($categories instanceof mysqli_result): while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
              <?php endwhile; endif; ?>
            </select>
            <label for="category" class="req">Category<span class="req"></span></label>
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="form-floating">
            <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control" placeholder="Cost Price" required />
            <label for="cost_price" class="req">Cost Price (₹)</label>
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="form-floating">
            <input type="number" step="0.01" name="profit_price" id="profit_price" class="form-control" placeholder="Profit Price" required />
            <label for="profit_price" class="req">Profit Price (₹)</label>
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="form-floating">
            <input type="number" step="0.01" id="total_price" class="form-control" placeholder="Total Price" readonly />
            <label for="total_price">Total Price (₹)</label>
          </div>
          <div class="form-text">Auto Total price of cost & selling price is suggested.</div>
        </div>


        <div class="col-sm-6 col-lg-3">
          <div class="form-floating">
            <input type="number" name="stock" id="stock" class="form-control" placeholder="Total Stock" required />
            <label for="stock" class="req">Total Stock</label>
          </div>
          <div class="form-text">Auto-sum of size stocks is suggested.</div>
        </div>
      </div>

      <hr class="my-4" />

      <!-- Sizes & stock -->
      <div class="mb-3">
        <label class="form-label fw-semibold req">Available Sizes & Stock</label>
        <div class="row" id="sizesContainer">
          <?php if ($sizes instanceof mysqli_result):
            $count = 0;
            while ($s = $sizes->fetch_assoc()):
            $count++;
          ?>
          <div class="col-12 col-sm-4 mb-2">
            <div class="form-check d-flex align-items-center gap-2">
              <input class="form-check-input mt-0" type="checkbox"
                      id="size_<?= $s['size_id'] ?>" name="sizes[]"
                      value="<?= $s['size_id'] ?>" onchange="toggleStockInput(this)" />
              <div class="quentity_label form-check-label d-flex align-items-center gap-2">
                <label class="form-check-label"
                      for="size_<?= $s['size_id'] ?>"><?= htmlspecialchars($s['size_value']) ?></label>
              </div>
              <input type="number" inputmode="numeric"
                      name="size_stock[<?= $s['size_id'] ?>]"
                      class="form-control form-control-sm" placeholder="Qty"
                      min="0" style="width: 90px;" disabled oninput="syncTotalStock()" />
            </div>
          </div>
          <?php
            if ($count % 3 === 0) echo '<div class="w-100 d-none d-sm-block"></div>'; // line break after 3
            endwhile;
            endif; 
          ?>
        </div>
      </div>

      <hr class="my-4" />

      <!-- Images -->
      <div class="mb-3">
        <label for="images" class="req form-label fw-semibold">Product Images (4–7)</label>
        <div id="dropzone" class="dropzone" tabindex="0" role="button" aria-label="Upload product images">
          <div><i class="bi bi-cloud-arrow-up" style="font-size:1.5rem"></i></div>
          <div class="mt-1">Drag & drop images here or click to browse</div>
          <div class="help">Accepted: JPG, PNG, WEBP</div>
        </div>
        <input type="file" id="images" name="images[]" class="form-control mt-2" accept="image/*" multiple required hidden />
        <div id="preview-container" class="preview-grid mt-3"></div>
      </div>

      <hr class="my-4" />

      <!-- Description -->
      <div class="mb-4">
        <div class="form-floating">
          <textarea name="description" id="description" class="form-control" placeholder="Product description" style="height: 120px;"></textarea>
          <label for="description" class="req">Description</label>
        </div>
      </div>

      <div class="d-grid d-sm-flex gap-2">
        <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-rocket-takeoff"></i> Add Product</button>
        <button type="reset" class="btn btn-outline-secondary px-4 py-2"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
      </div>
    </form>
  </div>
</main>

<script>

// Alert message hide after 2 sec
setTimeout(() => {
  const msg = document.getElementById('msg-alert');
  if(msg){
    msg.style.transition = "opacity 0.5s ease";
    msg.style.opacity = "0";
    setTimeout(() => msg.remove(), 500); // Remove after fade out
  }
},2000);


// Update total price on cost/price change
document.getElementById('cost_price').addEventListener('input', updateTotalPrice);
document.getElementById('profit_price').addEventListener('input', updateTotalPrice);

function updateTotalPrice(){
    const cost = parseFloat(document.getElementById('cost_price').value) || 0;
    const profit = parseFloat(document.getElementById('profit_price').value) || 0;
    document.getElementById('total_price').value = (cost + profit).toFixed(2);
}

// Sizes stock enable/disable + total stock sync
function toggleStockInput(checkbox) {
  const stockInput = checkbox.closest('.form-check').querySelector('input[type="number"]');
  stockInput.disabled = !checkbox.checked;
  if (!checkbox.checked) { stockInput.value = ''; }
  syncTotalStock();
}

window.syncTotalStock = function(){
  const inputs = document.querySelectorAll('#sizesContainer input[type="number"]:not(:disabled)');
  let sum = 0; inputs.forEach(i => { const v = parseInt(i.value, 10); if (!isNaN(v) && v >= 0) sum += v; });
  const total = document.getElementById('stock');
  if (sum > 0) total.value = sum;
}

// File input + drag & drop
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('images');
const preview = document.getElementById('preview-container');

dropzone.addEventListener('click', () => fileInput.click());
dropzone.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
});

['dragenter','dragover'].forEach(evt =>
  dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('dragover'); })
);
['dragleave','drop'].forEach(evt =>
  dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('dragover'); })
);

dropzone.addEventListener('drop', (e) => { handleFiles(e.dataTransfer.files); fileInput.files = e.dataTransfer.files; });
fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

function handleFiles(fileList){
  const files = Array.from(fileList);
  preview.innerHTML = '';

  if (files.length < 4 || files.length > 7) {
    alert('Upload between 4 and 7 images.');
    fileInput.value = '';
    return;
  }

  files.forEach((file, index) => {
    const url = URL.createObjectURL(file);
    const tile = document.createElement('div');
    tile.className = 'preview-tile';
    tile.innerHTML = `
      <img src="${url}" alt="preview-${index}" />
      <div class="preview-meta">
        <label class="d-inline-flex align-items-center gap-1">
          <input type="radio" name="default_image" value="${index}" ${index === 0 ? 'checked' : ''} />
          <span class="default-chip">Default</span>
        </label>
        <small class="text-secondary">${escapeHtml(file.name)}</small>
      </div>
    `;
    preview.appendChild(tile);
    tile.querySelector('img').addEventListener('load', () => URL.revokeObjectURL(url));
  });
}

function escapeHtml(str){ return str.replace(/[&<>'"]/g, (c)=>({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;","\"":"&quot;"}[c])); }

// Form validation for size stocks + images
document.getElementById('productForm').addEventListener('submit', function(e){
  let isValid = true;
  document.querySelectorAll('input[type="checkbox"][name^="sizes"]').forEach(cb => {
    if (cb.checked) {
      const stockInput = cb.closest('.form-check').querySelector('input[type="number"]');
      const v = stockInput.value.trim();
      if (v === '' || isNaN(v) || parseInt(v, 10) < 0) {
        stockInput.classList.add('is-invalid');
        isValid = false;
      } else {
        stockInput.classList.remove('is-invalid');
      }
    }
  });
  if (!document.getElementsByName('images[]')[0].files.length) {
    alert('Please upload 4–7 images.');
    isValid = false;
  }
  if (!isValid) e.preventDefault();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

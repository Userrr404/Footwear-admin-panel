<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$id = intval($_GET['id']);
if ($id <= 0) die("Invalid Product ID");

$product = $connection->query("SELECT * FROM products WHERE product_id=$id")->fetch_assoc();
if (!$product) die("Product not found");

$brands = $connection->query("SELECT * FROM brands");
$categories = $connection->query("SELECT * FROM categories");
$sizes = $connection->query("SELECT * FROM sizes")->fetch_all(MYSQLI_ASSOC);

$product_sizes_result = $connection->query("SELECT * FROM product_sizes WHERE product_id=$id");
$product_sizes = [];
while ($row = $product_sizes_result->fetch_assoc()) {
    $product_sizes[$row['size_id']] = $row['stock'];
}

$product_images = $connection->query("SELECT * FROM product_images WHERE product_id=$id")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['set_default'])) {
    $imgFile = basename($_GET['set_default']);
    $connection->query("UPDATE product_images SET is_default = 0 WHERE product_id = $id");
    $connection->query("UPDATE product_images SET is_default = 1 WHERE product_id = $id AND image_url = '" . $connection->real_escape_string($imgFile) . "'");
    header("Location: edit.php?id=$id&success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Edit Product</title>
  <meta charset="UTF-8" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .editable:hover { cursor: pointer; background: #f8f9fa; }
    .form-inline-edit input { width: 100%; }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0">🛠 Edit Product (ID: <?= $id ?>)</h5>
    </div>
    <div class="card-body">

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-4" id="editTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#generalTab" type="button" role="tab">General</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="sizes-tab" data-bs-toggle="tab" data-bs-target="#sizesTab" type="button" role="tab">Sizes</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#imagesTab" type="button" role="tab">Images</button>
        </li>
      </ul>

      <div class="tab-content" id="editTabContent">

        <!-- GENERAL TAB -->
        <div class="tab-pane fade show active" id="generalTab" role="tabpanel">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label>Product Name</label>
              <p class="editable border p-2" data-field="product_name"><?= htmlspecialchars($product['product_name']) ?></p>
            </div>
            <div class="col-md-3">
              <label>Price (₹)</label>
              <p class="editable border p-2" data-field="price"><?= $product['price'] ?></p>
            </div>
            <div class="col-md-3">
              <label>Total Stock</label>
              <p class="editable border p-2" data-field="stock"><?= $product['stock'] ?></p>
            </div>
          </div>
          <div class="mb-3">
            <label>Description</label>
            <p class="editable border p-2" data-field="description"><?= htmlspecialchars($product['description']) ?></p>
          </div>
        </div>

        <!-- SIZES TAB -->
        <div class="tab-pane fade" id="sizesTab" role="tabpanel">
          <form method="POST" action="update_sizes.php" class="mt-3">
            <div class="row">
              <?php foreach ($sizes as $s): ?>
                <div class="col-md-3 mb-2 d-flex align-items-center">
                  <input type="checkbox" class="me-2" name="sizes[]" value="<?= $s['size_id'] ?>" <?= isset($product_sizes[$s['size_id']]) ? 'checked' : '' ?> />
                  <label class="me-2"><?= htmlspecialchars($s['size_value']) ?></label>
                  <input type="number" name="size_stock[<?= $s['size_id'] ?>]" class="form-control form-control-sm"
                         value="<?= $product_sizes[$s['size_id']] ?? '' ?>" placeholder="Stock" />
                </div>
              <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-sm btn-primary mt-2">💾 Save Sizes</button>
          </form>
        </div>

        <!-- IMAGES TAB -->
        <div class="tab-pane fade" id="imagesTab" role="tabpanel">
          <div class="accordion mt-3" id="imageAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingUpload">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpload">Upload New</button>
              </h2>
              <div id="collapseUpload" class="accordion-collapse collapse show">
                <div class="accordion-body">
                  <form method="POST" action="upload_images.php" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="file" name="images[]" class="form-control mb-2" multiple accept="image/*">
                    <button type="submit" class="btn btn-sm btn-primary">📤 Upload</button>
                  </form>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="headingList">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseList">Manage Images</button>
              </h2>
              <div id="collapseList" class="accordion-collapse collapse">
                <!-- <input type="file" name="images[]" id="imageUpload" class="form-control mb-2" multiple accept="image/*" onchange="previewImages(event)">
<div class="row mt-2" id="previewContainer"></div> -->

                <div class="accordion-body">
                  <div class="row">
                    <?php foreach ($product_images as $img): ?>
                      <div class="col-md-2 text-center mb-3">
                        <img src="../uploads/products/<?= htmlspecialchars($img['image_url']) ?>" class="img-thumbnail" style="height:100px;object-fit:cover">
                        <?php if (!$img['is_default']): ?>
                          <a href="edit.php?id=<?= $id ?>&set_default=<?= urlencode($img['image_url']) ?>" class="btn btn-sm btn-outline-primary w-100 my-1">Set Default</a>
                        <?php else: ?>
                          <span class="badge bg-success d-block my-1">Default</span>
                        <?php endif; ?>
                        <a href="delete_image.php?pid=<?= $id ?>&img=<?= urlencode($img['image_url']) ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete image?')">Delete</a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div> <!-- accordion -->
        </div>

      </div> <!-- tab content -->
      <div class="text-end mt-4">
  <a href="list.php" class="btn btn-outline-secondary">← Back to Product List</a>
</div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Inline Edit with AJAX
document.querySelectorAll('.editable').forEach(el => {
  el.addEventListener('click', function () {
    const original = this.innerText;
    const field = this.getAttribute('data-field');
    const input = document.createElement('input');
    input.type = 'text';
    input.value = original;
    input.className = 'form-control form-control-sm';
    this.innerHTML = '';
    this.appendChild(input);
    input.focus();

    input.addEventListener('blur', () => {
      const newValue = input.value;
      if (newValue !== original) {
        fetch('update_inline.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `id=<?= $id ?>&field=${field}&value=${encodeURIComponent(newValue)}`
        }).then(res => res.text()).then(res => {
          if (res === 'OK') {
            el.innerText = newValue;
          } else {
            alert('Update failed');
            el.innerText = original;
          }
        });
      } else {
        el.innerText = original;
      }
    });
  });
});
</script>

<!-- Image Preview Script -->
<!-- <script>
function previewImages(event) {
  const files = event.target.files;
  const container = document.getElementById('previewContainer');
  container.innerHTML = '';
  Array.from(files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const col = document.createElement('div');
      col.className = 'col-md-2 col-4 mb-3';
      col.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="height:100px; object-fit:cover;">`;
      container.appendChild(col);
    };
    reader.readAsDataURL(file);
  });
}
</script> -->

</body>
</html>

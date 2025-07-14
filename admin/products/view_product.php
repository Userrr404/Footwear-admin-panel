<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Product ID");
}

$product_id = intval($_GET['id']);

// Fetch product with brand
$stmt = $connection->prepare("
    SELECT p.*, b.brand_name 
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Fetch images
$imgStmt = $connection->prepare("SELECT * FROM product_images WHERE product_id = ?");
$imgStmt->bind_param("i", $product_id);
$imgStmt->execute();
$images = $imgStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>View Product - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h3 class="mb-4"><?= htmlspecialchars($product['product_name']) ?> Details</h3>
<a href="list.php" class="btn btn-secondary mb-3">← Back to Product List</a>

<!-- Main Product Card -->
<div class="card shadow">
  <div class="card-header bg-primary text-white">
    <strong>Product ID:</strong> <?= $product['product_id'] ?>
  </div>
  <div class="card-body row">
    <div class="col-md-4">
      <?php
        $primaryImg = null;
        foreach ($images as $img) {
          if ($img['is_default']) {
            $primaryImg = $img['image_url'];
            break;
          }
        }
      ?>
      <img src="../uploads/products/<?= $primaryImg ?? 'no-image.png' ?>" 
           class="img-fluid border rounded" alt="Product Image">
    </div>
    <div class="col-md-8">
      <p><strong>Name:</strong> <?= htmlspecialchars($product['product_name']) ?></p>
      <p><strong>Price:</strong> ₹<?= number_format($product['price'], 2) ?></p>
      <p><strong>Stock:</strong> <?= $product['stock'] ?></p>
      <p><strong>Brand:</strong> <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></p>
      <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    </div>
  </div>
</div>

<!-- All Images -->
<h5 class="mt-4">All Images</h5>
<div class="row">
  <?php
    $imgStmt->execute(); // re-run to reset result pointer
    $images = $imgStmt->get_result();
    foreach ($images as $img):
  ?>
    <div class="col-md-3 mb-3">
      <img src="../uploads/products/<?= htmlspecialchars($img['image_url']) ?>" 
           class="img-thumbnail" alt="Image">
      <p class="text-center mt-1">
        <?= $img['is_default'] ? '<span class="badge bg-success">Primary</span>' : '' ?>
      </p>
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>

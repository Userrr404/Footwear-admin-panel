<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';
require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Product ID");
}
$product_id = intval($_GET['id']);

// Fetch product
$stmt = $connection->prepare("
    SELECT p.*, 
           b.brand_name, 
           c.category_name,
           (p.stock - p.stock_hold) AS available_stock
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Sizes
$stmt = $connection->prepare("
    SELECT s.size_value, ps.stock 
    FROM product_sizes ps
    INNER JOIN sizes s ON ps.size_id = s.size_id 
    WHERE product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$sizes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Images
$imgStmt = $connection->prepare("SELECT * FROM product_images WHERE product_id = ?");
$imgStmt->bind_param("i", $product_id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Primary Image
$primaryImg = 'no-image.png';
foreach ($images as $img) {
    if ($img['is_default']) {
        $primaryImg = $img['image_url'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

    <?php require_once '../includes/head.php'; ?>
    <style>
        .product-img-main {
            max-height: 400px;
            object-fit: contain;
            background-color: #1a1a1a;
        }
        .img-thumb {
            object-fit: cover;
            border-radius: 8px;
        }
        .size-badge {
            margin: 2px;
            font-size: 0.85rem;
        }
    </style>

<body>
<?php require_once '../includes/sub_header.php'; ?>

<main class="container my-4">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-box"></i> Product #<?= $product['product_id'] ?>
        </div>
        <div class="card-body row g-4">
            <!-- Left: Image -->
            <div class="col-12 col-lg-4 text-center">
                <img src="../uploads/products/<?= htmlspecialchars($primaryImg) ?>" 
                     alt="Product Image" class="img-fluid rounded product-img-main">
            </div>
            <!-- Right: Info -->
            <div class="col-12 col-lg-8">
                <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                <p><span class="badge bg-info"><i class="fas fa-tag"></i> <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></span>
                   <span class="badge bg-secondary"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></span></p>

                <p><strong>Cost Price:</strong> ₹<?= number_format($product['cost_price'], 2) ?></p>
                <p><strong>Profit Price:</strong> ₹<?= number_format($product['profit_price'], 2) ?></p>
                <p><strong>Selling Price:</strong> ₹<?= number_format($product['selling_price'], 2) ?></p>

                <p>
                    <strong>Sizes & Stock:</strong><br>
                    <?php if (empty($sizes)): ?>
                        <span class="text-muted">No sizes available</span>
                    <?php else: ?>
                        <?php foreach ($sizes as $s): ?>
                            <span class="badge bg-dark size-badge">
                                <?= htmlspecialchars($s['size_value']) ?>: <?= $s['stock'] ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </p>

                <p><strong>Total Stock:</strong> <?= $product['stock'] ?></p>
                <p><strong>Available Stock:</strong> <?= $product['available_stock'] ?></p>
                <p><strong>Stock on Hold:</strong> <?= $product['stock_hold'] ?></p>

                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                <p><small class="text-muted">Added On: <?= htmlspecialchars($product['created_at']) ?></small></p>
            </div>
        </div>
    </div>

    <!-- Image Gallery -->
    <h5 class="mt-4">All Images</h5>
    <div class="row row-cols-2 row-cols-md-4 g-3">
        <?php foreach ($images as $img): ?>
            <div class="col">
                <img src="../uploads/products/<?= htmlspecialchars($img['image_url']) ?>" 
                     class="w-100 img-thumb border" alt="Image">
                <?php if ($img['is_default']): ?>
                    <span class="badge bg-success mt-1">Primary</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
</main>

</body>
</html>

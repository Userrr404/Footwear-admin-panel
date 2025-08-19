<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "ERROR", "msg" => "Invalid request method"]);
    exit;
}

$product_id = intval($_POST['product_id']);
if ($product_id <= 0) {
    echo json_encode(["status" => "ERROR", "msg" => "Invalid product ID"]);
    exit;
}

// Fetch current images to count them
$existingImages = $connection->query("SELECT image_url FROM product_images WHERE product_id = $product_id")->fetch_all(MYSQLI_ASSOC);
$existingCount = count($existingImages);

// Validate file count
if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    echo json_encode(["status" => "ERROR", "msg" => "No files uploaded"]);
    exit;
}

$totalUploaded = count($_FILES['images']['name']);
if ($existingCount + $totalUploaded > 7) {
     echo json_encode(["status" => "ERROR", "msg" => "You can only upload up to 7 images in total."]);
    exit;
}

$uploadDir = "../uploads/products/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$successCount = 0;
$successFiles = []; // 🔹 initialize array to store successfully uploaded files

// Determine max index for this product
$maxIndex = 0;
foreach ($existingImages as $img) {
    if (preg_match("/^$product_id-(\d+)/", pathinfo($img['image_url'], PATHINFO_FILENAME), $m)) {
        $maxIndex = max($maxIndex, intval($m[1]));
    }
}

for ($i = 0; $i < $totalUploaded; $i++) {
    $tmpPath = $_FILES['images']['tmp_name'][$i];
    $originalName = $_FILES['images']['name'][$i];
    $mimeType = mime_content_type($tmpPath);

    if (!in_array($mimeType, $allowedTypes)) {
        continue; // skip unsupported types
    }

    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $newIndex = $maxIndex + $i + 1;
    $newFilename = "$product_id-$newIndex.$ext";
    $targetPath = $uploadDir . $newFilename;

    if (move_uploaded_file($tmpPath, $targetPath)) {
        $stmt = $connection->prepare("INSERT INTO product_images (product_id, image_url, is_default) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $product_id, $newFilename);
        $stmt->execute();
        $successCount++;
        $successFiles[] = $newFilename; // 🔹 add to response
    }
}

if (!empty($successFiles)) {
    echo json_encode(["status" => "OK", "files" => $successFiles]);
} else {
    echo json_encode(["status" => "ERROR", "msg" => "Upload failed"]);
}
exit;
?>
<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

// Validate product_id (should be in query or hidden input)
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    die("Invalid Product ID");
}
$product_id = intval($_POST['product_id']);

// Collect submitted sizes
$selected_sizes = $_POST['sizes'] ?? [];
$size_stock     = $_POST['size_stock'] ?? [];

// Step 1: Fetch existing size records for this product
$existing_sizes = [];
$res = $connection->query("SELECT size_id, stock FROM product_sizes WHERE product_id = $product_id");
while($row = $res->fetch_assoc()){
    $existing_sizes[$row['size_id']] = $row['stock'];
}

// Step 2: Update or insert sizes
$total_stock = 0;
foreach($selected_sizes as $size_id){
    $size_id = intval($size_id);
    $stock = isset($size_stock[$size_id]) ? intval($size_stock[$size_id]) : 0;

    // Prevent negative stock
    if($stock < 0){
        http_response_code(400);
        echo json_encode(["status"=>"error","msg"=>"Invalid stock value"]);
        exit;
    }

    if (isset($existing_sizes[$size_id])) {
        // Already exists --> Update it
        $stmt = $connection->prepare("UPDATE product_sizes SET stock=? WHERE product_id=? AND size_id=?");
        $stmt->bind_param("iii",$stock,$product_id,$size_id);
        $stmt->execute();
        $stmt->close();
    }else{
        // New size --> Insert it
        $stmt = $connection->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $product_id, $size_id, $stock);
        $stmt->execute();
        $stmt->close();
    }

    $total_stock += $stock;
}

// Step 3: Remove sizes that were unchecked (present in DB but not in submitted form)
if(!empty($existing_sizes)){
    $unchecked = array_diff(array_keys($existing_sizes), $selected_sizes);
    if(!empty($unchecked)){
        $ids = implode(",", array_map("intval", $unchecked));
        $connection->query("DELETE FROM product_sizes WHERE product_id=$product_id AND size_id IN ($ids)");
    }
}

// Step 4: Update total stock in products table
$update_stmt = $connection->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
$update_stmt->bind_param("ii", $total_stock, $product_id);
$update_stmt->execute();
$update_stmt->close();

// Fetch latest stock_hold + stock
$product = $connection->query("SELECT stock, stock_hold FROM products WHERE product_id=$product_id")->fetch_assoc();

// ✅ Return JSON
header("Content-Type: application/json");
echo json_encode([
    "status" => "OK",
    "total_stock" => $product['stock'],
    "stock_hold"  => $product['stock_hold']
]);
exit;
?>

<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $image_url  = $connection->real_escape_string($_POST['image_url'] ?? '');

    if ($product_id > 0 && $image_url) {

        // Check if this image is default
        $check = $connection->query("SELECT is_default FROM product_images WHERE product_id=$product_id AND image_url='$image_url'");

        if($check && $row = $check->fetch_assoc()){
            if((int)$row['is_default'] === 1){
                echo json_encode(["status" => "FAIL", "msg" => "You cannot delete the default image. Please set another image as default first."]);
                exit;
            }
        }else{
            echo json_encode(["status" => "FAIL", "msg" => "Image not found"]);
            exit;
        }

        // Remove file from server
        $filePath = "../uploads/products/" . $image_url;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Remove from DB
        $connection->query("DELETE FROM product_images WHERE product_id=$product_id AND image_url='$image_url'");

        if ($connection->affected_rows > 0) {
            echo json_encode(["status" => "OK", "image" => $image_url]);
        } else {
            echo json_encode(["status" => "FAIL", "msg" => "Image not found in database"]);
        }
    } else {
        echo json_encode(["status" => "FAIL", "msg" => "Invalid parameters"]);
    }
}
?>

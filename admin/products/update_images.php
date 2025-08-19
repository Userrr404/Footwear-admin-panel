<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $image_url  = $connection->real_escape_string($_POST['image_url'] ?? '');

    if ($product_id > 0 && $image_url) {
        // Reset all images
        $connection->query("UPDATE product_images SET is_default = 0 WHERE product_id = $product_id");

        // Set selected one
        $connection->query("UPDATE product_images 
                            SET is_default = 1 
                            WHERE product_id = $product_id AND image_url = '$image_url'");

        if ($connection->affected_rows > 0) {
            echo json_encode(["status" => "OK", "image" => $image_url]);
        } else {
            echo json_encode(["status" => "FAIL", "msg" => "Could not update default image"]);
        }
    } else {
        echo json_encode(["status" => "FAIL", "msg" => "Invalid parameters"]);
    }
}
?>

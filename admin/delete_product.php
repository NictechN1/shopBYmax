<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    $sql = "SELECT photo FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        $sql_delete = "DELETE FROM products WHERE product_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $product_id);
        
        if ($stmt_delete->execute()) {
            if ($product['photo'] != 'default.jpg') {
                $photo_path = '../assets/uploads/' . $product['photo'];
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }
        }
    }
}

header('Location: products.php');
exit;
?>
<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $allowed = ['pending', 'processing', 'completed', 'cancelled'];
    if (in_array($status, $allowed)) {
        $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
    }
}

header('Location: order_details.php?id=' . $order_id);
exit;
?>
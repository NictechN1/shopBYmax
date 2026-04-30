<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $product_id = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    $errors = [];
    
    if ($product_id <= 0) {
        $errors[] = 'Неверный товар';
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Неверная оценка';
    }
    
    if (empty($comment)) {
        $errors[] = 'Введите комментарий';
    }
    
    $sql_check = "SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $product_id, $user_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $errors[] = 'Вы уже оставляли отзыв на этот товар';
    }
    
    if (empty($errors)) {
        $sql_insert = "INSERT INTO reviews (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiis", $product_id, $user_id, $rating, $comment);
        
        if ($stmt_insert->execute()) {
            $review_id = $conn->insert_id;
            
            if (isset($_FILES['review_photo']) && $_FILES['review_photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['review_photo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $upload_dir = 'assets/uploads/reviews/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_filename = time() . '_' . $review_id . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['review_photo']['tmp_name'], $upload_path)) {
                        $sql_photo = "INSERT INTO review_photos (review_id, filename) VALUES (?, ?)";
                        $stmt_photo = $conn->prepare($sql_photo);
                        $stmt_photo->bind_param("is", $review_id, $new_filename);
                        $stmt_photo->execute();
                    }
                }
            }
            
            $_SESSION['review_success'] = 'Отзыв отправлен на модерацию';
        } else {
            $_SESSION['review_error'] = 'Ошибка при отправке отзыва';
        }
    } else {
        $_SESSION['review_error'] = implode(', ', $errors);
    }
    
    header('Location: product.php?id=' . $product_id);
    exit;
}

header('Location: products.php');
exit;
?>
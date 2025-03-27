<?php
/*************************************************************
 * File name: ./upload.php
 * Purpose:   info 152 project, allow sellers to upload pictures of baked goods to menu
 * Complete Date: 2025/03/20
 * Author: Diya Singh
 * DrexelId: ds3899
 ************************************************************/

session_start();
require_once 'configure.php';

header('Content-Type: application/json');

// checks if user is authenticated and is a baker/seller
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'baker') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // establishes required inputs 
    $requiredFields = ['food_name', 'category', 'price'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
    }

    $food_name = filter_var(trim($_POST['food_name']), FILTER_SANITIZE_STRING);
    $category = filter_var(trim($_POST['category']), FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $sellerUsername = $_SESSION['username'];

    // sets price
    if ($price === false || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price']);
        exit();
    }

    try {
        $statement_log = $db->prepare('SELECT sellerID, email FROM Sellers WHERE username = :username');
        $statement_log->bindParam(':username', $sellerUsername, PDO::PARAM_STR);
        $statement_log->execute();
        $seller = $statement_log->fetch(PDO::FETCH_ASSOC);

        if (!$seller) {
            echo json_encode(['success' => false, 'message' => 'Seller not found']);
            exit();
        }

        $sellerID = $seller['sellerID'];
        $sellerEmail = $seller['email'];

        
        if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = '../uploads/';

            // creates directory if it doesn't exist
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileInfo = pathinfo($_FILES['food_image']['name']);
            $fileName = uniqid() . '.' . strtolower($fileInfo['extension']);
            $targetFilePath = $targetDir . $fileName;

            // sets acceptable file types for images
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
                exit();
            }

            
            if (!move_uploaded_file($_FILES['food_image']['tmp_name'], $targetFilePath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                exit();
            }
        } else {
            $fileName = null; 
        }

        $statement_log = $db->prepare('
            INSERT INTO Bakery (userID, categoryName, Food_Name, image_path, price) 
            VALUES (:sellerID, :category, :food_name, :image_path, :price)
        ');
        $statement_log->bindParam(':sellerID', $sellerID, PDO::PARAM_INT);
        $statement_log->bindParam(':food_name', $food_name, PDO::PARAM_STR);
        $statement_log->bindParam(':category', $category, PDO::PARAM_STR);
        $statement_log->bindParam(':price', $price, PDO::PARAM_STR);
        $statement_log->bindParam(':image_path', $fileName, PDO::PARAM_STR);
            
        // executes query
        if ($statement_log->execute()) {
            echo json_encode(['success' => true, 'message' => 'Food item uploaded successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload food item.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

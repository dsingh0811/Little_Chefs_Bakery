<?php
/*************************************************************
 * File name: ./menu.php
 * Purpose:   info 152 project, create menu / upload item form for sellers/customers
 * Complete Date: 2025/03/20
 * Author: Diya Singh
 * DrexelId: ds3899
 ************************************************************/
session_start();
header("Content-Type: application/json");

// initializes response array
$response = ['error' => false];

// checks if user is logged in
if (!isset($_SESSION['userType']) || !isset($_SESSION['username'])) {
    $response['error'] = true;
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

$userType = $_SESSION['userType'];
$username = $_SESSION['username'];

error_log("User type: " . ($userType ?? 'not set'));
error_log("Username: " . ($username ?? 'not set'));

// database connection
function getDbConnection() {
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbname = "bakery";

    try {
        $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $dbUsername, $dbPassword, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

// manages POST requests 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDbConnection();
    if (!$conn) {
        $response['error'] = true;
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit();
    }

    // managed any deleted items from menu
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($userType === 'baker' && isset($_POST['food_id'])) {
            $foodID = $_POST['food_id'];

            // checks if item belongs to specific baker
            $checkSql = "SELECT COUNT(*) FROM Bakery WHERE foodID = :foodID AND 
                         userID = (SELECT sellerID FROM Sellers WHERE username = :username)";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute(['foodID' => $foodID, 'username' => $username]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $deleteSql = "DELETE FROM Bakery WHERE foodID = :foodID";
                $stmt = $conn->prepare($deleteSql);

                if ($stmt->execute(['foodID' => $foodID])) {
                    $response['success'] = true;
                    $response['message'] = 'Item deleted successfully.';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Error deleting item.';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'You can only delete your own items.';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request for deletion.';
        }
    } 
    // manages updating menu items 
    elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        if ($userType === 'baker' && isset($_POST['food_id'])) {
            $foodID = $_POST['food_id'];
            
            // checks if item belongs to specific baker
            $checkSql = "SELECT COUNT(*) FROM Bakery WHERE foodID = :foodID AND 
                        userID = (SELECT sellerID FROM Sellers WHERE username = :username)";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute(['foodID' => $foodID, 'username' => $username]);
            
            if ($checkStmt->fetchColumn() > 0) {
                
                if (!isset($_POST['food_name']) || !isset($_POST['price'])) {
                    $response['error'] = true;
                    $response['message'] = 'Missing required fields for update.';
                    echo json_encode($response);
                    exit();
                }
                
                $foodName = $_POST['food_name'];
                $category = $_POST['category'] ?? '';
                $price = $_POST['price'];
                
                try {
                
                    $conn->beginTransaction();
                    
                    // prepare SQL update statement
                    $updateSql = "UPDATE Bakery SET Food_Name = :foodName, price = :price";
                    
                    // add category to the update if given 
                    if (!empty($category)) {
                        $updateSql .= ", categoryName = :category";
                    }
                    
                    $updateSql .= " WHERE foodID = :foodID";
                    $stmt = $conn->prepare($updateSql);
                    
                    $params = ['foodName' => $foodName, 'price' => $price, 'foodID' => $foodID];
                    if (!empty($category)) {
                        $params['category'] = $category;
                    }
                    
                    if ($stmt->execute($params)) {
                        // checks if new image was uploaded
                        if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === 0) {
                            $image = $_FILES['food_image'];
                            $imageName = time() . '_' . basename($image['name']); // prevents duplicates
                            $imagePath = '../uploads/' . $imageName;
                            
                            if (move_uploaded_file($image['tmp_name'], $imagePath)) {
                                // update image path if new image uploads successfully
                                $updateImageSql = "UPDATE Bakery SET image_path = :imagePath WHERE foodID = :foodID";
                                $stmt = $conn->prepare($updateImageSql);
                                $stmt->execute(['imagePath' => $imageName, 'foodID' => $foodID]);
                            } else {
                                throw new Exception("Failed to move uploaded file.");
                            }
                        }
                        
                        
                        $conn->commit();
                        
                        $response['success'] = true;
                        $response['message'] = 'Item updated successfully.';
                    } else {
                        throw new Exception("Database update failed.");
                    }
                } catch (Exception $e) {
                    
                    $conn->rollBack();
                    $response['error'] = true;
                    $response['message'] = 'Error updating item: ' . $e->getMessage();
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'You can only update your own items.';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid request for update.';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Invalid action specified.';
    }
} 
// manages GET requests 
else {
    $menuItems = [];
    $conn = getDbConnection();
    
    if (!$conn) {
        $response['error'] = true;
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit();
    }

    try {
        if ($userType === 'customer') {
            // get all items for customers 
            $sql = "SELECT B.foodID AS Food_ID, B.Food_Name, B.price, B.image_path, S.email AS email, S.username AS seller_username
                    FROM Bakery B
                    JOIN Sellers S ON B.userID = S.sellerID";
            $stmt = $conn->query($sql);
            $menuItems = $stmt->fetchAll();
        } elseif ($userType === 'baker') {
            // get only the user baker's items
            $sql = "SELECT foodID AS Food_ID, Food_Name, price, image_path
                    FROM Bakery
                    WHERE userID = (SELECT sellerID FROM Sellers WHERE username = :username)";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['username' => $username]);
            $menuItems = $stmt->fetchAll();
        }

        if (empty($menuItems)) {
            $response['warning'] = true;
            $response['message'] = 'No menu items found';
        }
        
        $response['menuItems'] = $menuItems;
        $response['userType'] = $userType;
        $response['username'] = $username;
    } catch (PDOException $e) {
        $response['error'] = true;
        $response['message'] = 'Query failed: ' . $e->getMessage();
        error_log("Query error: " . $e->getMessage());
    }
}

echo json_encode($response);
exit();
?>
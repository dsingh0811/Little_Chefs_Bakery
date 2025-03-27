<?php
/*************************************************************
     * File name: ./log.php
     * Purpose:   info 152 project, create log in page 
     * Complete Date: 2025/03/20
     * Author: Diya Singh
     * DrexelId: ds3899
 ************************************************************/
    session_start();
    header("Content-Type: application/json");
    
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbname = "bakery";
    
    try {
        // establish PDO connection
        $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, $dbUsername, $dbPassword, $options);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['userType'], $data['username'], $data['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid request data"]);
        exit;
    }
    
    $userType = $data['userType'];
    $username = trim($data['username']);
    $password = trim($data['password']);
    
    $table = ($userType === 'baker') ? 'Sellers' : 'Customers';
    
    try {
        // make and execute SQL statement
        $statement_log = $pdo->prepare("SELECT * FROM $table WHERE username = :username");
        $statement_log->bindParam(':username', $username, PDO::PARAM_STR);
        $statement_log->execute();
    
        $user = $statement_log->fetch();
    
        if ($user && $password === $user['pass']) {
            // password is correct so this sets session variables
            $_SESSION['userType'] = $userType;
            $_SESSION['username'] = $username;
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
        } else {
            // invalid username or password
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]);
    }
?>


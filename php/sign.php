<?php
/*************************************************************
 * File name: ./sign.php
 * Purpose:   info 152 project, create sign up page
 * Complete Date: 2025/03/20
 * Author: Diya Singh
 * DrexelId: ds3899
 ************************************************************/
header("Content-Type: application/json");
include('configure.php');

$data = json_decode(file_get_contents("php://input"), true);

//checking if user put in valid account info
if (!isset($data['userType'], $data['username'], $data['password'], $data['email'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}
//establishes account variables
$userType = $data['userType'];
$username = trim($data['username']);
$password = trim($data['password']);
$email = trim($data['email']);

$table = ($userType === "baker") ? "Sellers" : "Customers";

//SQL query to check if inputted info already exists or not
try {
    $statement_log = $db->prepare("SELECT * FROM $table WHERE username = :username OR email = :email");
    $statement_log->bindParam(':username', $username);
    $statement_log->bindParam(':email', $email);
    $statement_log->execute();

    if ($statement_log->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Username or email already exists"]);
        exit;
    }
    //SQL query to add new account info to table sellers or customers
    $statement_log = $db->prepare("INSERT INTO $table (username, pass, email) VALUES (:username, :password, :email)");
    $statement_log->bindParam(':username', $username);
    $statement_log->bindParam(':password', $password);
    $statement_log->bindParam(':email', $email);

    //checks if sign up was successful
    if ($statement_log->execute()) {
        echo json_encode(["success" => true, "message" => "Registration successful!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . implode(" ", $statement_log->errorInfo())]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>


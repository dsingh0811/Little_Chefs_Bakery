<?php
/*************************************************************
 * File name: ./check_session.php
 * Purpose:   Check if user is logged in
 * Complete Date: 2025/03/20
 * Author: Diya Singh
 * DrexelId: ds3899
 ************************************************************/
session_start();
header("Content-Type: application/json");

$response = [
    'loggedIn' => isset($_SESSION['userType']) && isset($_SESSION['username']),
    'userType' => $_SESSION['userType'] ?? null,
    'username' => $_SESSION['username'] ?? null
];

echo json_encode($response);
?>
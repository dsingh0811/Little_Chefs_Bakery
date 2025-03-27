<?php
/*************************************************************
     * File name: ./configure.php
     * Purpose:   info 152 project, create configuration file
     * Complete Date: 2025/03/20
     * Author: Diya Singh
     * DrexelId: ds3899
     ************************************************************/

$dsn = 'mysql:host=localhost;dbname=bakery'; 
$username = 'root'; 
$password = ''; 

// establish PDO connection to database
try { 
    $db = new PDO($dsn,$username,$password);
}
catch (PDOException $e) {
    $error_message = $e->getMessage();
    include('database_error.php');
    exit();
};

?>
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Removed the echo statement that was causing JSON interference
} catch(PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Connection failed: " . $e->getMessage()
    ]);
    exit;
}

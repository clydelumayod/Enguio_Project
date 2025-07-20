<?php
// Test database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Test query to get products
    $stmt = $conn->prepare("SELECT * FROM tbl_product WHERE status = 'active'");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products:\n";
    foreach ($products as $product) {
        echo "- " . $product['product_name'] . " (ID: " . $product['product_id'] . ")\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
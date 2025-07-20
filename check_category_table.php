<?php
// Check if tbl_category table exists and what data it contains
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tbl_category table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'tbl_category'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Table tbl_category exists\n";
        
        // Get all categories
        $stmt = $conn->prepare("SELECT * FROM tbl_category ORDER BY category_id");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Categories found: " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "ID: " . $cat['category_id'] . ", Name: " . $cat['category_name'] . "\n";
        }
        
        // Also check what's in tbl_product category field
        $stmt = $conn->prepare("SELECT DISTINCT category FROM tbl_product WHERE category IS NOT NULL AND category != ''");
        $stmt->execute();
        $productCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCategories used in products:\n";
        foreach ($productCategories as $cat) {
            echo "- " . $cat['category'] . "\n";
        }
        
    } else {
        echo "Table tbl_category does not exist\n";
        
        // Check what categories are being used in products
        $stmt = $conn->prepare("SELECT DISTINCT category FROM tbl_product WHERE category IS NOT NULL AND category != ''");
        $stmt->execute();
        $productCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Categories used in products:\n";
        foreach ($productCategories as $cat) {
            echo "- " . $cat['category'] . "\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
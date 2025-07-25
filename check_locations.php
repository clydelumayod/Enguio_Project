<?php
// Check location IDs
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Check locations
    echo "\n=== LOCATIONS ===\n";
    $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_id");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($locations as $location) {
        echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
    }
    
    // Check products in each location
    echo "\n=== PRODUCTS BY LOCATION ===\n";
    foreach ($locations as $location) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE location_id = ?");
        $stmt->execute([$location['location_id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "- {$location['location_name']}: {$count['count']} products\n";
        
        // Show sample products
        $stmt = $conn->prepare("SELECT product_name, quantity, barcode FROM tbl_product WHERE location_id = ? LIMIT 3");
        $stmt->execute([$location['location_id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            echo "  * {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
        }
        echo "\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
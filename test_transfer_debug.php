<?php
// Test script to debug transfer issue
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Check locations
    $stmt = $conn->prepare("SELECT * FROM tbl_location");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== LOCATIONS ===\n";
    foreach ($locations as $location) {
        echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
    }
    
    // Check products in warehouse
    $stmt = $conn->prepare("SELECT * FROM tbl_product WHERE location_id = 1 AND quantity > 0 LIMIT 5");
    $stmt->execute();
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== WAREHOUSE PRODUCTS ===\n";
    foreach ($warehouseProducts as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Check products in convenience store
    $stmt = $conn->prepare("SELECT * FROM tbl_product WHERE location_id = 2 LIMIT 5");
    $stmt->execute();
    $convenienceProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== CONVENIENCE STORE PRODUCTS ===\n";
    foreach ($convenienceProducts as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Check transfer history
    $stmt = $conn->prepare("
        SELECT th.*, td.product_id, td.qty, p.product_name, p.barcode
        FROM tbl_transfer_header th
        JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
        JOIN tbl_product p ON td.product_id = p.product_id
        ORDER BY th.date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== RECENT TRANSFERS ===\n";
    foreach ($transfers as $transfer) {
        echo "- Transfer ID: {$transfer['transfer_header_id']}, Product: {$transfer['product_name']}, Qty: {$transfer['qty']}, Status: {$transfer['status']}\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
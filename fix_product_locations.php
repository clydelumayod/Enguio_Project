<?php
// Script to check and fix product locations
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Step 1: Check locations
    echo "\n=== STEP 1: CHECKING LOCATIONS ===\n";
    $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_id");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $warehouse = null;
    $convenience = null;
    
    foreach ($locations as $location) {
        echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
        if (strtolower($location['location_name']) === 'warehouse') {
            $warehouse = $location;
        }
        if (strtolower($location['location_name']) === 'convenience store') {
            $convenience = $location;
        }
    }
    
    if (!$warehouse || !$convenience) {
        echo "❌ Error: Warehouse or Convenience Store not found!\n";
        exit;
    }
    
    echo "✅ Found Warehouse: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
    echo "✅ Found Convenience Store: {$convenience['location_name']} (ID: {$convenience['location_id']})\n";
    
    // Step 2: Check current product locations
    echo "\n=== STEP 2: CHECKING CURRENT PRODUCT LOCATIONS ===\n";
    $stmt = $conn->prepare("
        SELECT 
            p.location_id,
            l.location_name,
            COUNT(*) as product_count
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        GROUP BY p.location_id, l.location_name
        ORDER BY p.location_id
    ");
    $stmt->execute();
    $locationCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($locationCounts as $count) {
        $locationName = $count['location_name'] ?: 'NULL/Unknown';
        echo "- Location ID {$count['location_id']} ({$locationName}): {$count['product_count']} products\n";
    }
    
    // Step 3: Check products with NULL or wrong location_id
    echo "\n=== STEP 3: CHECKING PRODUCTS WITH NULL/WRONG LOCATION ===\n";
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_product 
        WHERE location_id IS NULL OR location_id NOT IN (SELECT location_id FROM tbl_location)
    ");
    $stmt->execute();
    $nullCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Products with NULL or invalid location_id: {$nullCount['count']}\n";
    
    if ($nullCount['count'] > 0) {
        echo "\n=== STEP 4: FIXING PRODUCT LOCATIONS ===\n";
        
        // Update products with NULL location_id to warehouse
        $stmt = $conn->prepare("
            UPDATE tbl_product 
            SET location_id = ? 
            WHERE location_id IS NULL
        ");
        $stmt->execute([$warehouse['location_id']]);
        $updatedNull = $stmt->rowCount();
        echo "✅ Updated {$updatedNull} products with NULL location_id to warehouse\n";
        
        // Update products with invalid location_id to warehouse
        $stmt = $conn->prepare("
            UPDATE tbl_product 
            SET location_id = ? 
            WHERE location_id NOT IN (SELECT location_id FROM tbl_location)
        ");
        $stmt->execute([$warehouse['location_id']]);
        $updatedInvalid = $stmt->rowCount();
        echo "✅ Updated {$updatedInvalid} products with invalid location_id to warehouse\n";
    }
    
    // Step 5: Verify fix
    echo "\n=== STEP 5: VERIFYING FIX ===\n";
    $stmt = $conn->prepare("
        SELECT 
            p.location_id,
            l.location_name,
            COUNT(*) as product_count
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        GROUP BY p.location_id, l.location_name
        ORDER BY p.location_id
    ");
    $stmt->execute();
    $locationCountsAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($locationCountsAfter as $count) {
        $locationName = $count['location_name'] ?: 'NULL/Unknown';
        echo "- Location ID {$count['location_id']} ({$locationName}): {$count['product_count']} products\n";
    }
    
    // Step 6: Show sample products in warehouse
    echo "\n=== STEP 6: SAMPLE WAREHOUSE PRODUCTS ===\n";
    $stmt = $conn->prepare("
        SELECT product_id, product_name, quantity, barcode, location_id 
        FROM tbl_product 
        WHERE location_id = ? 
        LIMIT 5
    ");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Warehouse products (Location ID: {$warehouse['location_id']}):\n";
    foreach ($warehouseProducts as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    echo "\n🎉 Location fix completed! Products should now appear in the warehouse.\n";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
<?php
/**
 * Check and Fix Transfer Issue
 * 
 * This script checks the current database state and fixes the issue where
 * products are being transferred to warehouse instead of convenience store.
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    echo "\n=== CHECKING CURRENT DATABASE STATE ===\n";
    
    // Step 1: Check locations
    echo "\n1. Available Locations:\n";
    $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_id");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $warehouse = null;
    $convenience = null;
    
    foreach ($locations as $location) {
        echo "- ID: {$location['location_id']}, Name: {$location['location_name']}\n";
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
    
    echo "\n✅ Found Warehouse: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
    echo "✅ Found Convenience Store: {$convenience['location_name']} (ID: {$convenience['location_id']})\n";
    
    // Step 2: Check products in each location
    echo "\n2. Products by Location:\n";
    
    // Warehouse products
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count, SUM(quantity) as total_qty 
        FROM tbl_product 
        WHERE location_id = ?
    ");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseStats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Warehouse: {$warehouseStats['count']} products, {$warehouseStats['total_qty']} total quantity\n";
    
    // Convenience store products
    $stmt->execute([$convenience['location_id']]);
    $convenienceStats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Convenience Store: {$convenienceStats['count']} products, {$convenienceStats['total_qty']} total quantity\n";
    
    // Step 3: Check recent transfers
    echo "\n3. Recent Transfers:\n";
    $stmt = $conn->prepare("
        SELECT 
            th.transfer_header_id,
            th.date,
            th.status,
            sl.location_name as source_location,
            dl.location_name as destination_location,
            COUNT(td.product_id) as product_count
        FROM tbl_transfer_header th
        LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
        LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
        LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
        GROUP BY th.transfer_header_id
        ORDER BY th.date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($transfers as $transfer) {
        echo "- Transfer ID: {$transfer['transfer_header_id']}\n";
        echo "  Date: {$transfer['date']}\n";
        echo "  Status: {$transfer['status']}\n";
        echo "  From: {$transfer['source_location']} To: {$transfer['destination_location']}\n";
        echo "  Products: {$transfer['product_count']}\n\n";
    }
    
    // Step 4: Check if there are products that should be in convenience store
    echo "\n4. Checking for Products That Should Be in Convenience Store:\n";
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.quantity,
            p.barcode,
            l.location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE p.location_id = ? AND p.quantity > 0
        ORDER BY p.product_name
        LIMIT 5
    ");
    
    echo "Products in Warehouse:\n";
    $stmt->execute([$warehouse['location_id']]);
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($warehouseProducts as $product) {
        echo "- {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    echo "\nProducts in Convenience Store:\n";
    $stmt->execute([$convenience['location_id']]);
    $convenienceProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($convenienceProducts as $product) {
        echo "- {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Step 5: Test a transfer to convenience store
    echo "\n5. Testing Transfer to Convenience Store:\n";
    if (count($warehouseProducts) > 0) {
        $testProduct = $warehouseProducts[0];
        echo "Testing transfer of: {$testProduct['product_name']} (Qty: {$testProduct['quantity']})\n";
        
        // Prepare transfer data
        $transferData = [
            'source_location_id' => $warehouse['location_id'],
            'destination_location_id' => $convenience['location_id'],
            'employee_id' => 1, // Assuming employee ID 1 exists
            'status' => 'Completed',
            'products' => [
                [
                    'product_id' => $testProduct['product_id'],
                    'quantity' => min(2, $testProduct['quantity'])
                ]
            ]
        ];
        
        echo "Transfer data:\n";
        echo "Source: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
        echo "Destination: {$convenience['location_name']} (ID: {$convenience['location_id']})\n";
        echo "Product: {$testProduct['product_name']} (Qty: " . min(2, $testProduct['quantity']) . ")\n";
        
        // Make the API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'create_transfer'] + $transferData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            echo "✅ Transfer successful: " . $result['message'] . "\n";
            
            // Check if products moved to convenience store
            $stmt->execute([$convenience['location_id']]);
            $updatedConvenienceProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($updatedConvenienceProducts) > count($convenienceProducts)) {
                echo "✅ Products successfully moved to convenience store!\n";
                echo "Before: " . count($convenienceProducts) . " products\n";
                echo "After: " . count($updatedConvenienceProducts) . " products\n";
            } else {
                echo "❌ Products not found in convenience store after transfer\n";
            }
        } else {
            echo "❌ Transfer failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "No products available in warehouse for testing\n";
    }
    
    echo "\n=== ANALYSIS COMPLETE ===\n";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
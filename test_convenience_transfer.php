<?php
/**
 * Test Script for Convenience Store Transfer Verification
 * 
 * This script tests the transfer functionality specifically for convenience store transfers
 * to ensure products are properly moved from warehouse to convenience store.
 */

require_once 'Api/backend.php';

echo "🧪 Testing Convenience Store Transfer System\n";
echo "===========================================\n\n";

// Test configuration
$testConfig = [
    'source_location' => 'Warehouse',
    'destination_location' => 'Convenience Store',
    'test_products' => [
        ['product_name' => 'Test Convenience Product 1', 'quantity' => 5],
        ['product_name' => 'Test Convenience Product 2', 'quantity' => 3]
    ]
];

echo "1. Testing Location Availability...\n";
$locations = testGetLocations();
if ($locations) {
    echo "✅ Locations found: " . count($locations) . "\n";
    foreach ($locations as $location) {
        echo "   - " . $location['location_name'] . " (ID: " . $location['location_id'] . ")\n";
    }
} else {
    echo "❌ Failed to get locations\n";
    exit(1);
}

// Find warehouse and convenience store
$warehouse = null;
$convenience = null;

foreach ($locations as $location) {
    if (stripos($location['location_name'], 'warehouse') !== false) {
        $warehouse = $location;
    }
    if (stripos($location['location_name'], 'convenience') !== false) {
        $convenience = $location;
    }
}

if (!$warehouse || !$convenience) {
    echo "❌ Could not find Warehouse or Convenience Store locations\n";
    exit(1);
}

echo "\n2. Found Required Locations:\n";
echo "   - Source (Warehouse): {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
echo "   - Destination (Convenience): {$convenience['location_name']} (ID: {$convenience['location_id']})\n";

// Test 3: Check warehouse products
echo "\n3. Testing Warehouse Products...\n";
$warehouseProducts = testGetProductsByLocation('Warehouse');
if ($warehouseProducts) {
    echo "✅ Warehouse products found: " . count($warehouseProducts) . "\n";
    if (count($warehouseProducts) > 0) {
        echo "   Sample product: " . $warehouseProducts[0]['product_name'] . " (Qty: " . $warehouseProducts[0]['quantity'] . ")\n";
    }
} else {
    echo "❌ No warehouse products found\n";
}

// Test 4: Check convenience store products before transfer
echo "\n4. Testing Convenience Store Products (Before Transfer)...\n";
$convenienceProducts = testGetProductsByLocation('Convenience Store');
if ($convenienceProducts) {
    echo "✅ Convenience store products found: " . count($convenienceProducts) . "\n";
    foreach ($convenienceProducts as $product) {
        echo "   - {$product['product_name']} (Qty: {$product['quantity']})\n";
    }
} else {
    echo "ℹ️ No convenience store products found (this is normal if no transfers have been made)\n";
}

// Test 5: Perform a test transfer to convenience store
echo "\n5. Testing Convenience Store Transfer...\n";
if (count($warehouseProducts) > 0) {
    $testTransfer = testConvenienceTransfer($warehouseProducts[0]);
    if ($testTransfer) {
        echo "✅ Convenience store transfer test completed successfully\n";
        echo "   Transfer ID: " . $testTransfer['transfer_id'] . "\n";
        
        // Test 6: Verify products moved to convenience store
        echo "\n6. Verifying Products in Convenience Store...\n";
        $updatedConvenienceProducts = testGetProductsByLocation('Convenience Store');
        if ($updatedConvenienceProducts && count($updatedConvenienceProducts) > count($convenienceProducts)) {
            echo "✅ Products successfully moved to convenience store\n";
            echo "   Before: " . count($convenienceProducts) . " products\n";
            echo "   After: " . count($updatedConvenienceProducts) . " products\n";
            echo "   Products added: " . (count($updatedConvenienceProducts) - count($convenienceProducts)) . "\n";
            
            // Show new products
            $newProducts = array_slice($updatedConvenienceProducts, count($convenienceProducts));
            foreach ($newProducts as $product) {
                echo "   - {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
            }
        } else {
            echo "❌ Products not found in convenience store after transfer\n";
        }
    } else {
        echo "❌ Convenience store transfer test failed\n";
    }
} else {
    echo "⚠️ No products available for convenience store transfer test\n";
}

echo "\n🎉 Convenience Store Transfer System Test Complete!\n";

// Helper functions
function testGetLocations() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Error getting locations: " . $e->getMessage() . "\n";
        return null;
    }
}

function testGetProductsByLocation($locationName) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT p.*, l.location_name
            FROM tbl_product p
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE l.location_name = ? AND (p.status IS NULL OR p.status <> 'archived')
            ORDER BY p.product_name
        ");
        $stmt->execute([$locationName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Error getting products by location: " . $e->getMessage() . "\n";
        return null;
    }
}

function testConvenienceTransfer($product) {
    global $conn;
    
    try {
        // Get warehouse and convenience store locations
        $warehouseStmt = $conn->prepare("SELECT location_id FROM tbl_location WHERE location_name = 'Warehouse'");
        $warehouseStmt->execute();
        $warehouse = $warehouseStmt->fetch(PDO::FETCH_ASSOC);
        
        $convenienceStmt = $conn->prepare("SELECT location_id FROM tbl_location WHERE location_name = 'Convenience Store'");
        $convenienceStmt->execute();
        $convenience = $convenienceStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$warehouse || !$convenience) {
            echo "❌ Required locations not found\n";
            return null;
        }
        
        // Get a staff member
        $staffStmt = $conn->prepare("SELECT emp_id FROM tbl_inventory_staff LIMIT 1");
        $staffStmt->execute();
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            echo "❌ No staff members found\n";
            return null;
        }
        
        // Prepare transfer data for convenience store
        $transferData = [
            'source_location_id' => $warehouse['location_id'],
            'destination_location_id' => $convenience['location_id'],
            'employee_id' => $staff['emp_id'],
            'status' => 'Completed',
            'products' => [
                [
                    'product_id' => $product['product_id'],
                    'quantity' => min(2, $product['quantity'])
                ]
            ]
        ];
        
        echo "   Transferring: {$product['product_name']} (Qty: " . min(2, $product['quantity']) . ")\n";
        echo "   From: Warehouse (ID: {$warehouse['location_id']}) To: Convenience Store (ID: {$convenience['location_id']})\n";
        
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
            echo "   ✅ Transfer successful: " . $result['message'] . "\n";
            return $result;
        } else {
            echo "   ❌ Transfer failed: " . ($result['message'] ?? 'Unknown error') . "\n";
            return null;
        }
        
    } catch (Exception $e) {
        echo "Error in convenience transfer: " . $e->getMessage() . "\n";
        return null;
    }
}
?> 
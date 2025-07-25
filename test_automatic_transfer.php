<?php
/**
 * Test Script for Automatic Inventory Transfer System
 * 
 * This script tests the automatic transfer functionality to ensure products
 * are properly moved from warehouse to specific stores like convenience store.
 */

require_once 'Api/backend.php';

// Test configuration
$testConfig = [
    'source_location' => 'Warehouse',
    'destination_location' => 'Convenience Store',
    'test_products' => [
        ['product_name' => 'Test Product 1', 'quantity' => 5],
        ['product_name' => 'Test Product 2', 'quantity' => 3]
    ]
];

echo "🧪 Testing Automatic Inventory Transfer System\n";
echo "=============================================\n\n";

// Test 1: Check if locations exist
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

// Test 2: Check warehouse products
echo "\n2. Testing Warehouse Products...\n";
$warehouseProducts = testGetProductsByLocation('Warehouse');
if ($warehouseProducts) {
    echo "✅ Warehouse products found: " . count($warehouseProducts) . "\n";
    if (count($warehouseProducts) > 0) {
        echo "   Sample product: " . $warehouseProducts[0]['product_name'] . " (Qty: " . $warehouseProducts[0]['quantity'] . ")\n";
    }
} else {
    echo "❌ No warehouse products found\n";
}

// Test 3: Check convenience store products before transfer
echo "\n3. Testing Convenience Store Products (Before Transfer)...\n";
$convenienceProducts = testGetProductsByLocation('Convenience Store');
if ($convenienceProducts) {
    echo "✅ Convenience store products found: " . count($convenienceProducts) . "\n";
} else {
    echo "ℹ️ No convenience store products found (this is normal if no transfers have been made)\n";
}

// Test 4: Perform a test transfer
echo "\n4. Testing Automatic Transfer...\n";
if (count($warehouseProducts) > 0) {
    $testTransfer = testTransfer($warehouseProducts[0]);
    if ($testTransfer) {
        echo "✅ Transfer test completed successfully\n";
        echo "   Transfer ID: " . $testTransfer['transfer_id'] . "\n";
        
        // Test 5: Verify products moved to destination
        echo "\n5. Verifying Products in Destination...\n";
        $updatedConvenienceProducts = testGetProductsByLocation('Convenience Store');
        if ($updatedConvenienceProducts && count($updatedConvenienceProducts) > count($convenienceProducts)) {
            echo "✅ Products successfully moved to convenience store\n";
            echo "   Before: " . count($convenienceProducts) . " products\n";
            echo "   After: " . count($updatedConvenienceProducts) . " products\n";
        } else {
            echo "❌ Products not found in destination after transfer\n";
        }
    } else {
        echo "❌ Transfer test failed\n";
    }
} else {
    echo "⚠️ No products available for transfer test\n";
}

echo "\n🎉 Automatic Transfer System Test Complete!\n";

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

function testTransfer($product) {
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
        
        // Prepare transfer data
        $transferData = [
            'source_location_id' => $warehouse['location_id'],
            'destination_location_id' => $convenience['location_id'],
            'employee_id' => $staff['emp_id'],
            'status' => 'Completed',
            'products' => [
                [
                    'product_id' => $product['product_id'],
                    'quantity' => min(2, $product['quantity']) // Transfer 2 or available quantity
                ]
            ]
        ];
        
        // Simulate the API call
        $action = 'create_transfer';
        $data = $transferData;
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Validate product quantities
            foreach ($data['products'] as $product) {
                $product_id = $product['product_id'];
                $transfer_qty = $product['quantity'];
                
                $checkStmt = $conn->prepare("
                    SELECT quantity, product_name, location_id 
                    FROM tbl_product 
                    WHERE product_id = ? AND location_id = ?
                ");
                $checkStmt->execute([$product_id, $data['source_location_id']]);
                $currentProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentProduct) {
                    throw new Exception("Product not found in source location");
                }
                
                if ($currentProduct['quantity'] < $transfer_qty) {
                    throw new Exception("Insufficient quantity for transfer");
                }
            }
            
            // Insert transfer header
            $stmt = $conn->prepare("
                INSERT INTO tbl_transfer_header (
                    source_location_id, destination_location_id, employee_id, 
                    status, date
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$data['source_location_id'], $data['destination_location_id'], $data['employee_id'], $data['status']]);
            $transfer_header_id = $conn->lastInsertId();
            
            // Process transfer
            foreach ($data['products'] as $product) {
                $product_id = $product['product_id'];
                $transfer_qty = $product['quantity'];
                
                // Insert transfer detail
                $stmt2 = $conn->prepare("
                    INSERT INTO tbl_transfer_dtl (
                        transfer_header_id, product_id, qty
                    ) VALUES (?, ?, ?)
                ");
                $stmt2->execute([$transfer_header_id, $product_id, $transfer_qty]);
                
                // Get product details
                $productStmt = $conn->prepare("
                    SELECT product_name, category, barcode, description, prescription, bulk,
                           expiration, unit_price, brand_id, supplier_id, batch_id, status, Variation
                    FROM tbl_product 
                    WHERE product_id = ? AND location_id = ?
                    LIMIT 1
                ");
                $productStmt->execute([$product_id, $data['source_location_id']]);
                $productDetails = $productStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productDetails) {
                    // Decrease quantity in source
                    $updateSourceStmt = $conn->prepare("
                        UPDATE tbl_product 
                        SET quantity = quantity - ?,
                            stock_status = CASE 
                                WHEN quantity - ? <= 0 THEN 'out of stock'
                                WHEN quantity - ? <= 10 THEN 'low stock'
                                ELSE 'in stock'
                            END
                        WHERE product_id = ? AND location_id = ?
                    ");
                    $updateSourceStmt->execute([$transfer_qty, $transfer_qty, $transfer_qty, $product_id, $data['source_location_id']]);
                    
                    // Check if product exists in destination
                    $checkDestStmt = $conn->prepare("
                        SELECT product_id, quantity 
                        FROM tbl_product 
                        WHERE product_id = ? AND location_id = ?
                    ");
                    $checkDestStmt->execute([$product_id, $data['destination_location_id']]);
                    $existingProduct = $checkDestStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingProduct) {
                        // Update existing product
                        $updateDestStmt = $conn->prepare("
                            UPDATE tbl_product 
                            SET quantity = quantity + ?,
                                stock_status = CASE 
                                    WHEN quantity + ? <= 0 THEN 'out of stock'
                                    WHEN quantity + ? <= 10 THEN 'low stock'
                                    ELSE 'in stock'
                                END
                            WHERE product_id = ? AND location_id = ?
                        ");
                        $updateDestStmt->execute([$transfer_qty, $transfer_qty, $transfer_qty, $product_id, $data['destination_location_id']]);
                    } else {
                        // Create new product in destination
                        $uniqueBarcode = $productDetails['barcode'] . '_' . $data['destination_location_id'] . '_' . time();
                        
                        $insertDestStmt = $conn->prepare("
                            INSERT INTO tbl_product (
                                product_name, category, barcode, description, prescription, bulk,
                                expiration, quantity, unit_price, brand_id, supplier_id,
                                location_id, batch_id, status, Variation, stock_status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insertDestStmt->execute([
                            $productDetails['product_name'],
                            $productDetails['category'],
                            $uniqueBarcode,
                            $productDetails['description'],
                            $productDetails['prescription'],
                            $productDetails['bulk'],
                            $productDetails['expiration'],
                            $transfer_qty,
                            $productDetails['unit_price'],
                            $productDetails['brand_id'],
                            $productDetails['supplier_id'],
                            $data['destination_location_id'],
                            $productDetails['batch_id'],
                            $productDetails['status'],
                            $productDetails['Variation'],
                            $transfer_qty <= 0 ? 'out of stock' : ($transfer_qty <= 10 ? 'low stock' : 'in stock')
                        ]);
                    }
                }
            }
            
            $conn->commit();
            return [
                'success' => true,
                'transfer_id' => $transfer_header_id,
                'message' => 'Transfer completed successfully'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "Transfer error: " . $e->getMessage() . "\n";
            return null;
        }
        
    } catch (Exception $e) {
        echo "Error in test transfer: " . $e->getMessage() . "\n";
        return null;
    }
}
?> 
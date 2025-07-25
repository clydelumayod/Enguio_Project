<?php
// Comprehensive test script for transfer functionality
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
    
    foreach ($locations as $location) {
        echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
    }
    
    // Step 2: Check products in warehouse
    echo "\n=== STEP 2: CHECKING WAREHOUSE PRODUCTS ===\n";
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = 1 AND quantity > 0 LIMIT 3");
    $stmt->execute();
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($warehouseProducts) > 0) {
        foreach ($warehouseProducts as $product) {
            echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
        }
    } else {
        echo "No products found in warehouse with quantity > 0\n";
        exit;
    }
    
    // Step 3: Check products in convenience store before transfer
    echo "\n=== STEP 3: CHECKING CONVENIENCE STORE BEFORE TRANSFER ===\n";
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = 2");
    $stmt->execute();
    $convenienceProductsBefore = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products in convenience store before transfer: " . count($convenienceProductsBefore) . "\n";
    foreach ($convenienceProductsBefore as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Step 4: Perform transfer
    echo "\n=== STEP 4: PERFORMING TRANSFER ===\n";
    $testProduct = $warehouseProducts[0];
    $transferQty = min(2, $testProduct['quantity']);
    
    echo "Transferring: {$testProduct['product_name']} (Qty: {$transferQty})\n";
    echo "From: Warehouse (ID: 1) To: Convenience Store (ID: 2)\n";
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Insert transfer header
        $stmt = $conn->prepare("
            INSERT INTO tbl_transfer_header (
                source_location_id, destination_location_id, employee_id, 
                status, date
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([1, 2, 1, 'Completed']);
        $transfer_id = $conn->lastInsertId();
        echo "Created transfer header ID: $transfer_id\n";
        
        // Insert transfer detail
        $stmt = $conn->prepare("
            INSERT INTO tbl_transfer_dtl (
                transfer_header_id, product_id, qty
            ) VALUES (?, ?, ?)
        ");
        $stmt->execute([$transfer_id, $testProduct['product_id'], $transferQty]);
        echo "Created transfer detail\n";
        
        // Get product details from source
        $stmt = $conn->prepare("
            SELECT product_name, category, barcode, description, prescription, bulk,
                   expiration, unit_price, brand_id, supplier_id, batch_id, status, Variation
            FROM tbl_product 
            WHERE product_id = ? AND location_id = ?
            LIMIT 1
        ");
        $stmt->execute([$testProduct['product_id'], 1]);
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($productDetails) {
            // Decrease quantity in source
            $stmt = $conn->prepare("
                UPDATE tbl_product 
                SET quantity = quantity - ?,
                    stock_status = CASE 
                        WHEN quantity - ? <= 0 THEN 'out of stock'
                        WHEN quantity - ? <= 10 THEN 'low stock'
                        ELSE 'in stock'
                    END
                WHERE product_id = ? AND location_id = ?
            ");
            $stmt->execute([$transferQty, $transferQty, $transferQty, $testProduct['product_id'], 1]);
            echo "Decreased quantity in source location\n";
            
            // Check if product exists in destination by barcode
            $stmt = $conn->prepare("
                SELECT product_id, quantity 
                FROM tbl_product 
                WHERE barcode = ? AND location_id = ?
            ");
            $stmt->execute([$productDetails['barcode'], 2]);
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingProduct) {
                // Update existing product
                $stmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity + ?,
                        stock_status = CASE 
                            WHEN quantity + ? <= 0 THEN 'out of stock'
                            WHEN quantity + ? <= 10 THEN 'low stock'
                            ELSE 'in stock'
                        END
                    WHERE product_id = ? AND location_id = ?
                ");
                $stmt->execute([$transferQty, $transferQty, $transferQty, $existingProduct['product_id'], 2]);
                echo "Updated existing product in destination\n";
            } else {
                // Create new product in destination
                $stmt = $conn->prepare("
                    INSERT INTO tbl_product (
                        product_name, category, barcode, description, prescription, bulk,
                        expiration, quantity, unit_price, brand_id, supplier_id,
                        location_id, batch_id, status, Variation, stock_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $productDetails['product_name'],
                    $productDetails['category'],
                    $productDetails['barcode'],
                    $productDetails['description'],
                    $productDetails['prescription'],
                    $productDetails['bulk'],
                    $productDetails['expiration'],
                    $transferQty,
                    $productDetails['unit_price'],
                    $productDetails['brand_id'],
                    $productDetails['supplier_id'],
                    2, // destination location
                    $productDetails['batch_id'],
                    $productDetails['status'],
                    $productDetails['Variation'],
                    $transferQty <= 0 ? 'out of stock' : ($transferQty <= 10 ? 'low stock' : 'in stock')
                ]);
                
                if ($result) {
                    echo "Created new product in destination\n";
                } else {
                    throw new Exception("Failed to create product in destination");
                }
            }
        }
        
        $conn->commit();
        echo "Transfer completed successfully!\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transfer failed: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Step 5: Check products in convenience store after transfer
    echo "\n=== STEP 5: CHECKING CONVENIENCE STORE AFTER TRANSFER ===\n";
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = 2");
    $stmt->execute();
    $convenienceProductsAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products in convenience store after transfer: " . count($convenienceProductsAfter) . "\n";
    foreach ($convenienceProductsAfter as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Step 6: Verify transfer worked
    echo "\n=== STEP 6: VERIFICATION ===\n";
    $productsAdded = count($convenienceProductsAfter) - count($convenienceProductsBefore);
    echo "Products added to convenience store: $productsAdded\n";
    
    if ($productsAdded > 0) {
        echo "✅ TRANSFER SUCCESSFUL! Products were moved to convenience store.\n";
    } else {
        echo "❌ TRANSFER FAILED! No products were added to convenience store.\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
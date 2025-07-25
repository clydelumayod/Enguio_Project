<?php
// Test script to verify transfer functionality
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
    
    // Step 2: Check products before transfer
    echo "\n=== STEP 2: CHECKING PRODUCTS BEFORE TRANSFER ===\n";
    
    // Warehouse products
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = ? AND quantity > 0 LIMIT 3");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Warehouse products (ID: {$warehouse['location_id']}):\n";
    foreach ($warehouseProducts as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    // Convenience store products
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = ?");
    $stmt->execute([$convenience['location_id']]);
    $convenienceProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nConvenience store products (ID: {$convenience['location_id']}):\n";
    foreach ($convenienceProducts as $product) {
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
    }
    
    if (count($warehouseProducts) === 0) {
        echo "❌ No products found in warehouse to transfer!\n";
        exit;
    }
    
    // Step 3: Perform test transfer
    echo "\n=== STEP 3: PERFORMING TEST TRANSFER ===\n";
    $testProduct = $warehouseProducts[0];
    $transferQty = min(2, $testProduct['quantity']);
    
    echo "Transferring: {$testProduct['product_name']} (Qty: {$transferQty})\n";
    echo "From: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
    echo "To: {$convenience['location_name']} (ID: {$convenience['location_id']})\n";
    
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
        $stmt->execute([$warehouse['location_id'], $convenience['location_id'], 1, 'Completed']);
        $transfer_id = $conn->lastInsertId();
        echo "✅ Created transfer header ID: $transfer_id\n";
        
        // Insert transfer detail
        $stmt = $conn->prepare("
            INSERT INTO tbl_transfer_dtl (
                transfer_header_id, product_id, qty
            ) VALUES (?, ?, ?)
        ");
        $stmt->execute([$transfer_id, $testProduct['product_id'], $transferQty]);
        echo "✅ Created transfer detail\n";
        
        // Get product details from source
        $stmt = $conn->prepare("
            SELECT product_name, category, barcode, description, prescription, bulk,
                   expiration, unit_price, brand_id, supplier_id, batch_id, status, Variation
            FROM tbl_product 
            WHERE product_id = ? AND location_id = ?
            LIMIT 1
        ");
        $stmt->execute([$testProduct['product_id'], $warehouse['location_id']]);
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
            $stmt->execute([$transferQty, $transferQty, $transferQty, $testProduct['product_id'], $warehouse['location_id']]);
            echo "✅ Decreased quantity in source location\n";
            
            // Check if product exists in destination by barcode
            $stmt = $conn->prepare("
                SELECT product_id, quantity 
                FROM tbl_product 
                WHERE barcode = ? AND location_id = ?
            ");
            $stmt->execute([$productDetails['barcode'], $convenience['location_id']]);
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
                $stmt->execute([$transferQty, $transferQty, $transferQty, $existingProduct['product_id'], $convenience['location_id']]);
                echo "✅ Updated existing product in destination\n";
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
                    $convenience['location_id'],
                    $productDetails['batch_id'],
                    $productDetails['status'],
                    $productDetails['Variation'],
                    $transferQty <= 0 ? 'out of stock' : ($transferQty <= 10 ? 'low stock' : 'in stock')
                ]);
                
                if ($result) {
                    echo "✅ Created new product in destination\n";
                } else {
                    throw new Exception("Failed to create product in destination");
                }
            }
        }
        
        $conn->commit();
        echo "✅ Transfer completed successfully!\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Transfer failed: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Step 4: Verify transfer results
    echo "\n=== STEP 4: VERIFYING TRANSFER RESULTS ===\n";
    
    // Check warehouse products after transfer
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = ? AND product_id = ?");
    $stmt->execute([$warehouse['location_id'], $testProduct['product_id']]);
    $warehouseProductAfter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Warehouse product after transfer:\n";
    if ($warehouseProductAfter) {
        echo "- {$warehouseProductAfter['product_name']} (Qty: {$warehouseProductAfter['quantity']})\n";
    } else {
        echo "- Product not found in warehouse\n";
    }
    
    // Check convenience store products after transfer
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity, barcode FROM tbl_product WHERE location_id = ? AND barcode = ?");
    $stmt->execute([$convenience['location_id'], $testProduct['barcode']]);
    $convenienceProductAfter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nConvenience store product after transfer:\n";
    if ($convenienceProductAfter) {
        echo "- {$convenienceProductAfter['product_name']} (Qty: {$convenienceProductAfter['quantity']}, Barcode: {$convenienceProductAfter['barcode']})\n";
        echo "✅ SUCCESS: Product transferred to convenience store!\n";
    } else {
        echo "❌ FAILED: Product not found in convenience store\n";
    }
    
    // Step 5: Summary
    echo "\n=== STEP 5: SUMMARY ===\n";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE location_id = ?");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$convenience['location_id']]);
    $convenienceCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Warehouse products: {$warehouseCount['count']}\n";
    echo "Convenience store products: {$convenienceCount['count']}\n";
    
    if ($convenienceProductAfter) {
        echo "🎉 TRANSFER VERIFICATION SUCCESSFUL!\n";
        echo "Products are being correctly moved from warehouse to convenience store.\n";
    } else {
        echo "❌ TRANSFER VERIFICATION FAILED!\n";
        echo "Products are not being moved to the convenience store.\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
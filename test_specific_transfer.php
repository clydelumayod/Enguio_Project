<?php
// Test script to check specific transfer issue
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
    
    // Step 2: Find products with quantity = 1
    echo "\n=== STEP 2: FINDING PRODUCTS WITH QTY = 1 ===\n";
    $stmt = $conn->prepare("
        SELECT product_id, product_name, quantity, barcode, location_id, location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE p.quantity = 1
        ORDER BY p.location_id
    ");
    $stmt->execute();
    $productsWithQty1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products with quantity = 1:\n";
    foreach ($productsWithQty1 as $product) {
        $locationName = $product['location_name'] ?: 'Unknown';
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Location: {$locationName}, Barcode: {$product['barcode']})\n";
    }
    
    if (count($productsWithQty1) === 0) {
        echo "No products found with quantity = 1\n";
        exit;
    }
    
    // Step 3: Check transfer history for these products
    echo "\n=== STEP 3: CHECKING TRANSFER HISTORY ===\n";
    foreach ($productsWithQty1 as $product) {
        $stmt = $conn->prepare("
            SELECT th.transfer_header_id, th.date, th.status, td.qty, 
                   th.source_location_id, th.destination_location_id,
                   sl.location_name as source_name, dl.location_name as dest_name
            FROM tbl_transfer_header th
            JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
            LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
            LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
            WHERE td.product_id = ?
            ORDER BY th.date DESC
            LIMIT 3
        ");
        $stmt->execute([$product['product_id']]);
        $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTransfer history for {$product['product_name']}:\n";
        foreach ($transfers as $transfer) {
            echo "- Transfer ID: {$transfer['transfer_header_id']}, Date: {$transfer['date']}, Status: {$transfer['status']}\n";
            echo "  From: {$transfer['source_name']} To: {$transfer['dest_name']}, Qty: {$transfer['qty']}\n";
        }
    }
    
    // Step 4: Check if products exist in both locations
    echo "\n=== STEP 4: CHECKING PRODUCTS IN BOTH LOCATIONS ===\n";
    foreach ($productsWithQty1 as $product) {
        $stmt = $conn->prepare("
            SELECT product_id, product_name, quantity, barcode, location_id, location_name
            FROM tbl_product p
            LEFT JOIN tbl_location l ON p.location_id = l.location_id
            WHERE p.barcode = ?
            ORDER BY p.location_id
        ");
        $stmt->execute([$product['barcode']]);
        $sameBarcodeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nProducts with barcode {$product['barcode']} ({$product['product_name']}):\n";
        foreach ($sameBarcodeProducts as $p) {
            $locationName = $p['location_name'] ?: 'Unknown';
            echo "- {$p['product_name']} (ID: {$p['product_id']}, Location: {$locationName}, Qty: {$p['quantity']})\n";
        }
    }
    
    // Step 5: Manual fix - Move products with qty=1 to convenience store
    echo "\n=== STEP 5: MANUAL FIX - MOVING PRODUCTS WITH QTY=1 TO CONVENIENCE STORE ===\n";
    
    foreach ($productsWithQty1 as $product) {
        if ($product['location_id'] == $warehouse['location_id']) {
            echo "Moving {$product['product_name']} from warehouse to convenience store...\n";
            
            // Check if product already exists in convenience store
            $stmt = $conn->prepare("
                SELECT product_id, quantity 
                FROM tbl_product 
                WHERE barcode = ? AND location_id = ?
            ");
            $stmt->execute([$product['barcode'], $convenience['location_id']]);
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingProduct) {
                // Update existing product
                $stmt = $conn->prepare("
                    UPDATE tbl_product 
                    SET quantity = quantity + ?
                    WHERE product_id = ?
                ");
                $stmt->execute([$product['quantity'], $existingProduct['product_id']]);
                echo "✅ Updated existing product in convenience store\n";
            } else {
                // Create new product in convenience store
                $stmt = $conn->prepare("
                    INSERT INTO tbl_product (
                        product_name, category, barcode, description, prescription, bulk,
                        expiration, quantity, unit_price, brand_id, supplier_id,
                        location_id, batch_id, status, Variation, stock_status
                    ) SELECT 
                        product_name, category, barcode, description, prescription, bulk,
                        expiration, quantity, unit_price, brand_id, supplier_id,
                        ?, batch_id, status, Variation, stock_status
                    FROM tbl_product 
                    WHERE product_id = ?
                ");
                $stmt->execute([$convenience['location_id'], $product['product_id']]);
                echo "✅ Created new product in convenience store\n";
            }
            
            // Remove from warehouse
            $stmt = $conn->prepare("DELETE FROM tbl_product WHERE product_id = ?");
            $stmt->execute([$product['product_id']]);
            echo "✅ Removed product from warehouse\n";
        }
    }
    
    // Step 6: Verify the fix
    echo "\n=== STEP 6: VERIFYING THE FIX ===\n";
    $stmt = $conn->prepare("
        SELECT product_id, product_name, quantity, barcode, location_id, location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE p.quantity = 1
        ORDER BY p.location_id
    ");
    $stmt->execute();
    $productsAfterFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products with quantity = 1 after fix:\n";
    foreach ($productsAfterFix as $product) {
        $locationName = $product['location_name'] ?: 'Unknown';
        echo "- {$product['product_name']} (ID: {$product['product_id']}, Location: {$locationName}, Barcode: {$product['barcode']})\n";
    }
    
    echo "\n🎉 Manual fix completed! Products with qty=1 should now be in convenience store.\n";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
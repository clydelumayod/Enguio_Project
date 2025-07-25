<?php
// Direct database test for transfer functionality
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Direct Database Transfer Test\n";
    echo "===============================\n\n";
    
    // 1. Check locations
    echo "1. Available Locations:\n";
    $stmt = $pdo->query("SELECT * FROM tbl_location ORDER BY location_name");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($locations as $location) {
        echo "- ID: {$location['location_id']}, Name: {$location['location_name']}\n";
    }
    echo "\n";
    
    // 2. Check products before transfer
    echo "2. Products Before Transfer:\n";
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.barcode,
            p.quantity,
            l.location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE p.quantity > 0
        ORDER BY l.location_name, p.product_name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "- {$product['product_name']}: {$product['quantity']} in {$product['location_name']}\n";
    }
    echo "\n";
    
    // 3. Find warehouse and convenience store
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
        echo "❌ Could not find Warehouse or Convenience Store\n";
        exit;
    }
    
    echo "3. Found Locations:\n";
    echo "- Warehouse: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
    echo "- Convenience: {$convenience['location_name']} (ID: {$convenience['location_id']})\n\n";
    
    // 4. Check products in warehouse
    $stmt = $pdo->prepare("
        SELECT product_id, product_name, quantity, barcode 
        FROM tbl_product 
        WHERE location_id = ? AND quantity > 0 
        LIMIT 1
    ");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$warehouseProduct) {
        echo "❌ No products found in warehouse\n";
        exit;
    }
    
    echo "4. Testing Transfer:\n";
    echo "- Product: {$warehouseProduct['product_name']}\n";
    echo "- Current Quantity: {$warehouseProduct['quantity']}\n";
    echo "- Barcode: {$warehouseProduct['barcode']}\n\n";
    
    // 5. Check products in convenience store before
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_product 
        WHERE location_id = ?
    ");
    $stmt->execute([$convenience['location_id']]);
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "5. Products in Convenience Store Before: {$beforeCount['count']}\n";
    
    // 6. Perform transfer
    $pdo->beginTransaction();
    
    try {
        // Insert transfer header
        $stmt = $pdo->prepare("
            INSERT INTO tbl_transfer_header (
                source_location_id, destination_location_id, employee_id, 
                status, date
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$warehouse['location_id'], $convenience['location_id'], 1, 'Completed']);
        $transfer_id = $pdo->lastInsertId();
        
        echo "6. Created Transfer ID: $transfer_id\n";
        
        // Insert transfer detail
        $stmt = $pdo->prepare("
            INSERT INTO tbl_transfer_dtl (
                transfer_header_id, product_id, qty
            ) VALUES (?, ?, ?)
        ");
        $transfer_qty = 1;
        $stmt->execute([$transfer_id, $warehouseProduct['product_id'], $transfer_qty]);
        
        // Decrease quantity in source
        $stmt = $pdo->prepare("
            UPDATE tbl_product 
            SET quantity = quantity - ?
            WHERE product_id = ? AND location_id = ?
        ");
        $stmt->execute([$transfer_qty, $warehouseProduct['product_id'], $warehouse['location_id']]);
        
        // Check if product exists in destination
        $stmt = $pdo->prepare("
            SELECT product_id, quantity 
            FROM tbl_product 
            WHERE barcode = ? AND location_id = ?
        ");
        $stmt->execute([$warehouseProduct['barcode'], $convenience['location_id']]);
        $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingProduct) {
            // Update existing product
            $stmt = $pdo->prepare("
                UPDATE tbl_product 
                SET quantity = quantity + ?
                WHERE product_id = ? AND location_id = ?
            ");
            $stmt->execute([$transfer_qty, $existingProduct['product_id'], $convenience['location_id']]);
            echo "7. Updated existing product in destination\n";
        } else {
            // Create new product in destination
            $stmt = $pdo->prepare("
                INSERT INTO tbl_product (
                    product_name, category, barcode, description, prescription, bulk,
                    expiration, quantity, unit_price, brand_id, supplier_id,
                    location_id, batch_id, status, Variation, stock_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $warehouseProduct['product_name'],
                'Test Category',
                $warehouseProduct['barcode'],
                'Test Description',
                0, 0, null, $transfer_qty, 0.00, 1, 1,
                $convenience['location_id'], 1, 'active', 'Test Variation',
                $transfer_qty <= 0 ? 'out of stock' : ($transfer_qty <= 10 ? 'low stock' : 'in stock')
            ]);
            echo "7. Created new product in destination\n";
        }
        
        $pdo->commit();
        echo "8. ✅ Transfer completed successfully!\n\n";
        
        // 9. Verify results
        echo "9. Verification:\n";
        
        // Check source quantity
        $stmt = $pdo->prepare("
            SELECT quantity FROM tbl_product 
            WHERE product_id = ? AND location_id = ?
        ");
        $stmt->execute([$warehouseProduct['product_id'], $warehouse['location_id']]);
        $sourceQty = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "- Source quantity: {$sourceQty['quantity']}\n";
        
        // Check destination quantity
        $stmt = $pdo->prepare("
            SELECT quantity FROM tbl_product 
            WHERE barcode = ? AND location_id = ?
        ");
        $stmt->execute([$warehouseProduct['barcode'], $convenience['location_id']]);
        $destQty = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "- Destination quantity: {$destQty['quantity']}\n";
        
        // Check total products in destination
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tbl_product 
            WHERE location_id = ?
        ");
        $stmt->execute([$convenience['location_id']]);
        $afterCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "- Products in destination after: {$afterCount['count']}\n";
        
        if ($afterCount['count'] > $beforeCount['count']) {
            echo "✅ Transfer successful - products moved to destination\n";
        } else {
            echo "❌ Transfer failed - no products moved to destination\n";
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "❌ Transfer failed: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?> 
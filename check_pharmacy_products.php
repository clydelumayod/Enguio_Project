<?php
// Check products in Pharmacy Store
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Checking Products in Pharmacy Store\n";
    echo "====================================\n\n";
    
    // First, check if Pharmacy Store location exists
    $stmt = $pdo->prepare("SELECT * FROM tbl_location WHERE location_name LIKE '%pharmacy%' OR location_name LIKE '%Pharmacy%'");
    $stmt->execute();
    $pharmacyLocation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pharmacyLocation) {
        echo "❌ Pharmacy Store location not found\n";
        echo "Available locations:\n";
        $stmt = $pdo->query("SELECT * FROM tbl_location ORDER BY location_name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($locations as $location) {
            echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
        }
        exit;
    }
    
    echo "📍 Found Pharmacy Store: {$pharmacyLocation['location_name']} (ID: {$pharmacyLocation['location_id']})\n\n";
    
    // Check products in Pharmacy Store
    $stmt = $pdo->prepare("
        SELECT 
            product_id,
            product_name,
            barcode,
            quantity,
            unit_price,
            category,
            brand,
            supplier_id,
            status,
            stock_status,
            date_added
        FROM tbl_product 
        WHERE location_id = ?
        ORDER BY product_name
    ");
    $stmt->execute([$pharmacyLocation['location_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📦 Products in Pharmacy Store:\n";
    echo "==============================\n";
    
    if (count($products) > 0) {
        $totalProducts = 0;
        $totalValue = 0;
        
        foreach ($products as $product) {
            $totalProducts += $product['quantity'];
            $totalValue += ($product['quantity'] * $product['unit_price']);
            
            echo "- {$product['product_name']}\n";
            echo "  📊 Quantity: {$product['quantity']}\n";
            echo "  🏷️  Barcode: {$product['barcode']}\n";
            echo "  💰 Unit Price: ₱{$product['unit_price']}\n";
            echo "  📂 Category: {$product['category']}\n";
            echo "  🏢 Brand: {$product['brand']}\n";
            echo "  📈 Stock Status: {$product['stock_status']}\n";
            echo "  📅 Date Added: {$product['date_added']}\n";
            echo "  ---\n";
        }
        
        echo "\n📊 Summary:\n";
        echo "- Total Products: " . count($products) . " different items\n";
        echo "- Total Quantity: {$totalProducts} units\n";
        echo "- Total Value: ₱" . number_format($totalValue, 2) . "\n";
        
    } else {
        echo "❌ No products found in Pharmacy Store\n";
    }
    
    // Check recent transfers to Pharmacy Store
    echo "\n🔄 Recent Transfers to Pharmacy Store:\n";
    echo "=====================================\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            th.transfer_header_id,
            th.date,
            sl.location_name as source_name,
            dl.location_name as dest_name,
            th.status,
            COUNT(td.product_id) as product_count,
            SUM(td.qty) as total_qty
        FROM tbl_transfer_header th
        LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
        LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
        LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
        WHERE dl.location_id = ?
        GROUP BY th.transfer_header_id
        ORDER BY th.date DESC
        LIMIT 10
    ");
    $stmt->execute([$pharmacyLocation['location_id']]);
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($transfers) > 0) {
        foreach ($transfers as $transfer) {
            echo "- Transfer {$transfer['transfer_header_id']}: {$transfer['source_name']} → {$transfer['dest_name']}\n";
            echo "  📦 Products: {$transfer['product_count']} items, {$transfer['total_qty']} total units\n";
            echo "  📅 Date: {$transfer['date']}\n";
            echo "  ✅ Status: {$transfer['status']}\n";
            echo "  ---\n";
        }
    } else {
        echo "❌ No transfers found to Pharmacy Store\n";
    }
    
    // Check if there are any products that should be in pharmacy but aren't
    echo "\n🔍 Checking for Medicine Products:\n";
    echo "================================\n";
    
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.barcode,
            p.quantity,
            l.location_name,
            p.category
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE (p.category LIKE '%medicine%' OR p.category LIKE '%Medicine%' OR p.product_name LIKE '%tablet%' OR p.product_name LIKE '%syrup%' OR p.product_name LIKE '%capsule%')
        AND p.quantity > 0
        ORDER BY l.location_name, p.product_name
    ");
    $medicineProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($medicineProducts) > 0) {
        echo "Found medicine-related products:\n";
        foreach ($medicineProducts as $product) {
            $inPharmacy = ($product['location_name'] == $pharmacyLocation['location_name']) ? "✅" : "❌";
            echo "{$inPharmacy} {$product['product_name']} ({$product['quantity']} qty) - {$product['location_name']}\n";
        }
    } else {
        echo "No medicine-related products found\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?> 
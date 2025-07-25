<?php
// Simple script to check where products are located
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Product Location Check\n";
    echo "=======================\n\n";
    
    // Check all locations
    $stmt = $pdo->query("SELECT * FROM tbl_location ORDER BY location_name");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available Locations:\n";
    foreach ($locations as $location) {
        echo "- {$location['location_name']} (ID: {$location['location_id']})\n";
    }
    echo "\n";
    
    // Check products in each location
    foreach ($locations as $location) {
        $stmt = $pdo->prepare("
            SELECT 
                product_id,
                product_name,
                barcode,
                quantity,
                location_id
            FROM tbl_product 
            WHERE location_id = ? AND quantity > 0
            ORDER BY product_name
        ");
        $stmt->execute([$location['location_id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Products in {$location['location_name']}:\n";
        if (count($products) > 0) {
            foreach ($products as $product) {
                echo "  - {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
            }
        } else {
            echo "  (No products)\n";
        }
        echo "\n";
    }
    
    // Check recent transfers
    echo "Recent Transfers:\n";
    $stmt = $pdo->query("
        SELECT 
            th.transfer_header_id,
            th.date,
            sl.location_name as source_name,
            dl.location_name as dest_name,
            th.status,
            COUNT(td.product_id) as product_count
        FROM tbl_transfer_header th
        LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
        LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
        LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
        GROUP BY th.transfer_header_id
        ORDER BY th.date DESC
        LIMIT 5
    ");
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($transfers as $transfer) {
        echo "- Transfer {$transfer['transfer_header_id']}: {$transfer['source_name']} → {$transfer['dest_name']} ({$transfer['product_count']} products, {$transfer['status']})\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?> 
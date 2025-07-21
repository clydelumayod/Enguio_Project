<?php
// Simple Pharmacy Store Check - can be accessed via web browser
// Access this at: http://localhost/Enguio_Project/simple_pharmacy_check.php

echo "<h2>🔍 Pharmacy Store Products Check</h2>";
echo "<hr>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check all locations
    $stmt = $pdo->query("SELECT * FROM tbl_location ORDER BY location_name");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📍 Available Locations:</h3>";
    echo "<ul>";
    foreach ($locations as $location) {
        echo "<li><strong>{$location['location_name']}</strong> (ID: {$location['location_id']})</li>";
    }
    echo "</ul>";
    
    // Find Pharmacy Store
    $pharmacyLocation = null;
    foreach ($locations as $location) {
        if (stripos($location['location_name'], 'pharmacy') !== false) {
            $pharmacyLocation = $location;
            break;
        }
    }
    
    if (!$pharmacyLocation) {
        echo "<p style='color: red;'>❌ Pharmacy Store location not found!</p>";
        echo "<p>Available locations:</p><ul>";
        foreach ($locations as $location) {
            echo "<li>{$location['location_name']}</li>";
        }
        echo "</ul>";
        exit;
    }
    
    echo "<h3>✅ Found Pharmacy Store: <span style='color: green;'>{$pharmacyLocation['location_name']}</span></h3>";
    
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
            stock_status
        FROM tbl_product 
        WHERE location_id = ?
        ORDER BY product_name
    ");
    $stmt->execute([$pharmacyLocation['location_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📦 Products in Pharmacy Store:</h3>";
    
    if (count($products) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Product Name</th><th>Barcode</th><th>Quantity</th><th>Unit Price</th><th>Category</th><th>Brand</th><th>Stock Status</th>";
        echo "</tr>";
        
        $totalProducts = 0;
        $totalValue = 0;
        
        foreach ($products as $product) {
            $totalProducts += $product['quantity'];
            $totalValue += ($product['quantity'] * $product['unit_price']);
            
            $stockColor = ($product['stock_status'] == 'in stock') ? 'green' : 
                         (($product['stock_status'] == 'low stock') ? 'orange' : 'red');
            
            echo "<tr>";
            echo "<td>{$product['product_name']}</td>";
            echo "<td>{$product['barcode']}</td>";
            echo "<td>{$product['quantity']}</td>";
            echo "<td>₱{$product['unit_price']}</td>";
            echo "<td>{$product['category']}</td>";
            echo "<td>{$product['brand']}</td>";
            echo "<td style='color: {$stockColor};'>{$product['stock_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>📊 Summary:</h4>";
        echo "<ul>";
        echo "<li><strong>Total Products:</strong> " . count($products) . " different items</li>";
        echo "<li><strong>Total Quantity:</strong> {$totalProducts} units</li>";
        echo "<li><strong>Total Value:</strong> ₱" . number_format($totalValue, 2) . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: orange;'>❌ No products found in Pharmacy Store</p>";
    }
    
    // Check recent transfers to Pharmacy Store
    echo "<h3>🔄 Recent Transfers to Pharmacy Store:</h3>";
    
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
        LIMIT 5
    ");
    $stmt->execute([$pharmacyLocation['location_id']]);
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($transfers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Transfer ID</th><th>Date</th><th>From</th><th>To</th><th>Products</th><th>Total Qty</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($transfers as $transfer) {
            echo "<tr>";
            echo "<td>{$transfer['transfer_header_id']}</td>";
            echo "<td>{$transfer['date']}</td>";
            echo "<td>{$transfer['source_name']}</td>";
            echo "<td>{$transfer['dest_name']}</td>";
            echo "<td>{$transfer['product_count']}</td>";
            echo "<td>{$transfer['total_qty']}</td>";
            echo "<td>{$transfer['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>❌ No transfers found to Pharmacy Store</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?> 
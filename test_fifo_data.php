<?php
// Test script to check FIFO data
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=enguio2', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Connection Test ===\n";
    echo "Connected successfully!\n\n";
    
    // Check stock summary records
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM tbl_stock_summary');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Stock Summary Records: " . $result['count'] . "\n";
    
    // Check batch records
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM tbl_batch');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Batch Records: " . $result['count'] . "\n";
    
    // Check products with stock
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM tbl_product WHERE status = "active"');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Active Products: " . $result['count'] . "\n";
    
    // Show sample products
    echo "\n=== Sample Products ===\n";
    $stmt = $pdo->query('SELECT product_id, product_name, barcode FROM tbl_product WHERE status = "active" LIMIT 5');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        echo "ID: {$product['product_id']}, Name: {$product['product_name']}, Barcode: {$product['barcode']}\n";
    }
    
    // Check if any products have stock summary
    echo "\n=== Products with Stock Summary ===\n";
    $stmt = $pdo->query('
        SELECT p.product_id, p.product_name, COUNT(ss.summary_id) as stock_records
        FROM tbl_product p
        LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        WHERE p.status = "active"
        GROUP BY p.product_id, p.product_name
        HAVING stock_records > 0
        LIMIT 5
    ');
    $productsWithStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productsWithStock)) {
        echo "No products have stock summary records!\n";
    } else {
        foreach ($productsWithStock as $product) {
            echo "ID: {$product['product_id']}, Name: {$product['product_name']}, Stock Records: {$product['stock_records']}\n";
        }
    }
    
    // Test FIFO view
    echo "\n=== FIFO View Test ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM v_fifo_stock');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "FIFO View Records: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT * FROM v_fifo_stock LIMIT 3');
        $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample FIFO Data:\n";
        foreach ($fifoData as $row) {
            echo "Product: {$row['product_name']}, Batch: {$row['batch_id']}, Qty: {$row['available_quantity']}\n";
        }
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
<?php
// Simple FIFO Test
require_once 'Api/conn_mysqli.php';

echo "<h2>Simple FIFO Test</h2>";

// Find Century Tuna product
$productStmt = $conn->prepare("
    SELECT product_id, product_name, quantity, location_id 
    FROM tbl_product 
    WHERE product_name LIKE '%Century Tuna%' OR product_name LIKE '%tuna%'
    LIMIT 5
");
$productStmt->execute();
$productResult = $productStmt->get_result();

echo "<h3>Found Products:</h3>";
while ($product = $productResult->fetch_assoc()) {
    echo "<p><strong>Product:</strong> {$product['product_name']} (ID: {$product['product_id']}, Qty: {$product['quantity']}, Location: {$product['location_id']})</p>";
    
    // Check stock summary for this product
    $stockStmt = $conn->prepare("
        SELECT COUNT(*) as count, SUM(available_quantity) as total_qty
        FROM tbl_stock_summary 
        WHERE product_id = ?
    ");
    $stockStmt->bind_param("i", $product['product_id']);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();
    $stockData = $stockResult->fetch_assoc();
    
    echo "<p>Stock Summary: {$stockData['count']} entries, Total Qty: {$stockData['total_qty']}</p>";
    
    // Check FIFO query for this product
    $fifoStmt = $conn->prepare("
        SELECT COUNT(*) as count, SUM(ss.available_quantity) as total_qty
        FROM tbl_stock_summary ss
        JOIN tbl_batch b ON ss.batch_id = b.batch_id
        WHERE ss.product_id = ? 
        AND b.location_id = ?
        AND ss.available_quantity > 0
    ");
    $fifoStmt->bind_param("ii", $product['product_id'], $product['location_id']);
    $fifoStmt->execute();
    $fifoResult = $fifoStmt->get_result();
    $fifoData = $fifoResult->fetch_assoc();
    
    echo "<p>FIFO Query: {$fifoData['count']} entries, Total Qty: {$fifoData['total_qty']}</p>";
    echo "<hr>";
}

$conn->close();
?> 
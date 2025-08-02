<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=enguio2', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for Mang Juan product
    $stmt = $pdo->query("SELECT * FROM tbl_product WHERE product_name LIKE '%Mang Juan%'");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Mang Juan products found: " . count($result) . "\n";
    if (count($result) > 0) {
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
    
    // Check total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_product");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal products in database: " . $total['total'] . "\n";
    
    // Check recent products
    $stmt = $pdo->query("SELECT product_id, product_name, date_added FROM tbl_product ORDER BY product_id DESC LIMIT 10");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent products:\n";
    foreach ($recent as $product) {
        echo "ID: " . $product['product_id'] . ", Name: " . $product['product_name'] . ", Date: " . $product['date_added'] . "\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 
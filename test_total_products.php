<?php
// Include DB connection
include 'Api/index.php';

try {
    // Test 1: Get total products without any location filter
    $stmt1 = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE (status IS NULL OR status <> 'archived')");
    $stmt1->execute();
    $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    // Test 2: Get products by location
    $stmt2 = $conn->prepare("
        SELECT l.location_name, COUNT(p.product_id) as count 
        FROM tbl_product p 
        LEFT JOIN tbl_location l ON p.location_id = l.location_id 
        WHERE (p.status IS NULL OR p.status <> 'archived')
        GROUP BY l.location_name, l.location_id
    ");
    $stmt2->execute();
    $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 3: Get first few products to see their structure
    $stmt3 = $conn->prepare("SELECT product_id, product_name, location_id, quantity FROM tbl_product LIMIT 5");
    $stmt3->execute();
    $result3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Database Test Results</h2>";
    echo "<p><strong>Total Products (all locations):</strong> " . $result1['total'] . "</p>";
    
    echo "<p><strong>Products by Location:</strong></p>";
    echo "<ul>";
    foreach ($result2 as $row) {
        echo "<li>" . ($row['location_name'] ?? 'Unknown') . ": " . $row['count'] . " products</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>Sample Products:</strong></p>";
    echo "<ul>";
    foreach ($result3 as $row) {
        echo "<li>ID: " . $row['product_id'] . ", Name: " . $row['product_name'] . ", Location: " . $row['location_id'] . ", Qty: " . $row['quantity'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 
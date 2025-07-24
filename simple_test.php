<?php
// Simple database connection test
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test 1: Simple count query
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total Products:</strong> " . $result['total'] . "</p>";
    
    // Test 2: Check if warehouse location exists
    $stmt = $conn->prepare("SELECT * FROM tbl_location WHERE location_id = 2");
    $stmt->execute();
    $warehouse = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Warehouse Location:</strong> " . ($warehouse ? $warehouse['location_name'] : 'Not found') . "</p>";
    
    // Test 3: Check products in warehouse
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE location_id = 2");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Products in Warehouse (location_id = 2):</strong> " . $result['total'] . "</p>";
    
    // Test 4: Check all locations and their product counts
    $stmt = $conn->prepare("
        SELECT l.location_name, COUNT(p.product_id) as count 
        FROM tbl_location l 
        LEFT JOIN tbl_product p ON l.location_id = p.location_id 
        GROUP BY l.location_id, l.location_name
        ORDER BY l.location_id
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Products by Location:</strong></p>";
    echo "<ul>";
    foreach ($locations as $loc) {
        echo "<li>" . $loc['location_name'] . ": " . $loc['count'] . " products</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h2>Database Connection Test</h2>";
    echo "<p style='color: red;'>✗ Connection failed: " . $e->getMessage() . "</p>";
}
?> 
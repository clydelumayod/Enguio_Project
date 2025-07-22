<?php
// Simple database connection test
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h2>Database Connection Test</h2>";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful!</p>";

    // Test 1: Get all products count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total Products in Database:</strong> " . $result['total'] . "</p>";

    // Test 2: Get warehouse products count (location_id = 2)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE location_id = 2 AND (status IS NULL OR status <> 'archived')");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Warehouse Products (location_id = 2):</strong> " . $result['total'] . "</p>";

    // Test 3: Get products by location
    $stmt = $conn->prepare("
        SELECT l.location_name, COUNT(p.product_id) as product_count 
        FROM tbl_location l 
        LEFT JOIN tbl_product p ON l.location_id = p.location_id 
        WHERE (p.status IS NULL OR p.status <> 'archived')
        GROUP BY l.location_id, l.location_name
        ORDER BY l.location_id
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Products by Location:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Location</th><th>Product Count</th></tr>";
    foreach ($results as $row) {
        echo "<tr><td>" . $row['location_name'] . "</td><td>" . $row['product_count'] . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 
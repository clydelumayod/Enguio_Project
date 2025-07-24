<?php
// Direct database check for warehouse products
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Warehouse Products Check</h2>";
    
    // Check 1: All locations
    $stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_id");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Locations:</h3>";
    echo "<ul>";
    foreach ($locations as $loc) {
        echo "<li>ID: " . $loc['location_id'] . " - " . $loc['location_name'] . "</li>";
    }
    echo "</ul>";
    
    // Check 2: Total products in database
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE (status IS NULL OR status <> 'archived')");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total Products (all locations):</strong> " . $result['total'] . "</p>";
    
    // Check 3: Products by location
    $stmt = $conn->prepare("
        SELECT l.location_name, COUNT(p.product_id) as count 
        FROM tbl_location l 
        LEFT JOIN tbl_product p ON l.location_id = p.location_id 
        WHERE (p.status IS NULL OR p.status <> 'archived') OR p.status IS NULL
        GROUP BY l.location_id, l.location_name
        ORDER BY l.location_id
    ");
    $stmt->execute();
    $locationCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Products by Location:</h3>";
    echo "<ul>";
    foreach ($locationCounts as $loc) {
        echo "<li>" . $loc['location_name'] . ": " . $loc['count'] . " products</li>";
    }
    echo "</ul>";
    
    // Check 4: Sample warehouse products (location_id = 2)
    $stmt = $conn->prepare("
        SELECT product_id, product_name, location_id, quantity, status 
        FROM tbl_product 
        WHERE location_id = 2 
        LIMIT 10
    ");
    $stmt->execute();
    $warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Warehouse Products (location_id = 2):</h3>";
    echo "<ul>";
    foreach ($warehouseProducts as $product) {
        echo "<li>ID: " . $product['product_id'] . ", Name: " . $product['product_name'] . 
             ", Location: " . $product['location_id'] . ", Qty: " . $product['quantity'] . 
             ", Status: " . ($product['status'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
    
    // Check 5: Test the exact warehouse KPIs query
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT p.product_id) as totalProducts,
            COUNT(DISTINCT s.supplier_id) as totalSuppliers,
            ROUND(COUNT(DISTINCT p.product_id) * 100.0 / 1000, 1) as storageCapacity,
            SUM(p.quantity * p.unit_price) as warehouseValue,
            COUNT(CASE WHEN p.quantity <= 10 AND p.quantity > 0 THEN 1 END) as lowStockItems,
            COUNT(CASE WHEN p.expiration IS NOT NULL AND p.expiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiringSoon,
            COUNT(DISTINCT b.batch_id) as totalBatches,
            COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as activeTransfers
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        LEFT JOIN tbl_transfer t ON p.product_id = t.product_id
        WHERE (p.status IS NULL OR p.status <> 'archived')
    ");
    $stmt->execute();
    $warehouseKPIs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Warehouse KPIs (All Products):</h3>";
    echo "<ul>";
    foreach ($warehouseKPIs as $key => $value) {
        echo "<li><strong>" . $key . ":</strong> " . $value . "</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 
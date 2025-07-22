<?php
// Test script to get total products from database
include 'Api/index.php';

echo "<h2>Database Connection Test</h2>";

try {
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

    // Test 4: Test the warehouse KPIs query
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
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    foreach ($warehouseKPIs as $key => $value) {
        echo "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
    }
    echo "</table>";

    // Test 5: Warehouse-only KPIs (location_id = 2)
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
        WHERE (p.status IS NULL OR p.status <> 'archived') AND p.location_id = 2
    ");
    $stmt->execute();
    $warehouseOnlyKPIs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Warehouse-Only KPIs (location_id = 2):</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    foreach ($warehouseOnlyKPIs as $key => $value) {
        echo "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 
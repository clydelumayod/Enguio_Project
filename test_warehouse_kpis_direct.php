<?php
// Direct test of warehouse KPIs API
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    echo "<h2>Direct Warehouse KPIs Test</h2>";
    
    // Test the exact query from the API
    $query = "
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
        LEFT JOIN tbl_transfer_dtl td ON p.product_id = td.product_id
        LEFT JOIN tbl_transfer_header t ON td.transfer_header_id = t.transfer_header_id
        WHERE (p.status IS NULL OR p.status <> 'archived')
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    $warehouseKPIs = mysqli_fetch_assoc($result);
    
    echo "<h3>Query Results:</h3>";
    echo "<ul>";
    foreach ($warehouseKPIs as $key => $value) {
        echo "<li><strong>" . $key . ":</strong> " . $value . "</li>";
    }
    echo "</ul>";
    
    // Test JSON encoding
    $jsonResponse = json_encode($warehouseKPIs);
    echo "<h3>JSON Response:</h3>";
    echo "<pre>" . htmlspecialchars($jsonResponse) . "</pre>";
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p style='color: red;'>JSON Error: " . json_last_error_msg() . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Valid JSON</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}

mysqli_close($conn);
?> 
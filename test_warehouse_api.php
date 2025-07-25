<?php
// Test the warehouse API endpoint
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h2>Warehouse API Test</h2>";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful!</p>";

    // Test the warehouse KPIs query directly
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
    $warehouseKPIs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Warehouse KPIs (location_id = 2):</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    foreach ($warehouseKPIs as $key => $value) {
        echo "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
    }
    echo "</table>";

    // Test API call via HTTP
    echo "<h3>Testing API Call:</h3>";
    
    $apiUrl = "http://localhost/Enguio_Project/Api/backend.php";
    $postData = json_encode([
        'action' => 'get_warehouse_kpis',
        'product' => 'All',
        'location' => 'Warehouse'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Status:</strong> " . $httpCode . "</p>";
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($response) {
        $apiData = json_decode($response, true);
        if ($apiData && isset($apiData['totalProducts'])) {
            echo "<p style='color: green;'>✓ API returned totalProducts: " . $apiData['totalProducts'] . "</p>";
        } else {
            echo "<p style='color: red;'>✗ API response format issue</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 
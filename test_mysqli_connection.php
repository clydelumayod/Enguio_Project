<?php
// Test mysqli connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    echo "Connection failed: " . mysqli_connect_error();
    exit;
}

echo "Database connection successful!<br>";

// Test simple query
$query = "SELECT COUNT(*) as total FROM tbl_product WHERE location_id = 2";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total products in warehouse: " . $row['total'] . "<br>";
} else {
    echo "Query failed: " . mysqli_error($conn) . "<br>";
}

// Test warehouse KPIs query
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

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<h3>Warehouse KPIs:</h3>";
    echo "<ul>";
    foreach ($row as $key => $value) {
        echo "<li><strong>" . $key . ":</strong> " . $value . "</li>";
    }
    echo "</ul>";
} else {
    echo "Warehouse KPIs query failed: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?> 
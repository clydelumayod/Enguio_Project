<?php
// Check stock_summary table structure and data
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h1>Stock Summary Table Check</h1>";
    
    // Check table structure
    echo "<h2>Table Structure:</h2>";
    $result = $conn->query("DESCRIBE tbl_stock_summary");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check sample data
    echo "<h2>Sample Data (first 10 records):</h2>";
    $result = $conn->query("SELECT * FROM tbl_stock_summary LIMIT 10");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Summary ID</th><th>Product ID</th><th>Batch ID</th><th>Available Qty</th><th>Total Qty</th><th>Unit Cost</th><th>Expiry Date</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['summary_id'] . "</td>";
            echo "<td>" . $row['product_id'] . "</td>";
            echo "<td>" . $row['batch_id'] . "</td>";
            echo "<td>" . $row['available_quantity'] . "</td>";
            echo "<td>" . $row['total_quantity'] . "</td>";
            echo "<td>" . $row['unit_cost'] . "</td>";
            echo "<td>" . $row['expiration_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in tbl_stock_summary</p>";
    }
    
    // Check products with multiple batches
    echo "<h2>Products with Multiple Batches:</h2>";
    $sql = "
        SELECT 
            p.product_name,
            COUNT(ss.batch_id) as batch_count,
            SUM(ss.available_quantity) as total_available
        FROM tbl_product p
        JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        WHERE ss.available_quantity > 0
        GROUP BY p.product_id, p.product_name
        HAVING COUNT(ss.batch_id) > 1
        ORDER BY batch_count DESC
        LIMIT 10
    ";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Product Name</th><th>Batch Count</th><th>Total Available</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . $row['batch_count'] . "</td>";
            echo "<td>" . $row['total_available'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No products with multiple batches found</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
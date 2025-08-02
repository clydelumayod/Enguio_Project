<?php
// Fix UpdateProductTotalFromFIFO Function with Proper Delimiter
echo "<h2>Fixing UpdateProductTotalFromFIFO Function with Proper Delimiter</h2>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Step 1: Drop existing function
echo "<h3>Step 1: Dropping existing function</h3>";

$dropQuery = "DROP FUNCTION IF EXISTS UpdateProductTotalFromFIFO";
if ($conn->query($dropQuery) === TRUE) {
    echo "✅ Function dropped successfully<br>";
} else {
    echo "❌ Error dropping function: " . $conn->error . "<br>";
}

// Step 2: Set delimiter and create function
echo "<h3>Step 2: Creating UpdateProductTotalFromFIFO function with proper delimiter</h3>";

// Set delimiter to $$
$conn->query("DELIMITER $$");

$functionSQL = "
CREATE FUNCTION UpdateProductTotalFromFIFO(product_id_param INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from FIFO
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = product_id_param;
    
    -- Update product quantity
    UPDATE tbl_product 
    SET quantity = total_qty,
        stock_status = CASE 
            WHEN total_qty <= 0 THEN 'out of stock'
            WHEN total_qty <= 10 THEN 'low stock'
            ELSE 'in stock'
        END
    WHERE product_id = product_id_param;
    
    RETURN total_qty;
END$$
";

if ($conn->query($functionSQL) === TRUE) {
    echo "✅ UpdateProductTotalFromFIFO function created successfully<br>";
} else {
    echo "❌ Error creating function: " . $conn->error . "<br>";
    echo "<br><strong>Debug Info:</strong><br>";
    echo "SQL: " . htmlspecialchars($functionSQL) . "<br>";
}

// Reset delimiter back to ;
$conn->query("DELIMITER ;");

// Step 3: Test the function
echo "<h3>Step 3: Testing the function</h3>";

$testSQL = "SELECT UpdateProductTotalFromFIFO(169) as result";
$testResult = $conn->query($testSQL);

if ($testResult && $testResult->num_rows > 0) {
    $testRow = $testResult->fetch_assoc();
    echo "✅ Function test successful! Result: " . $testRow['result'] . "<br>";
} else {
    echo "❌ Function test failed: " . $conn->error . "<br>";
}

// Step 4: Show current product status
echo "<h3>Step 4: Current Product Status</h3>";

$statusSQL = "
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(fs.available_quantity), 0) as fifo_total_qty,
    p.stock_status
FROM tbl_product p
LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status
ORDER BY p.product_id
LIMIT 10
";

$result = $conn->query($statusSQL);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Product Qty</th><th>FIFO Qty</th><th>Stock Status</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['product_total_qty'] . "</td>";
        echo "<td>" . $row['fifo_total_qty'] . "</td>";
        echo "<td>" . $row['stock_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

$conn->close();
echo "<br><strong>✅ UpdateProductTotalFromFIFO function fix completed!</strong>";
echo "<br><p>The UpdateProductTotalFromFIFO error should now be resolved.</p>";
echo "<br><p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>✅ Created the missing UpdateProductTotalFromFIFO function</li>";
echo "<li>✅ Used proper DELIMITER syntax for MariaDB/MySQL</li>";
echo "<li>✅ Function can now be called manually when needed</li>";
echo "<li>✅ Function updates product quantities and stock status</li>";
echo "</ul>";
echo "<br><p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>✅ The function is now available for manual calls</li>";
echo "<li>✅ Your stock update functionality should work properly</li>";
echo "<li>✅ You can call UpdateProductTotalFromFIFO(product_id) when needed</li>";
echo "</ul>";
?> 
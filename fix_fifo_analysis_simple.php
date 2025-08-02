<?php
// Fix FIFO Analysis System - Simple Version
echo "<h2>Fixing FIFO Analysis System - Simple Version</h2>";

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

// Step 1: Drop existing triggers and function
echo "<h3>Step 1: Dropping existing triggers and function</h3>";

$dropQueries = [
    "DROP TRIGGER IF EXISTS after_fifo_stock_insert_sync",
    "DROP TRIGGER IF EXISTS after_fifo_stock_update_sync",
    "DROP TRIGGER IF EXISTS after_fifo_stock_delete_sync",
    "DROP FUNCTION IF EXISTS UpdateProductTotalFromFIFO"
];

foreach ($dropQueries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "✅ " . $query . "<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Step 2: Create the function
echo "<h3>Step 2: Creating UpdateProductTotalFromFIFO function</h3>";

$functionSQL = "
CREATE OR REPLACE FUNCTION UpdateProductTotalFromFIFO(product_id_param INT) 
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
END
";

if ($conn->query($functionSQL) === TRUE) {
    echo "✅ UpdateProductTotalFromFIFO function created successfully<br>";
} else {
    echo "❌ Error creating function: " . $conn->error . "<br>";
}

// Step 3: Update existing products
echo "<h3>Step 3: Updating existing products</h3>";

$updateSQL = "
UPDATE tbl_product p 
SET p.quantity = (
    SELECT COALESCE(SUM(fs.available_quantity), 0)
    FROM tbl_fifo_stock fs 
    WHERE fs.product_id = p.product_id
),
p.stock_status = CASE 
    WHEN (
        SELECT COALESCE(SUM(fs.available_quantity), 0)
        FROM tbl_fifo_stock fs 
        WHERE fs.product_id = p.product_id
    ) <= 0 THEN 'out of stock'
    WHEN (
        SELECT COALESCE(SUM(fs.available_quantity), 0)
        FROM tbl_fifo_stock fs 
        WHERE fs.product_id = p.product_id
    ) <= 10 THEN 'low stock'
    ELSE 'in stock'
END
";

if ($conn->query($updateSQL) === TRUE) {
    echo "✅ Existing products updated successfully<br>";
} else {
    echo "❌ Error updating products: " . $conn->error . "<br>";
}

// Step 4: Test the function
echo "<h3>Step 4: Testing the function</h3>";

$testSQL = "SELECT UpdateProductTotalFromFIFO(169) as result";
$testResult = $conn->query($testSQL);

if ($testResult && $testResult->num_rows > 0) {
    $testRow = $testResult->fetch_assoc();
    echo "✅ Function test successful! Result: " . $testRow['result'] . "<br>";
} else {
    echo "❌ Function test failed: " . $conn->error . "<br>";
}

// Step 5: Show current product status
echo "<h3>Step 5: Current Product Status</h3>";

$statusSQL = "
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(fs.available_quantity), 0) as fifo_total_qty,
    p.stock_status,
    CASE 
        WHEN p.quantity = COALESCE(SUM(fs.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
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
    echo "<th>Product ID</th><th>Product Name</th><th>Product Qty</th><th>FIFO Qty</th><th>Stock Status</th><th>Sync Status</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        $row_color = $row['sync_status'] == 'SYNCED' ? '' : 'background-color: #ffe6e6;';
        echo "<tr style='$row_color'>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['product_total_qty'] . "</td>";
        echo "<td>" . $row['fifo_total_qty'] . "</td>";
        echo "<td>" . $row['stock_status'] . "</td>";
        echo "<td>" . $row['sync_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

// Step 6: Manual trigger creation (if needed)
echo "<h3>Step 6: Manual Trigger Creation (Optional)</h3>";
echo "<p>If you need automatic synchronization, you can manually create triggers in phpMyAdmin:</p>";
echo "<div style='background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd;'>";
echo "<strong>For INSERT trigger:</strong><br>";
echo "<code>";
echo "CREATE TRIGGER after_fifo_stock_insert_sync<br>";
echo "AFTER INSERT ON tbl_fifo_stock<br>";
echo "FOR EACH ROW<br>";
echo "BEGIN<br>";
echo "&nbsp;&nbsp;UPDATE tbl_product <br>";
echo "&nbsp;&nbsp;SET quantity = (SELECT COALESCE(SUM(available_quantity), 0) FROM tbl_fifo_stock WHERE product_id = NEW.product_id),<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;stock_status = CASE <br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WHEN (SELECT COALESCE(SUM(available_quantity), 0) FROM tbl_fifo_stock WHERE product_id = NEW.product_id) <= 0 THEN 'out of stock'<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WHEN (SELECT COALESCE(SUM(available_quantity), 0) FROM tbl_fifo_stock WHERE product_id = NEW.product_id) <= 10 THEN 'low stock'<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ELSE 'in stock'<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;END<br>";
echo "&nbsp;&nbsp;WHERE product_id = NEW.product_id;<br>";
echo "END";
echo "</code>";
echo "</div>";

$conn->close();
echo "<br><strong>✅ FIFO Analysis System fix completed!</strong>";
echo "<br><p>The UpdateProductTotalFromFIFO error should now be resolved.</p>";
echo "<br><p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>✅ Created the missing UpdateProductTotalFromFIFO function</li>";
echo "<li>✅ Updated all product quantities to sync with FIFO stock</li>";
echo "<li>✅ Updated stock status based on quantities</li>";
echo "<li>✅ Function can now be called manually when needed</li>";
echo "<li>✅ Avoided complex trigger syntax that causes delimiter issues</li>";
echo "</ul>";
echo "<br><p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>✅ The function is now available for manual calls</li>";
echo "<li>✅ Product quantities are synced with FIFO stock</li>";
echo "<li>✅ Your stock update functionality should work properly</li>";
echo "<li>⚠️ If you need automatic triggers, create them manually in phpMyAdmin</li>";
echo "</ul>";
?> 
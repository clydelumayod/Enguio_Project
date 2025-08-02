<?php
// Fix FIFO Triggers and UpdateProductTotalQuantity Function
echo "<h2>Fixing FIFO Triggers and UpdateProductTotalQuantity Function</h2>";

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
    "DROP TRIGGER IF EXISTS after_fifo_stock_update",
    "DROP TRIGGER IF EXISTS after_fifo_stock_insert", 
    "DROP TRIGGER IF EXISTS after_fifo_stock_delete",
    "DROP TRIGGER IF EXISTS after_stock_summary_update",
    "DROP TRIGGER IF EXISTS after_stock_summary_insert",
    "DROP TRIGGER IF EXISTS after_stock_summary_delete",
    "DROP FUNCTION IF EXISTS UpdateProductTotalQuantity"
];

foreach ($dropQueries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "✅ " . $query . "<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Step 2: Create the function
echo "<h3>Step 2: Creating UpdateProductTotalQuantity function</h3>";

$functionSQL = "
CREATE FUNCTION UpdateProductTotalQuantity(product_id_param INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from all stock summary for this product
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_stock_summary 
    WHERE product_id = product_id_param;
    
    -- Update the product's total quantity
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
    echo "✅ UpdateProductTotalQuantity function created successfully<br>";
} else {
    echo "❌ Error creating function: " . $conn->error . "<br>";
}

// Step 3: Create triggers
echo "<h3>Step 3: Creating triggers</h3>";

$triggers = [
    "after_stock_summary_update" => "
        CREATE TRIGGER after_stock_summary_update
        AFTER UPDATE ON tbl_stock_summary
        FOR EACH ROW
        BEGIN
            SELECT UpdateProductTotalQuantity(NEW.product_id);
        END
    ",
    "after_stock_summary_insert" => "
        CREATE TRIGGER after_stock_summary_insert
        AFTER INSERT ON tbl_stock_summary
        FOR EACH ROW
        BEGIN
            SELECT UpdateProductTotalQuantity(NEW.product_id);
        END
    ",
    "after_stock_summary_delete" => "
        CREATE TRIGGER after_stock_summary_delete
        AFTER DELETE ON tbl_stock_summary
        FOR EACH ROW
        BEGIN
            SELECT UpdateProductTotalQuantity(OLD.product_id);
        END
    "
];

foreach ($triggers as $triggerName => $triggerSQL) {
    if ($conn->query($triggerSQL) === TRUE) {
        echo "✅ Trigger $triggerName created successfully<br>";
    } else {
        echo "❌ Error creating trigger $triggerName: " . $conn->error . "<br>";
    }
}

// Step 4: Update existing products
echo "<h3>Step 4: Updating existing products</h3>";

$updateSQL = "
UPDATE tbl_product p 
SET p.quantity = (
    SELECT COALESCE(SUM(ss.available_quantity), 0)
    FROM tbl_stock_summary ss 
    WHERE ss.product_id = p.product_id
),
p.stock_status = CASE 
    WHEN (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    ) <= 0 THEN 'out of stock'
    WHEN (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    ) <= 10 THEN 'low stock'
    ELSE 'in stock'
END
";

if ($conn->query($updateSQL) === TRUE) {
    echo "✅ Existing products updated successfully<br>";
} else {
    echo "❌ Error updating products: " . $conn->error . "<br>";
}

// Step 5: Show sync status
echo "<h3>Step 5: Sync Status</h3>";

$statusSQL = "
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(ss.available_quantity), 0) as stock_summary_total_qty,
    p.stock_status,
    CASE 
        WHEN p.quantity = COALESCE(SUM(ss.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
FROM tbl_product p
LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status
ORDER BY p.product_id
LIMIT 10
";

$result = $conn->query($statusSQL);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Product Qty</th><th>Stock Summary Qty</th><th>Stock Status</th><th>Sync Status</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        $row_color = $row['sync_status'] == 'SYNCED' ? '' : 'background-color: #ffe6e6;';
        echo "<tr style='$row_color'>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['product_total_qty'] . "</td>";
        echo "<td>" . $row['stock_summary_total_qty'] . "</td>";
        echo "<td>" . $row['stock_status'] . "</td>";
        echo "<td>" . $row['sync_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

$conn->close();
echo "<br><strong>✅ FIFO triggers and function fix completed!</strong>";
echo "<br><p>The UpdateProductTotalQuantity error should now be resolved.</p>";
?> 
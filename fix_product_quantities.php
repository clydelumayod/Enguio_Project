<?php
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

echo "<h2>Fixing Product Quantities</h2>";

// Step 1: Add new quantity column
echo "<h3>Step 1: Adding new quantity column</h3>";
$sql1 = "ALTER TABLE tbl_product ADD COLUMN current_quantity INT(11) DEFAULT 0 AFTER quantity";
if ($conn->query($sql1) === TRUE) {
    echo "✅ Added current_quantity column successfully<br>";
} else {
    echo "❌ Error adding current_quantity column: " . $conn->error . "<br>";
}

// Step 2: Populate current_quantity from stock_summary
echo "<h3>Step 2: Populating current_quantity from stock_summary</h3>";
$sql2 = "
    UPDATE tbl_product p 
    SET p.current_quantity = (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    )
";
if ($conn->query($sql2) === TRUE) {
    echo "✅ Updated current_quantity from stock_summary successfully<br>";
} else {
    echo "❌ Error updating current_quantity: " . $conn->error . "<br>";
}

// Step 3: Update the main quantity column with current_quantity values
echo "<h3>Step 3: Updating main quantity column</h3>";
$sql3 = "UPDATE tbl_product SET quantity = current_quantity WHERE current_quantity > 0";
if ($conn->query($sql3) === TRUE) {
    echo "✅ Updated main quantity column successfully<br>";
} else {
    echo "❌ Error updating main quantity: " . $conn->error . "<br>";
}

// Step 4: Update stock_status based on quantity
echo "<h3>Step 4: Updating stock_status based on quantity</h3>";
$sql4 = "
    UPDATE tbl_product 
    SET stock_status = CASE 
        WHEN quantity = 0 THEN 'out of stock'
        WHEN quantity <= 10 THEN 'low stock'
        ELSE 'in stock'
    END
";
if ($conn->query($sql4) === TRUE) {
    echo "✅ Updated stock_status successfully<br>";
} else {
    echo "❌ Error updating stock_status: " . $conn->error . "<br>";
}

// Step 5: Show results
echo "<h3>Step 5: Verification - Sample Products</h3>";
$sql5 = "
    SELECT 
        product_id, 
        product_name, 
        quantity, 
        current_quantity, 
        stock_status,
        location_id
    FROM tbl_product 
    ORDER BY product_id 
    LIMIT 10
";

$result = $conn->query($sql5);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th><th>Location</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['current_quantity'] . "</td>";
        echo "<td>" . $row['stock_status'] . "</td>";
        echo "<td>" . $row['location_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

// Step 6: Show summary statistics
echo "<h3>Step 6: Summary Statistics</h3>";
$sql6 = "
    SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as products_with_stock,
        COUNT(CASE WHEN quantity = 0 THEN 1 END) as products_without_stock,
        COUNT(CASE WHEN stock_status = 'in stock' THEN 1 END) as in_stock,
        COUNT(CASE WHEN stock_status = 'low stock' THEN 1 END) as low_stock,
        COUNT(CASE WHEN stock_status = 'out of stock' THEN 1 END) as out_of_stock
    FROM tbl_product
";

$result = $conn->query($sql6);
$stats = $result->fetch_assoc();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'>";
echo "<th>Total Products</th><th>With Stock</th><th>Without Stock</th><th>In Stock</th><th>Low Stock</th><th>Out of Stock</th>";
echo "</tr>";
echo "<tr>";
echo "<td>" . $stats['total_products'] . "</td>";
echo "<td>" . $stats['products_with_stock'] . "</td>";
echo "<td>" . $stats['products_without_stock'] . "</td>";
echo "<td>" . $stats['in_stock'] . "</td>";
echo "<td>" . $stats['low_stock'] . "</td>";
echo "<td>" . $stats['out_of_stock'] . "</td>";
echo "</tr>";
echo "</table>";

$conn->close();
echo "<br><strong>✅ Quantity fix completed!</strong>";
?> 
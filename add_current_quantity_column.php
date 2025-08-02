<?php
// Add current_quantity column to tbl_product table
echo "<h2>Adding current_quantity column to tbl_product table</h2>";

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

// Step 1: Check if current_quantity column already exists
echo "<h3>Step 1: Checking if current_quantity column exists</h3>";
$checkColumn = "SHOW COLUMNS FROM tbl_product LIKE 'current_quantity'";
$result = $conn->query($checkColumn);

if ($result->num_rows > 0) {
    echo "✅ current_quantity column already exists<br>";
} else {
    echo "❌ current_quantity column does not exist, adding it now...<br>";
    
    // Add current_quantity column
    $addColumn = "ALTER TABLE tbl_product ADD COLUMN current_quantity INT(11) DEFAULT 0 AFTER quantity";
    if ($conn->query($addColumn) === TRUE) {
        echo "✅ current_quantity column added successfully<br>";
    } else {
        echo "❌ Error adding current_quantity column: " . $conn->error . "<br>";
    }
}

// Step 2: Initialize current_quantity with existing quantity values
echo "<h3>Step 2: Initializing current_quantity with existing quantities</h3>";
$initCurrent = "UPDATE tbl_product SET current_quantity = quantity WHERE quantity > 0";
if ($conn->query($initCurrent) === TRUE) {
    echo "✅ current_quantity initialized successfully<br>";
} else {
    echo "❌ Error initializing current_quantity: " . $conn->error . "<br>";
}

// Step 3: Show updated table structure
echo "<h3>Step 3: Updated Table Structure</h3>";
$describeTable = "DESCRIBE tbl_product";
$result = $conn->query($describeTable);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
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

// Step 4: Show sample data
echo "<h3>Step 4: Sample Data Verification</h3>";
$sampleData = "
SELECT 
    product_id, 
    product_name, 
    quantity, 
    current_quantity, 
    stock_status
FROM tbl_product 
ORDER BY product_id 
LIMIT 10
";

$result = $conn->query($sampleData);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['current_quantity'] . "</td>";
        echo "<td>" . $row['stock_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

$conn->close();
echo "<br><strong>✅ current_quantity column setup completed!</strong>";
echo "<br><p>The current_quantity column has been added to store newly added product quantities.</p>";
echo "<br><p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>✅ current_quantity column is now available</li>";
echo "<li>✅ Backend API needs to be updated to use current_quantity</li>";
echo "<li>✅ Warehouse.js will now store new quantities in current_quantity</li>";
echo "</ul>";
?> 
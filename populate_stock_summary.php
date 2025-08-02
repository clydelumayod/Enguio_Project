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

echo "<h2>Populating Stock Summary Table</h2>";

// Step 1: Check if stock_summary table is empty
echo "<h3>Step 1: Checking stock_summary table</h3>";
$check_sql = "SELECT COUNT(*) as count FROM tbl_stock_summary";
$result = $conn->query($check_sql);
$count = $result->fetch_assoc()['count'];

echo "Current records in stock_summary: " . $count . "<br>";

if ($count > 0) {
    echo "⚠️ Stock summary table already has data. Skipping population.<br>";
} else {
    // Step 2: Populate stock_summary from existing products
    echo "<h3>Step 2: Populating stock_summary from existing products</h3>";
    
    $populate_sql = "
        INSERT INTO tbl_stock_summary (
            product_id, 
            batch_id, 
            available_quantity, 
            reserved_quantity, 
            total_quantity, 
            unit_cost, 
            expiration_date, 
            batch_reference
        )
        SELECT 
            p.product_id,
            p.batch_id,
            p.quantity as available_quantity,
            0 as reserved_quantity,
            p.quantity as total_quantity,
            p.unit_price as unit_cost,
            p.expiration as expiration_date,
            b.batch_reference
        FROM tbl_product p
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        WHERE p.quantity > 0
    ";
    
    if ($conn->query($populate_sql) === TRUE) {
        echo "✅ Populated stock_summary table successfully<br>";
        
        // Show how many records were inserted
        $inserted_sql = "SELECT COUNT(*) as count FROM tbl_stock_summary";
        $result = $conn->query($inserted_sql);
        $inserted_count = $result->fetch_assoc()['count'];
        echo "Total records in stock_summary: " . $inserted_count . "<br>";
    } else {
        echo "❌ Error populating stock_summary: " . $conn->error . "<br>";
    }
}

// Step 3: Show sample data from stock_summary
echo "<h3>Step 3: Sample Stock Summary Data</h3>";
$sample_sql = "
    SELECT 
        ss.summary_id,
        ss.product_id,
        p.product_name,
        ss.batch_id,
        ss.available_quantity,
        ss.unit_cost,
        ss.expiration_date,
        ss.batch_reference
    FROM tbl_stock_summary ss
    JOIN tbl_product p ON ss.product_id = p.product_id
    ORDER BY ss.summary_id
    LIMIT 10
";

$result = $conn->query($sample_sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Summary ID</th><th>Product ID</th><th>Product Name</th><th>Batch ID</th><th>Available Qty</th><th>Unit Cost</th><th>Expiration</th><th>Batch Ref</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['summary_id'] . "</td>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['available_quantity'] . "</td>";
        echo "<td>" . $row['unit_cost'] . "</td>";
        echo "<td>" . $row['expiration_date'] . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No stock summary data found";
}

// Step 4: Show summary statistics
echo "<h3>Step 4: Summary Statistics</h3>";
$stats_sql = "
    SELECT 
        COUNT(*) as total_summaries,
        SUM(available_quantity) as total_available,
        SUM(reserved_quantity) as total_reserved,
        SUM(total_quantity) as total_quantity,
        COUNT(DISTINCT product_id) as unique_products,
        COUNT(DISTINCT batch_id) as unique_batches
    FROM tbl_stock_summary
";

$result = $conn->query($stats_sql);
$stats = $result->fetch_assoc();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'>";
echo "<th>Total Summaries</th><th>Total Available</th><th>Total Reserved</th><th>Total Quantity</th><th>Unique Products</th><th>Unique Batches</th>";
echo "</tr>";
echo "<tr>";
echo "<td>" . $stats['total_summaries'] . "</td>";
echo "<td>" . $stats['total_available'] . "</td>";
echo "<td>" . $stats['total_reserved'] . "</td>";
echo "<td>" . $stats['total_quantity'] . "</td>";
echo "<td>" . $stats['unique_products'] . "</td>";
echo "<td>" . $stats['unique_batches'] . "</td>";
echo "</tr>";
echo "</table>";

$conn->close();
echo "<br><strong>✅ Stock summary population completed!</strong>";
?> 
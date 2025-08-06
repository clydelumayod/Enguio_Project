<?php
// Test FIFO Debug Script
// This script will help us understand why FIFO validation is failing

require_once 'Api/conn_mysqli.php';

echo "<h2>FIFO Stock Debug Test</h2>";

// Test parameters - you can change these
$test_product_id = 1; // Change this to the actual product ID for Century Tuna
$test_location_id = 2; // Warehouse location ID

echo "<h3>Test Parameters:</h3>";
echo "<p><strong>Product ID:</strong> $test_product_id</p>";
echo "<p><strong>Location ID:</strong> $test_location_id</p>";

// 1. Check if product exists
echo "<h3>1. Product Information:</h3>";
$productStmt = $conn->prepare("
    SELECT product_id, product_name, quantity, location_id, stock_status
    FROM tbl_product 
    WHERE product_id = ?
");
$productStmt->bind_param("i", $test_product_id);
$productStmt->execute();
$productResult = $productStmt->get_result();

if ($productResult->num_rows > 0) {
    $product = $productResult->fetch_assoc();
    echo "<p><strong>Product Found:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $product['product_id'] . "</li>";
    echo "<li>Name: " . $product['product_name'] . "</li>";
    echo "<li>Quantity: " . $product['quantity'] . "</li>";
    echo "<li>Location ID: " . $product['location_id'] . "</li>";
    echo "<li>Stock Status: " . $product['stock_status'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>Product not found!</strong></p>";
}

// 2. Check stock summary
echo "<h3>2. Stock Summary Information:</h3>";
$stockStmt = $conn->prepare("
    SELECT summary_id, product_id, batch_id, available_quantity, batch_reference
    FROM tbl_stock_summary 
    WHERE product_id = ?
");
$stockStmt->bind_param("i", $test_product_id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();

if ($stockResult->num_rows > 0) {
    echo "<p><strong>Stock Summary Entries Found:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Summary ID</th><th>Product ID</th><th>Batch ID</th><th>Available Qty</th><th>Batch Reference</th></tr>";
    
    $total_stock_summary = 0;
    while ($row = $stockResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['summary_id'] . "</td>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['available_quantity'] . "</td>";
        echo "<td>" . $row['batch_reference'] . "</td>";
        echo "</tr>";
        $total_stock_summary += $row['available_quantity'];
    }
    echo "</table>";
    echo "<p><strong>Total Available in Stock Summary:</strong> $total_stock_summary</p>";
} else {
    echo "<p style='color: red;'><strong>No stock summary entries found!</strong></p>";
}

// 3. Check batches
echo "<h3>3. Batch Information:</h3>";
$batchStmt = $conn->prepare("
    SELECT b.batch_id, b.batch_reference, b.entry_date, b.location_id, b.status
    FROM tbl_batch b
    JOIN tbl_stock_summary ss ON b.batch_id = ss.batch_id
    WHERE ss.product_id = ?
    ORDER BY b.entry_date ASC
");
$batchStmt->bind_param("i", $test_product_id);
$batchStmt->execute();
$batchResult = $batchStmt->get_result();

if ($batchResult->num_rows > 0) {
    echo "<p><strong>Batch Entries Found:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Batch ID</th><th>Batch Reference</th><th>Entry Date</th><th>Location ID</th><th>Status</th></tr>";
    
    while ($row = $batchResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['batch_reference'] . "</td>";
        echo "<td>" . $row['entry_date'] . "</td>";
        echo "<td>" . $row['location_id'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No batch entries found!</strong></p>";
}

// 4. Check the actual FIFO query
echo "<h3>4. FIFO Query Test:</h3>";
$fifoStmt = $conn->prepare("
    SELECT 
        ss.available_quantity,
        ss.batch_reference,
        b.entry_date,
        b.location_id,
        ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_rank
    FROM tbl_stock_summary ss
    JOIN tbl_batch b ON ss.batch_id = b.batch_id
    WHERE ss.product_id = ? 
    AND b.location_id = ?
    AND ss.available_quantity > 0
    ORDER BY b.entry_date ASC, ss.summary_id ASC
");

$fifoStmt->bind_param("ii", $test_product_id, $test_location_id);
$fifoStmt->execute();
$fifoResult = $fifoStmt->get_result();

if ($fifoResult->num_rows > 0) {
    echo "<p><strong>FIFO Query Results:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>FIFO Rank</th><th>Available Qty</th><th>Batch Reference</th><th>Entry Date</th><th>Location ID</th></tr>";
    
    $total_fifo_available = 0;
    while ($row = $fifoResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['fifo_rank'] . "</td>";
        echo "<td>" . $row['available_quantity'] . "</td>";
        echo "<td>" . $row['batch_reference'] . "</td>";
        echo "<td>" . $row['entry_date'] . "</td>";
        echo "<td>" . $row['location_id'] . "</td>";
        echo "</tr>";
        $total_fifo_available += $row['available_quantity'];
    }
    echo "</table>";
    echo "<p><strong>Total Available in FIFO Query:</strong> $total_fifo_available</p>";
} else {
    echo "<p style='color: red;'><strong>No FIFO results found!</strong></p>";
    echo "<p>This means either:</p>";
    echo "<ul>";
    echo "<li>No stock summary entries for this product</li>";
    echo "<li>No batches with location_id = $test_location_id</li>";
    echo "<li>All available_quantity values are 0</li>";
    echo "</ul>";
}

// 5. Check all locations
echo "<h3>5. Available Locations:</h3>";
$locationStmt = $conn->prepare("SELECT location_id, location_name FROM tbl_location WHERE status = 'active'");
$locationStmt->execute();
$locationResult = $locationStmt->get_result();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Location ID</th><th>Location Name</th></tr>";
while ($row = $locationResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['location_id'] . "</td>";
    echo "<td>" . $row['location_name'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>6. Recommendations:</h3>";
echo "<ul>";
echo "<li>Check if the product has stock summary entries</li>";
echo "<li>Verify that batches have the correct location_id</li>";
echo "<li>Ensure available_quantity > 0 in stock summary</li>";
echo "<li>Make sure the location_id matches the warehouse location</li>";
echo "</ul>";

$conn->close();
?> 
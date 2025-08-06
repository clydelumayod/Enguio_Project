<?php
// Test FIFO Transfer System
// This script tests the FIFO transfer functionality

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>FIFO Transfer System Test</h1>\n";

// Step 1: Check current stock summary
echo "<h2>Step 1: Current Stock Summary</h2>\n";
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        ss.batch_id,
        ss.batch_reference,
        ss.available_quantity,
        b.entry_date,
        ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order
    FROM tbl_product p
    INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
    INNER JOIN tbl_batch b ON ss.batch_id = b.batch_id
    WHERE p.status = 'active' AND ss.available_quantity > 0
    ORDER BY p.product_id, fifo_order
";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Product ID</th><th>Product Name</th><th>Batch ID</th><th>Batch Reference</th><th>Available Qty</th><th>Entry Date</th><th>FIFO Order</th></tr>\n";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['batch_reference'] . "</td>";
        echo "<td>" . $row['available_quantity'] . "</td>";
        echo "<td>" . $row['entry_date'] . "</td>";
        echo "<td>" . $row['fifo_order'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "No stock summary records found.\n";
}

// Step 2: Test FIFO Transfer API
echo "<h2>Step 2: Test FIFO Transfer API</h2>\n";

// Get a product with multiple batches for testing
$testProductSql = "
    SELECT 
        p.product_id,
        p.product_name,
        COUNT(ss.batch_id) as batch_count,
        SUM(ss.available_quantity) as total_available
    FROM tbl_product p
    INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
    WHERE p.status = 'active' AND ss.available_quantity > 0
    GROUP BY p.product_id
    HAVING batch_count > 1
    ORDER BY total_available DESC
    LIMIT 1
";

$testResult = $conn->query($testProductSql);
if ($testResult->num_rows > 0) {
    $testProduct = $testResult->fetch_assoc();
    $product_id = $testProduct['product_id'];
    $product_name = $testProduct['product_name'];
    
    echo "<p>Testing FIFO transfer for product: <strong>$product_name</strong> (ID: $product_id)</p>\n";
    
    // Get source and destination locations
    $locationSql = "SELECT location_id, location_name FROM tbl_location WHERE status = 'active' ORDER BY location_id LIMIT 2";
    $locationResult = $conn->query($locationSql);
    $locations = [];
    while ($row = $locationResult->fetch_assoc()) {
        $locations[] = $row;
    }
    
    if (count($locations) >= 2) {
        $source_location_id = $locations[0]['location_id'];
        $destination_location_id = $locations[1]['location_id'];
        
        echo "<p>Source: " . $locations[0]['location_name'] . " (ID: $source_location_id)</p>\n";
        echo "<p>Destination: " . $locations[1]['location_name'] . " (ID: $destination_location_id)</p>\n";
        
        // Prepare transfer data
        $transferData = [
            'action' => 'create_fifo_transfer',
            'source_location_id' => $source_location_id,
            'destination_location_id' => $destination_location_id,
            'employee_id' => 21, // Use existing employee
            'status' => 'approved',
            'products' => [
                [
                    'product_id' => $product_id,
                    'quantity' => 10 // Transfer 10 units
                ]
            ]
        ];
        
        echo "<p>Transferring 10 units using FIFO method...</p>\n";
        
        // Make API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost/Enguio_Project/Api/backend_mysqli.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transferData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>API Response (HTTP $httpCode):</p>\n";
        echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
        
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['success']) {
            echo "<p style='color: green;'><strong>✅ FIFO Transfer successful!</strong></p>\n";
            echo "<p>Transfer ID: " . $responseData['transfer_id'] . "</p>\n";
            echo "<p>Products transferred: " . $responseData['products_transferred'] . "</p>\n";
        } else {
            echo "<p style='color: red;'><strong>❌ FIFO Transfer failed!</strong></p>\n";
            if ($responseData) {
                echo "<p>Error: " . $responseData['message'] . "</p>\n";
            }
        }
    } else {
        echo "<p style='color: red;'>Need at least 2 active locations for testing.</p>\n";
    }
} else {
    echo "<p style='color: red;'>No products with multiple batches found for testing.</p>\n";
}

// Step 3: Show updated stock summary
echo "<h2>Step 3: Updated Stock Summary (After Transfer)</h2>\n";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Product ID</th><th>Product Name</th><th>Batch ID</th><th>Batch Reference</th><th>Available Qty</th><th>Entry Date</th><th>FIFO Order</th></tr>\n";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['batch_reference'] . "</td>";
        echo "<td>" . $row['available_quantity'] . "</td>";
        echo "<td>" . $row['entry_date'] . "</td>";
        echo "<td>" . $row['fifo_order'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "No stock summary records found.\n";
}

// Step 4: Show transfer logs
echo "<h2>Step 4: Recent Transfer Logs</h2>\n";
$logSql = "
    SELECT 
        tl.transfer_id,
        p.product_name,
        tl.from_location,
        tl.to_location,
        tl.quantity,
        tl.transfer_date,
        tl.created_at
    FROM tbl_transfer_log tl
    JOIN tbl_product p ON tl.product_id = p.product_id
    ORDER BY tl.created_at DESC
    LIMIT 10
";

$logResult = $conn->query($logSql);
if ($logResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Transfer ID</th><th>Product</th><th>From</th><th>To</th><th>Quantity</th><th>Transfer Date</th><th>Created At</th></tr>\n";
    while ($row = $logResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['transfer_id'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['from_location'] . "</td>";
        echo "<td>" . $row['to_location'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['transfer_date'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "No transfer logs found.\n";
}

$conn->close();
echo "<p><strong>Test completed!</strong></p>\n";
?> 
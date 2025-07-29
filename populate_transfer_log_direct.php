<?php
// Direct database population script for transfer log
// This script reads from tbl_transfer_header and tbl_transfer_dtl to populate tbl_transfer_log

echo "<h1>Populate Transfer Log - Direct Database Method</h1>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if tbl_transfer_log exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'tbl_transfer_log'");
    if ($checkTable->num_rows == 0) {
        echo "<p style='color: red;'>❌ tbl_transfer_log table does not exist!</p>";
        exit;
    }
    
    // Check current log count
    $currentCount = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $currentCountRow = $currentCount->fetch_assoc();
    echo "<p>📊 Current transfer log entries: " . $currentCountRow['count'] . "</p>";
    
    // Get transfer header data
    $headerQuery = "SELECT 
        th.transfer_header_id,
        th.date,
        th.source_location_id,
        th.destination_location_id,
        th.employee_id,
        th.status,
        th.delivery_date,
        sl.location_name as source_location_name,
        dl.location_name as destination_location_name
    FROM tbl_transfer_header th
    LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
    LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
    ORDER BY th.transfer_header_id";
    
    $headerResult = $conn->query($headerQuery);
    
    if (!$headerResult) {
        throw new Exception("Error querying transfer headers: " . $conn->error);
    }
    
    echo "<p>📋 Found " . $headerResult->num_rows . " transfer headers</p>";
    
    $insertedCount = 0;
    $skippedCount = 0;
    
    // Process each transfer header
    while ($header = $headerResult->fetch_assoc()) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
        echo "<h3>Processing Transfer ID: " . $header['transfer_header_id'] . "</h3>";
        echo "<p>Date: " . $header['date'] . "</p>";
        echo "<p>From: " . $header['source_location_name'] . " → To: " . $header['destination_location_name'] . "</p>";
        
        // Get transfer details for this header
        $detailQuery = "SELECT 
            td.transfer_dtl_id,
            td.product_id,
            td.quantity,
            p.product_name,
            p.barcode
        FROM tbl_transfer_dtl td
        LEFT JOIN tbl_product p ON td.product_id = p.product_id
        WHERE td.transfer_header_id = " . $header['transfer_header_id'];
        
        $detailResult = $conn->query($detailQuery);
        
        if (!$detailResult) {
            echo "<p style='color: red;'>❌ Error querying transfer details: " . $conn->error . "</p>";
            continue;
        }
        
        echo "<p>📦 Found " . $detailResult->num_rows . " products in this transfer</p>";
        
        // Process each product in the transfer
        while ($detail = $detailResult->fetch_assoc()) {
            // Check if this log entry already exists
            $checkExisting = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log 
                WHERE transfer_id = " . $header['transfer_header_id'] . " 
                AND product_id = " . $detail['product_id']);
            $existingRow = $checkExisting->fetch_assoc();
            
            if ($existingRow['count'] > 0) {
                echo "<p style='color: orange;'>⚠️ Log entry already exists for Product ID: " . $detail['product_id'] . " - Skipping</p>";
                $skippedCount++;
                continue;
            }
            
            // Insert log entry
            $insertQuery = "INSERT INTO tbl_transfer_log (
                transfer_id, 
                product_id, 
                product_name,
                from_location, 
                to_location, 
                quantity, 
                transfer_date, 
                created_at
            ) VALUES (
                " . $header['transfer_header_id'] . ",
                " . $detail['product_id'] . ",
                '" . $conn->real_escape_string($detail['product_name'] ?? 'Unknown Product') . "',
                '" . $conn->real_escape_string($header['source_location_name'] ?? 'Unknown') . "',
                '" . $conn->real_escape_string($header['destination_location_name'] ?? 'Unknown') . "',
                " . $detail['quantity'] . ",
                '" . $header['date'] . "',
                NOW()
            )";
            
            if ($conn->query($insertQuery)) {
                echo "<p style='color: green;'>✅ Inserted log for Product: " . ($detail['product_name'] ?? 'ID: ' . $detail['product_id']) . " (Qty: " . $detail['quantity'] . ")</p>";
                $insertedCount++;
            } else {
                echo "<p style='color: red;'>❌ Failed to insert log for Product ID: " . $detail['product_id'] . " - " . $conn->error . "</p>";
            }
        }
        
        echo "</div>";
    }
    
    // Final summary
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px;'>";
    echo "<h2>📊 Population Summary</h2>";
    echo "<p><strong>✅ Successfully inserted:</strong> " . $insertedCount . " log entries</p>";
    echo "<p><strong>⚠️ Skipped (already exists):</strong> " . $skippedCount . " entries</p>";
    
    // Check final count
    $finalCount = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $finalCountRow = $finalCount->fetch_assoc();
    echo "<p><strong>📈 Total transfer log entries now:</strong> " . $finalCountRow['count'] . "</p>";
    
    // Show sample of populated data
    echo "<h3>📋 Sample of Populated Data:</h3>";
    $sampleQuery = "SELECT * FROM tbl_transfer_log ORDER BY created_at DESC LIMIT 5";
    $sampleResult = $conn->query($sampleQuery);
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Transfer ID</th><th>Product</th><th>From</th><th>To</th><th>Qty</th><th>Transfer Date</th><th>Created At</th>";
        echo "</tr>";
        
        while ($sample = $sampleResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>TR-" . $sample['transfer_id'] . "</td>";
            echo "<td>" . $sample['product_name'] . "</td>";
            echo "<td>" . $sample['from_location'] . "</td>";
            echo "<td>" . $sample['to_location'] . "</td>";
            echo "<td>" . $sample['quantity'] . "</td>";
            echo "<td>" . $sample['transfer_date'] . "</td>";
            echo "<td>" . $sample['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 
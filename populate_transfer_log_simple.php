<?php
// Simple transfer log population script
echo "=== Transfer Log Population Script ===\n";

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
    echo "✅ Database connected successfully\n";
    
    // Check current log count
    $currentCount = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $currentCountRow = $currentCount->fetch_assoc();
    echo "📊 Current transfer log entries: " . $currentCountRow['count'] . "\n";
    
    // Get all transfer headers
    $headerQuery = "SELECT 
        th.transfer_header_id,
        th.date,
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
    
    echo "📋 Found " . $headerResult->num_rows . " transfer headers\n\n";
    
    $insertedCount = 0;
    $skippedCount = 0;
    
    // Process each transfer header
    while ($header = $headerResult->fetch_assoc()) {
        echo "Processing Transfer ID: " . $header['transfer_header_id'] . "\n";
        echo "  From: " . $header['source_location_name'] . " → To: " . $header['destination_location_name'] . "\n";
        
        // Get transfer details for this header
        $detailQuery = "SELECT 
            td.product_id,
            td.quantity,
            p.product_name
        FROM tbl_transfer_dtl td
        LEFT JOIN tbl_product p ON td.product_id = p.product_id
        WHERE td.transfer_header_id = " . $header['transfer_header_id'];
        
        $detailResult = $conn->query($detailQuery);
        
        if (!$detailResult) {
            echo "  ❌ Error querying transfer details: " . $conn->error . "\n";
            continue;
        }
        
        echo "  📦 Found " . $detailResult->num_rows . " products\n";
        
        // Process each product in the transfer
        while ($detail = $detailResult->fetch_assoc()) {
            // Check if this log entry already exists
            $checkExisting = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log 
                WHERE transfer_id = " . $header['transfer_header_id'] . " 
                AND product_id = " . $detail['product_id']);
            $existingRow = $checkExisting->fetch_assoc();
            
            if ($existingRow['count'] > 0) {
                echo "    ⚠️ Log entry already exists for Product ID: " . $detail['product_id'] . " - Skipping\n";
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
                echo "    ✅ Inserted log for Product: " . ($detail['product_name'] ?? 'ID: ' . $detail['product_id']) . " (Qty: " . $detail['quantity'] . ")\n";
                $insertedCount++;
            } else {
                echo "    ❌ Failed to insert log for Product ID: " . $detail['product_id'] . " - " . $conn->error . "\n";
            }
        }
        
        echo "\n";
    }
    
    // Final summary
    echo "=== Population Summary ===\n";
    echo "✅ Successfully inserted: " . $insertedCount . " log entries\n";
    echo "⚠️ Skipped (already exists): " . $skippedCount . " entries\n";
    
    // Check final count
    $finalCount = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $finalCountRow = $finalCount->fetch_assoc();
    echo "📈 Total transfer log entries now: " . $finalCountRow['count'] . "\n";
    
    // Show sample of populated data
    echo "\n=== Sample of Populated Data ===\n";
    $sampleQuery = "SELECT * FROM tbl_transfer_log ORDER BY created_at DESC LIMIT 3";
    $sampleResult = $conn->query($sampleQuery);
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        while ($sample = $sampleResult->fetch_assoc()) {
            echo "TR-" . $sample['transfer_id'] . " | " . 
                 $sample['product_name'] . " | " . 
                 $sample['from_location'] . " → " . $sample['to_location'] . " | " . 
                 $sample['quantity'] . " units | " . 
                 $sample['transfer_date'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 
<?php
// PDO transfer log population script
echo "=== Transfer Log Population Script (PDO) ===\n";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    // Try PDO with MySQL driver
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connected successfully using PDO\n";
    
    // Check current log count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $currentCountRow = $stmt->fetch(PDO::FETCH_ASSOC);
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
    
    $headerStmt = $conn->query($headerQuery);
    $headerCount = $headerStmt->rowCount();
    
    echo "📋 Found " . $headerCount . " transfer headers\n\n";
    
    $insertedCount = 0;
    $skippedCount = 0;
    
    // Process each transfer header
    while ($header = $headerStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Processing Transfer ID: " . $header['transfer_header_id'] . "\n";
        echo "  From: " . $header['source_location_name'] . " → To: " . $header['destination_location_name'] . "\n";
        
        // Get transfer details for this header
        $detailQuery = "SELECT 
            td.product_id,
            td.quantity,
            p.product_name
        FROM tbl_transfer_dtl td
        LEFT JOIN tbl_product p ON td.product_id = p.product_id
        WHERE td.transfer_header_id = :transfer_id";
        
        $detailStmt = $conn->prepare($detailQuery);
        $detailStmt->execute(['transfer_id' => $header['transfer_header_id']]);
        $detailCount = $detailStmt->rowCount();
        
        echo "  📦 Found " . $detailCount . " products\n";
        
        // Process each product in the transfer
        while ($detail = $detailStmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if this log entry already exists
            $checkQuery = "SELECT COUNT(*) as count FROM tbl_transfer_log 
                WHERE transfer_id = :transfer_id AND product_id = :product_id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([
                'transfer_id' => $header['transfer_header_id'],
                'product_id' => $detail['product_id']
            ]);
            $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
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
                :transfer_id,
                :product_id,
                :product_name,
                :from_location,
                :to_location,
                :quantity,
                :transfer_date,
                NOW()
            )";
            
            $insertStmt = $conn->prepare($insertQuery);
            $result = $insertStmt->execute([
                'transfer_id' => $header['transfer_header_id'],
                'product_id' => $detail['product_id'],
                'product_name' => $detail['product_name'] ?? 'Unknown Product',
                'from_location' => $header['source_location_name'] ?? 'Unknown',
                'to_location' => $header['destination_location_name'] ?? 'Unknown',
                'quantity' => $detail['quantity'],
                'transfer_date' => $header['date']
            ]);
            
            if ($result) {
                echo "    ✅ Inserted log for Product: " . ($detail['product_name'] ?? 'ID: ' . $detail['product_id']) . " (Qty: " . $detail['quantity'] . ")\n";
                $insertedCount++;
            } else {
                echo "    ❌ Failed to insert log for Product ID: " . $detail['product_id'] . "\n";
            }
        }
        
        echo "\n";
    }
    
    // Final summary
    echo "=== Population Summary ===\n";
    echo "✅ Successfully inserted: " . $insertedCount . " log entries\n";
    echo "⚠️ Skipped (already exists): " . $skippedCount . " entries\n";
    
    // Check final count
    $finalStmt = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_log");
    $finalCountRow = $finalStmt->fetch(PDO::FETCH_ASSOC);
    echo "📈 Total transfer log entries now: " . $finalCountRow['count'] . "\n";
    
    // Show sample of populated data
    echo "\n=== Sample of Populated Data ===\n";
    $sampleStmt = $conn->query("SELECT * FROM tbl_transfer_log ORDER BY created_at DESC LIMIT 3");
    
    while ($sample = $sampleStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "TR-" . $sample['transfer_id'] . " | " . 
             $sample['product_name'] . " | " . 
             $sample['from_location'] . " → " . $sample['to_location'] . " | " . 
             $sample['quantity'] . " units | " . 
             $sample['transfer_date'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ PDO Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn = null;
    }
}
?> 
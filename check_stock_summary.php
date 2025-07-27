<?php
/**
 * Check and Populate Stock Summary Table
 * This script ensures the tbl_stock_summary table has data for FIFO tracking
 */

require_once 'Api/conn.php';

echo "<h1>Stock Summary Table Check and Population</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>\n";

try {
    // Check current state of stock summary table
    echo "<div class='section'>\n";
    echo "<h2>1. Current Stock Summary Table Status</h2>\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_stock_summary");
    $stmt->execute();
    $summaryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>Current records in tbl_stock_summary:</strong> <span class='info'>$summaryCount</span></p>\n";
    
    if ($summaryCount > 0) {
        echo "<p class='success'>✓ Stock summary table has data</p>\n";
        
        // Show sample data
        $stmt = $conn->prepare("SELECT * FROM tbl_stock_summary LIMIT 5");
        $stmt->execute();
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Stock Summary Data:</h3>\n";
        echo "<table>\n";
        echo "<thead><tr>\n";
        if (!empty($sampleData)) {
            $headers = array_keys($sampleData[0]);
            foreach ($headers as $header) {
                echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
            }
        }
        echo "</tr></thead><tbody>\n";
        
        foreach ($sampleData as $row) {
            echo "<tr>\n";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'N/A') . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p class='error'>✗ Stock summary table is empty</p>\n";
    }
    echo "</div>\n";
    
    // Check products with batch information
    echo "<div class='section'>\n";
    echo "<h2>2. Products with Batch Information</h2>\n";
    
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.quantity,
            p.batch_id,
            b.batch_reference,
            b.entry_date,
            b.entry_time,
            p.unit_price,
            p.expiration
        FROM tbl_product p
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        WHERE p.status = 'active' AND p.batch_id IS NOT NULL
        ORDER BY p.product_id
    ");
    $stmt->execute();
    $productsWithBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Products with batch information:</strong> <span class='info'>" . count($productsWithBatches) . "</span></p>\n";
    
    if (!empty($productsWithBatches)) {
        echo "<h3>Sample Products with Batches:</h3>\n";
        echo "<table>\n";
        echo "<thead><tr>\n";
        $headers = ['product_id', 'product_name', 'quantity', 'batch_reference', 'entry_date', 'unit_price'];
        foreach ($headers as $header) {
            echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
        }
        echo "</tr></thead><tbody>\n";
        
        $displayCount = min(5, count($productsWithBatches));
        for ($i = 0; $i < $displayCount; $i++) {
            $row = $productsWithBatches[$i];
            echo "<tr>\n";
            foreach ($headers as $header) {
                $value = $row[$header] ?? 'N/A';
                echo "<td>" . htmlspecialchars($value) . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        if (count($productsWithBatches) > 5) {
            echo "<p class='info'>Showing first 5 of " . count($productsWithBatches) . " products</p>\n";
        }
    } else {
        echo "<p class='error'>✗ No products have batch information</p>\n";
    }
    echo "</div>\n";
    
    // Populate stock summary table if empty
    if ($summaryCount == 0 && !empty($productsWithBatches)) {
        echo "<div class='section'>\n";
        echo "<h2>3. Populating Stock Summary Table</h2>\n";
        
        $conn->beginTransaction();
        
        try {
            $insertCount = 0;
            
            foreach ($productsWithBatches as $product) {
                // Check if record already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_stock_summary WHERE product_id = ? AND batch_id = ?");
                $stmt->execute([$product['product_id'], $product['batch_id']]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                
                if (!$exists) {
                    // Insert into stock summary
                    $stmt = $conn->prepare("
                        INSERT INTO tbl_stock_summary (
                            product_id, batch_id, available_quantity, reserved_quantity, 
                            total_quantity, unit_cost, expiration_date, batch_reference
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $product['product_id'],
                        $product['batch_id'],
                        $product['quantity'], // available_quantity
                        0, // reserved_quantity
                        $product['quantity'], // total_quantity
                        $product['unit_price'], // unit_cost
                        $product['expiration'], // expiration_date
                        $product['batch_reference'] // batch_reference
                    ]);
                    
                    $insertCount++;
                }
            }
            
            $conn->commit();
            
            echo "<p class='success'>✓ Successfully populated stock summary table with $insertCount records</p>\n";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<p class='error'>✗ Error populating stock summary: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
        
        echo "</div>\n";
    }
    
    // Test FIFO view
    echo "<div class='section'>\n";
    echo "<h2>4. Testing FIFO View</h2>\n";
    
    if (!empty($productsWithBatches)) {
        $testProduct = $productsWithBatches[0];
        $productId = $testProduct['product_id'];
        
        $stmt = $conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.barcode,
                p.category,
                ss.batch_id,
                ss.batch_reference,
                ss.available_quantity,
                ss.unit_cost,
                ss.expiration_date,
                b.entry_date as batch_date,
                ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order
            FROM tbl_product p
            JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
            JOIN tbl_batch b ON ss.batch_id = b.batch_id
            WHERE p.product_id = ? AND p.status = 'active' AND ss.available_quantity > 0
            ORDER BY b.entry_date ASC, ss.summary_id ASC
        ");
        $stmt->execute([$productId]);
        $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>FIFO data for product ID $productId:</strong> <span class='info'>" . count($fifoData) . " batches</span></p>\n";
        
        if (!empty($fifoData)) {
            echo "<h3>FIFO Stock Details:</h3>\n";
            echo "<table>\n";
            echo "<thead><tr>\n";
            $headers = ['fifo_order', 'batch_reference', 'available_quantity', 'unit_cost', 'expiration_date', 'batch_date'];
            foreach ($headers as $header) {
                echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
            }
            echo "</tr></thead><tbody>\n";
            
            foreach ($fifoData as $batch) {
                echo "<tr>\n";
                foreach ($headers as $header) {
                    $value = $batch[$header] ?? 'N/A';
                    if ($header === 'batch_date' && $value !== 'N/A') {
                        $value = date('M d, Y', strtotime($value));
                    }
                    echo "<td>" . htmlspecialchars($value) . "</td>\n";
                }
                echo "</tr>\n";
            }
            echo "</tbody></table>\n";
            
            echo "<p class='success'>✓ FIFO view is working correctly!</p>\n";
        } else {
            echo "<p class='error'>✗ No FIFO data available</p>\n";
        }
    }
    echo "</div>\n";
    
    // Summary
    echo "<div class='section'>\n";
    echo "<h2>5. Summary</h2>\n";
    echo "<div class='info'>\n";
    echo "<p><strong>Stock Summary Records:</strong> $summaryCount</p>\n";
    echo "<p><strong>Products with Batches:</strong> " . count($productsWithBatches) . "</p>\n";
    
    if ($summaryCount > 0) {
        echo "<p class='success'>✓ Your FIFO modal should now display batch dates correctly!</p>\n";
        echo "<p><strong>Next Steps:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Refresh your Warehouse page</li>\n";
        echo "<li>Click on a product's FIFO button</li>\n";
        echo "<li>You should now see the 'Batch Date' column populated with actual dates</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p class='error'>✗ Stock summary table needs to be populated</p>\n";
    }
    echo "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'>\n";
    echo "<h2>Error</h2>\n";
    echo "<p class='error'>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?> 
<?php
/**
 * Fix FIFO Dates Display Issue
 * This script will check and populate the stock summary table
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h1>Fixing FIFO Dates Display</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>\n";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='section'>\n";
    echo "<p class='success'>✓ Database connected successfully!</p>\n";
    echo "</div>\n";
    
    // Step 1: Check current stock summary
    echo "<div class='section'>\n";
    echo "<h2>Step 1: Checking Stock Summary Table</h2>\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_stock_summary");
    $stmt->execute();
    $summaryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>Current records in tbl_stock_summary:</strong> <span class='info'>$summaryCount</span></p>\n";
    
    if ($summaryCount > 0) {
        echo "<p class='success'>✓ Stock summary table has data</p>\n";
    } else {
        echo "<p class='error'>✗ Stock summary table is empty - will populate it</p>\n";
    }
    echo "</div>\n";
    
    // Step 2: Check products with batch information
    echo "<div class='section'>\n";
    echo "<h2>Step 2: Checking Products with Batch Information</h2>\n";
    
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.quantity,
            p.batch_id,
            b.batch_reference,
            b.entry_date,
            p.unit_price
        FROM tbl_product p
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        WHERE p.status = 'active' AND p.batch_id IS NOT NULL
        ORDER BY p.product_id
        LIMIT 5
    ");
    $stmt->execute();
    $productsWithBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Products with batch information:</strong> <span class='info'>" . count($productsWithBatches) . " (showing first 5)</span></p>\n";
    
    if (!empty($productsWithBatches)) {
        echo "<table>\n";
        echo "<thead><tr>\n";
        echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Batch Reference</th><th>Entry Date</th><th>Unit Price</th>\n";
        echo "</tr></thead><tbody>\n";
        
        foreach ($productsWithBatches as $product) {
            echo "<tr>\n";
            echo "<td>" . $product['product_id'] . "</td>\n";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>\n";
            echo "<td>" . $product['quantity'] . "</td>\n";
            echo "<td>" . htmlspecialchars($product['batch_reference']) . "</td>\n";
            echo "<td>" . $product['entry_date'] . "</td>\n";
            echo "<td>₱" . number_format($product['unit_price'], 2) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p class='error'>✗ No products have batch information</p>\n";
    }
    echo "</div>\n";
    
    // Step 3: Populate stock summary if empty
    if ($summaryCount == 0 && !empty($productsWithBatches)) {
        echo "<div class='section'>\n";
        echo "<h2>Step 3: Populating Stock Summary Table</h2>\n";
        
        $conn->beginTransaction();
        
        try {
            // Get all products with batches
            $stmt = $conn->prepare("
                SELECT 
                    p.product_id,
                    p.batch_id,
                    p.quantity,
                    p.unit_price,
                    p.expiration,
                    b.batch_reference
                FROM tbl_product p
                JOIN tbl_batch b ON p.batch_id = b.batch_id
                WHERE p.status = 'active' AND p.batch_id IS NOT NULL AND p.quantity > 0
            ");
            $stmt->execute();
            $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $insertCount = 0;
            
            foreach ($allProducts as $product) {
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
    
    // Step 4: Test FIFO query
    echo "<div class='section'>\n";
    echo "<h2>Step 4: Testing FIFO Query</h2>\n";
    
    if (!empty($productsWithBatches)) {
        $testProduct = $productsWithBatches[0];
        $productId = $testProduct['product_id'];
        
        $stmt = $conn->prepare("
            SELECT 
                ss.summary_id,
                ss.batch_id,
                ss.batch_reference,
                ss.available_quantity,
                ss.unit_cost,
                ss.expiration_date,
                b.entry_date as batch_date,
                b.entry_time as batch_time,
                ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order,
                DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry
            FROM tbl_stock_summary ss
            JOIN tbl_batch b ON ss.batch_id = b.batch_id
            WHERE ss.product_id = ? AND ss.available_quantity > 0
            ORDER BY b.entry_date ASC, ss.summary_id ASC
        ");
        $stmt->execute([$productId]);
        $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>FIFO data for product ID $productId:</strong> <span class='info'>" . count($fifoData) . " batches</span></p>\n";
        
        if (!empty($fifoData)) {
            echo "<h3>FIFO Stock Details:</h3>\n";
            echo "<table>\n";
            echo "<thead><tr>\n";
            echo "<th>FIFO Order</th><th>Batch Reference</th><th>Available Qty</th><th>Unit Cost</th><th>Expiration Date</th><th>Days Until Expiry</th><th>Batch Date</th>\n";
            echo "</tr></thead><tbody>\n";
            
            foreach ($fifoData as $batch) {
                echo "<tr>\n";
                echo "<td>#" . $batch['fifo_order'] . "</td>\n";
                echo "<td>" . htmlspecialchars($batch['batch_reference']) . "</td>\n";
                echo "<td>" . $batch['available_quantity'] . "</td>\n";
                echo "<td>₱" . number_format($batch['unit_cost'], 2) . "</td>\n";
                echo "<td>" . ($batch['expiration_date'] ? date('M d, Y', strtotime($batch['expiration_date'])) : 'N/A') . "</td>\n";
                echo "<td>" . ($batch['days_until_expiry'] !== null ? $batch['days_until_expiry'] . ' days' : 'N/A') . "</td>\n";
                echo "<td>" . ($batch['batch_date'] ? date('M d, Y', strtotime($batch['batch_date'])) : 'N/A') . "</td>\n";
                echo "</tr>\n";
            }
            echo "</tbody></table>\n";
            
            echo "<p class='success'>✓ FIFO query is working correctly!</p>\n";
        } else {
            echo "<p class='error'>✗ No FIFO data available</p>\n";
        }
    }
    echo "</div>\n";
    
    // Step 5: Summary and next steps
    echo "<div class='section'>\n";
    echo "<h2>Step 5: Summary</h2>\n";
    echo "<div class='info'>\n";
    echo "<p><strong>Stock Summary Records:</strong> $summaryCount</p>\n";
    echo "<p><strong>Products with Batches:</strong> " . count($productsWithBatches) . "</p>\n";
    
    if ($summaryCount > 0 || !empty($productsWithBatches)) {
        echo "<p class='success'>✓ Your FIFO modal should now display batch dates correctly!</p>\n";
        echo "<p><strong>Next Steps:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Refresh your Warehouse page in the browser</li>\n";
        echo "<li>Click on any product's FIFO button</li>\n";
        echo "<li>You should now see the 'Batch Date' column populated with actual dates</li>\n";
        echo "</ol>\n";
        echo "<p><strong>If it still doesn't work:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Clear your browser cache (Ctrl+F5)</li>\n";
        echo "<li>Check the browser console for any JavaScript errors</li>\n";
        echo "<li>Make sure you're using the latest version of the Warehouse.js file</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p class='error'>✗ No data available for FIFO tracking</p>\n";
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
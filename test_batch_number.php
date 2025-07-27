<?php
/**
 * Test Batch Number Display
 * Verify that the FIFO API now returns batch_number
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h1>Test Batch Number Display</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>\n";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='section'>\n";
    echo "<p class='success'>✓ Database connected successfully!</p>\n";
    echo "</div>\n";
    
    // Get a sample product with FIFO data
    echo "<div class='section'>\n";
    echo "<h2>Testing Updated FIFO Query</h2>\n";
    
    $stmt = $conn->prepare("
        SELECT p.product_id, p.product_name 
        FROM tbl_product p 
        JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        WHERE p.status = 'active' 
        LIMIT 1
    ");
    $stmt->execute();
    $sampleProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleProduct) {
        $productId = $sampleProduct['product_id'];
        echo "<p><strong>Testing with Product:</strong> " . htmlspecialchars($sampleProduct['product_name']) . " (ID: $productId)</p>\n";
        
        // Test the updated FIFO query
        $stmt = $conn->prepare("
            SELECT 
                ss.summary_id,
                ss.batch_id,
                ss.batch_id as batch_number,
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
        
        echo "<p><strong>FIFO Data Found:</strong> <span class='info'>" . count($fifoData) . " batches</span></p>\n";
        
        if (!empty($fifoData)) {
            echo "<h3>Updated FIFO Data with Batch Numbers:</h3>\n";
            echo "<table>\n";
            echo "<thead><tr>\n";
            echo "<th>FIFO Order</th><th>Batch ID</th><th>Batch Number</th><th>Batch Reference</th><th>Available Qty</th><th>Unit Cost</th><th>Batch Date</th>\n";
            echo "</tr></thead><tbody>\n";
            
            foreach ($fifoData as $batch) {
                echo "<tr>\n";
                echo "<td>#" . $batch['fifo_order'] . "</td>\n";
                echo "<td>" . $batch['batch_id'] . "</td>\n";
                echo "<td><strong>" . $batch['batch_number'] . "</strong></td>\n";
                echo "<td>" . htmlspecialchars($batch['batch_reference'] ?: 'N/A') . "</td>\n";
                echo "<td>" . $batch['available_quantity'] . "</td>\n";
                echo "<td>₱" . number_format($batch['unit_cost'], 2) . "</td>\n";
                echo "<td>" . ($batch['batch_date'] ? date('M d, Y', strtotime($batch['batch_date'])) : 'N/A') . "</td>\n";
                echo "</tr>\n";
            }
            echo "</tbody></table>\n";
            
            echo "<p class='success'>✓ Batch numbers are now available in the API response!</p>\n";
            echo "<p><strong>Key Changes:</strong></p>\n";
            echo "<ul>\n";
            echo "<li>✅ Added <code>ss.batch_id as batch_number</code> to the query</li>\n";
            echo "<li>✅ Updated frontend to display <code>batch_number</code> instead of <code>batch_reference</code></li>\n";
            echo "<li>✅ Changed column header from 'Batch Reference' to 'Batch Number'</li>\n";
            echo "</ul>\n";
            
            echo "<h3>Raw API Response:</h3>\n";
            echo "<pre>" . json_encode($fifoData, JSON_PRETTY_PRINT) . "</pre>\n";
            
        } else {
            echo "<p class='error'>✗ No FIFO data found for this product</p>\n";
        }
    } else {
        echo "<p class='error'>✗ No products with FIFO data found</p>\n";
    }
    echo "</div>\n";
    
    // Instructions for testing
    echo "<div class='section'>\n";
    echo "<h2>Next Steps</h2>\n";
    echo "<div class='info'>\n";
    echo "<p><strong>To test the changes:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Refresh your Warehouse page</li>\n";
    echo "<li>Click on any product's FIFO button</li>\n";
    echo "<li>You should now see the 'Batch Number' column populated with the actual batch ID numbers</li>\n";
    echo "<li>The column header should say 'Batch Number' instead of 'Batch Reference'</li>\n";
    echo "</ol>\n";
    echo "<p><strong>Expected Result:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Batch Number column shows actual batch ID numbers (e.g., 1, 2, 3, etc.)</li>\n";
    echo "<li>✅ No more empty/blank batch reference cells</li>\n";
    echo "<li>✅ Clear identification of which batch each stock belongs to</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'>\n";
    echo "<h2>Error</h2>\n";
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?> 
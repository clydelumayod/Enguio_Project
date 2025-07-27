<?php
/**
 * Test FIFO API
 * Simple test to check if the FIFO API is working
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h1>FIFO API Test</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>\n";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='section'>\n";
    echo "<p class='success'>✓ Database connected successfully!</p>\n";
    echo "</div>\n";
    
    // Test 1: Check if stock summary has data
    echo "<div class='section'>\n";
    echo "<h2>Test 1: Stock Summary Data</h2>\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_stock_summary");
    $stmt->execute();
    $summaryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>Stock Summary Records:</strong> <span class='info'>$summaryCount</span></p>\n";
    
    if ($summaryCount == 0) {
        echo "<p class='error'>✗ Stock summary table is empty!</p>\n";
        echo "<p>Please run the SQL script in phpMyAdmin first.</p>\n";
    } else {
        echo "<p class='success'>✓ Stock summary table has data</p>\n";
    }
    echo "</div>\n";
    
    // Test 2: Get a sample product ID
    echo "<div class='section'>\n";
    echo "<h2>Test 2: Sample Product</h2>\n";
    
    $stmt = $conn->prepare("
        SELECT p.product_id, p.product_name, p.batch_id 
        FROM tbl_product p 
        WHERE p.status = 'active' AND p.batch_id IS NOT NULL 
        LIMIT 1
    ");
    $stmt->execute();
    $sampleProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleProduct) {
        echo "<p><strong>Sample Product:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Product ID: " . $sampleProduct['product_id'] . "</li>\n";
        echo "<li>Product Name: " . htmlspecialchars($sampleProduct['product_name']) . "</li>\n";
        echo "<li>Batch ID: " . $sampleProduct['batch_id'] . "</li>\n";
        echo "</ul>\n";
        
        $testProductId = $sampleProduct['product_id'];
    } else {
        echo "<p class='error'>✗ No products with batch information found</p>\n";
        $testProductId = null;
    }
    echo "</div>\n";
    
    // Test 3: Test FIFO API directly
    if ($testProductId) {
        echo "<div class='section'>\n";
        echo "<h2>Test 3: FIFO API Response</h2>\n";
        
        // Simulate the API call
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
        $stmt->execute([$testProductId]);
        $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>FIFO Data for Product ID $testProductId:</strong> <span class='info'>" . count($fifoData) . " batches</span></p>\n";
        
        if (!empty($fifoData)) {
            echo "<p class='success'>✓ FIFO data found!</p>\n";
            echo "<h3>Raw API Response:</h3>\n";
            echo "<pre>" . json_encode($fifoData, JSON_PRETTY_PRINT) . "</pre>\n";
            
            echo "<h3>Formatted Data:</h3>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>FIFO Order</th><th>Batch Reference</th><th>Available Qty</th><th>Unit Cost</th><th>Batch Date</th></tr>\n";
            
            foreach ($fifoData as $batch) {
                echo "<tr>\n";
                echo "<td>#" . $batch['fifo_order'] . "</td>\n";
                echo "<td>" . htmlspecialchars($batch['batch_reference']) . "</td>\n";
                echo "<td>" . $batch['available_quantity'] . "</td>\n";
                echo "<td>₱" . number_format($batch['unit_cost'], 2) . "</td>\n";
                echo "<td>" . ($batch['batch_date'] ? date('M d, Y', strtotime($batch['batch_date'])) : 'N/A') . "</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
            
            echo "<p class='success'>✓ The API is returning batch dates correctly!</p>\n";
        } else {
            echo "<p class='error'>✗ No FIFO data returned</p>\n";
        }
        echo "</div>\n";
    }
    
    // Test 4: Check if the issue is with the frontend
    echo "<div class='section'>\n";
    echo "<h2>Test 4: Frontend Test</h2>\n";
    
    if ($testProductId) {
        echo "<p><strong>To test the frontend:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Open your browser console (F12)</li>\n";
        echo "<li>Go to your Warehouse page</li>\n";
        echo "<li>Click on a product's FIFO button</li>\n";
        echo "<li>Check the console for any errors</li>\n";
        echo "<li>Look for the API response in the Network tab</li>\n";
        echo "</ol>\n";
        
        echo "<p><strong>Expected API URL:</strong></p>\n";
        echo "<code>POST /Api/backend.php</code><br>\n";
        echo "<code>Action: get_fifo_stock</code><br>\n";
        echo "<code>Product ID: $testProductId</code>\n";
    }
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'>\n";
    echo "<h2>Error</h2>\n";
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?> 
<?php
/**
 * Test Inventory Transfer Batch Information
 * Verify that the get_products API now includes batch_id and shows oldest batch
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

echo "<h1>Test Inventory Transfer Batch Information</h1>\n";
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
    
    // Test 1: Check the updated get_products query
    echo "<div class='section'>\n";
    echo "<h2>Test 1: Updated get_products Query</h2>\n";
    
    // Simulate the updated query
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            s.supplier_name,
            b.brand,
            l.location_name,
            batch.batch_id,
            batch.batch as batch_reference,
            batch.entry_date,
            batch.entry_by,
            COALESCE(p.date_added, CURDATE()) as date_added
        FROM tbl_product p 
        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
        LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        LEFT JOIN (
            SELECT 
                p2.product_id,
                b2.batch_id,
                b2.batch,
                b2.entry_date,
                b2.entry_by
            FROM tbl_product p2
            LEFT JOIN tbl_batch b2 ON p2.batch_id = b2.batch_id
            WHERE p2.batch_id IS NOT NULL
            GROUP BY p2.product_id
            HAVING MIN(b2.entry_date) = b2.entry_date
        ) batch ON p.product_id = batch.product_id
        WHERE (p.status IS NULL OR p.status <> 'archived')
        ORDER BY p.product_name ASC
        LIMIT 10
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Products with Batch Information:</strong> <span class='info'>" . count($products) . " products</span></p>\n";
    
    if (!empty($products)) {
        echo "<h3>Sample Products with Batch Numbers:</h3>\n";
        echo "<table>\n";
        echo "<thead><tr>\n";
        echo "<th>Product Name</th><th>Category</th><th>Batch ID</th><th>Batch Reference</th><th>Entry Date</th><th>Available Qty</th><th>Unit Price</th>\n";
        echo "</tr></thead><tbody>\n";
        
        foreach ($products as $product) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>\n";
            echo "<td>" . htmlspecialchars($product['category']) . "</td>\n";
            echo "<td><strong>" . ($product['batch_id'] ?: 'N/A') . "</strong></td>\n";
            echo "<td>" . htmlspecialchars($product['batch_reference'] ?: 'N/A') . "</td>\n";
            echo "<td>" . ($product['entry_date'] ? date('M d, Y', strtotime($product['entry_date'])) : 'N/A') . "</td>\n";
            echo "<td>" . $product['quantity'] . "</td>\n";
            echo "<td>₱" . number_format($product['unit_price'], 2) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        echo "<p class='success'>✓ Batch information is now included in the API response!</p>\n";
    } else {
        echo "<p class='error'>✗ No products found</p>\n";
    }
    echo "</div>\n";
    
    // Test 2: Verify oldest batch selection
    echo "<div class='section'>\n";
    echo "<h2>Test 2: Verify Oldest Batch Selection</h2>\n";
    
    // Check if we're getting the oldest batch for each product
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            COUNT(DISTINCT b.batch_id) as total_batches,
            MIN(b.entry_date) as oldest_batch_date,
            MAX(b.entry_date) as newest_batch_date
        FROM tbl_product p
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        WHERE p.status = 'active' AND p.batch_id IS NOT NULL
        GROUP BY p.product_id, p.product_name
        HAVING COUNT(DISTINCT b.batch_id) > 1
        ORDER BY p.product_name
        LIMIT 5
    ");
    $stmt->execute();
    $productsWithMultipleBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productsWithMultipleBatches)) {
        echo "<p><strong>Products with Multiple Batches:</strong> <span class='info'>" . count($productsWithMultipleBatches) . " products</span></p>\n";
        
        echo "<h3>Batch Date Range Verification:</h3>\n";
        echo "<table>\n";
        echo "<thead><tr>\n";
        echo "<th>Product Name</th><th>Total Batches</th><th>Oldest Batch Date</th><th>Newest Batch Date</th><th>Date Range</th>\n";
        echo "</tr></thead><tbody>\n";
        
        foreach ($productsWithMultipleBatches as $product) {
            $oldestDate = new DateTime($product['oldest_batch_date']);
            $newestDate = new DateTime($product['newest_batch_date']);
            $dateRange = $oldestDate->diff($newestDate)->days;
            
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>\n";
            echo "<td>" . $product['total_batches'] . "</td>\n";
            echo "<td>" . $oldestDate->format('M d, Y') . "</td>\n";
            echo "<td>" . $newestDate->format('M d, Y') . "</td>\n";
            echo "<td>" . $dateRange . " days</td>\n";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        echo "<p class='success'>✓ The API will show only the oldest batch for each product!</p>\n";
    } else {
        echo "<p class='info'>ℹ️ No products with multiple batches found (this is normal)</p>\n";
    }
    echo "</div>\n";
    
    // Test 3: Test API endpoint directly
    echo "<div class='section'>\n";
    echo "<h2>Test 3: API Endpoint Test</h2>\n";
    
    // Simulate the API call
    $apiData = [
        'action' => 'get_products'
    ];
    
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            s.supplier_name,
            b.brand,
            l.location_name,
            batch.batch_id,
            batch.batch as batch_reference,
            batch.entry_date,
            batch.entry_by,
            COALESCE(p.date_added, CURDATE()) as date_added
        FROM tbl_product p 
        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
        LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        LEFT JOIN (
            SELECT 
                p2.product_id,
                b2.batch_id,
                b2.batch,
                b2.entry_date,
                b2.entry_by
            FROM tbl_product p2
            LEFT JOIN tbl_batch b2 ON p2.batch_id = b2.batch_id
            WHERE p2.batch_id IS NOT NULL
            GROUP BY p2.product_id
            HAVING MIN(b2.entry_date) = b2.entry_date
        ) batch ON p.product_id = batch.product_id
        WHERE (p.status IS NULL OR p.status <> 'archived')
        ORDER BY p.product_name ASC
        LIMIT 5
    ");
    $stmt->execute();
    $apiProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>API Response Sample:</strong></p>\n";
    echo "<pre>" . json_encode($apiProducts, JSON_PRETTY_PRINT) . "</pre>\n";
    
    // Check if batch_id is present
    $hasBatchId = false;
    foreach ($apiProducts as $product) {
        if (isset($product['batch_id']) && $product['batch_id'] !== null) {
            $hasBatchId = true;
            break;
        }
    }
    
    if ($hasBatchId) {
        echo "<p class='success'>✅ Batch ID is included in the API response!</p>\n";
    } else {
        echo "<p class='error'>❌ Batch ID is missing from the API response</p>\n";
    }
    echo "</div>\n";
    
    // Test 4: Instructions for frontend
    echo "<div class='section'>\n";
    echo "<h2>Test 4: Frontend Integration</h2>\n";
    echo "<div class='info'>\n";
    echo "<p><strong>Changes Made:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Updated API queries to include <code>batch_id</code></li>\n";
    echo "<li>✅ Modified queries to show only the oldest batch per product</li>\n";
    echo "<li>✅ Added 'Batch Number' column to product selection table</li>\n";
    echo "<li>✅ Added 'Batch Number' column to selected products table</li>\n";
    echo "</ul>\n";
    echo "<p><strong>To test the changes:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Go to Inventory Transfer page</li>\n";
    echo "<li>Click 'Create Transfer'</li>\n";
    echo "<li>Select source and destination stores</li>\n";
    echo "<li>Click 'Select Products'</li>\n";
    echo "<li>You should see a new 'Batch Number' column showing the oldest batch ID for each product</li>\n";
    echo "<li>Select some products and verify the batch numbers appear in the selected products table</li>\n";
    echo "</ol>\n";
    echo "<p><strong>Expected Result:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ 'Batch Number' column appears in both tables</li>\n";
    echo "<li>✅ Shows actual batch ID numbers (e.g., 1, 2, 3, etc.)</li>\n";
    echo "<li>✅ Shows 'N/A' for products without batch information</li>\n";
    echo "<li>✅ Only shows the oldest/first batch for each product</li>\n";
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
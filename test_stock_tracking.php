<?php
/**
 * Test Script for Product Stock Tracking System
 * Demonstrates how to use the tracking functions
 */

require_once 'product_stock_tracking.php';

echo "<h1>Product Stock Tracking System - Test Results</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .summary { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
</style>\n";

try {
    // Test 1: Basic Stock History
    echo "<div class='section'>\n";
    echo "<h2>1. Basic Stock History</h2>\n";
    $history = getProductStockHistory();
    echo "<div class='summary'>\n";
    echo "<strong>Total Records:</strong> " . count($history) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    if (count($history) > 0) {
        echo "<table>\n";
        echo "<thead><tr>\n";
        $headers = array_keys($history[0]);
        foreach ($headers as $header) {
            echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
        }
        echo "</tr></thead><tbody>\n";
        
        // Show first 5 records
        $displayCount = min(5, count($history));
        for ($i = 0; $i < $displayCount; $i++) {
            echo "<tr>\n";
            foreach ($headers as $header) {
                $value = $history[$i][$header] ?? 'N/A';
                echo "<td>" . htmlspecialchars($value) . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        if (count($history) > 5) {
            echo "<p class='info'>Showing first 5 of " . count($history) . " records</p>\n";
        }
    }
    echo "</div>\n";
    
    // Test 2: Stock Movement History
    echo "<div class='section'>\n";
    echo "<h2>2. Stock Movement History</h2>\n";
    $movements = getStockMovementHistory();
    echo "<div class='summary'>\n";
    echo "<strong>Total Movement Records:</strong> " . count($movements) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    if (count($movements) > 0) {
        echo "<table>\n";
        echo "<thead><tr>\n";
        $headers = array_keys($movements[0]);
        foreach ($headers as $header) {
            echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
        }
        echo "</tr></thead><tbody>\n";
        
        // Show first 5 records
        $displayCount = min(5, count($movements));
        for ($i = 0; $i < $displayCount; $i++) {
            echo "<tr>\n";
            foreach ($headers as $header) {
                $value = $movements[$i][$header] ?? 'N/A';
                echo "<td>" . htmlspecialchars($value) . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        if (count($movements) > 5) {
            echo "<p class='info'>Showing first 5 of " . count($movements) . " records</p>\n";
        }
    }
    echo "</div>\n";
    
    // Test 3: Stock Summary by Location
    echo "<div class='section'>\n";
    echo "<h2>3. Stock Summary by Location</h2>\n";
    $summary = getProductStockSummary();
    echo "<div class='summary'>\n";
    echo "<strong>Total Products:</strong> " . count($summary) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    if (count($summary) > 0) {
        // Group by location
        $locations = [];
        foreach ($summary as $record) {
            $location = $record['location_name'] ?? 'Unknown';
            if (!isset($locations[$location])) {
                $locations[$location] = [];
            }
            $locations[$location][] = $record;
        }
        
        foreach ($locations as $location => $products) {
            echo "<h3>Location: " . htmlspecialchars($location) . " (" . count($products) . " products)</h3>\n";
            echo "<table>\n";
            echo "<thead><tr>\n";
            $headers = ['product_name', 'current_stock', 'unit_price', 'batch_reference', 'last_batch_date'];
            foreach ($headers as $header) {
                echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
            }
            echo "</tr></thead><tbody>\n";
            
            // Show first 3 products per location
            $displayCount = min(3, count($products));
            for ($i = 0; $i < $displayCount; $i++) {
                echo "<tr>\n";
                foreach ($headers as $header) {
                    $value = $products[$i][$header] ?? 'N/A';
                    echo "<td>" . htmlspecialchars($value) . "</td>\n";
                }
                echo "</tr>\n";
            }
            echo "</tbody></table>\n";
            
            if (count($products) > 3) {
                echo "<p class='info'>Showing first 3 of " . count($products) . " products in this location</p>\n";
            }
        }
    }
    echo "</div>\n";
    
    // Test 4: Audit Report
    echo "<div class='section'>\n";
    echo "<h2>4. Stock Audit Report</h2>\n";
    $audit = getStockAuditReport();
    echo "<div class='summary'>\n";
    echo "<strong>Total Audit Records:</strong> " . count($audit) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    if (count($audit) > 0) {
        echo "<table>\n";
        echo "<thead><tr>\n";
        $headers = ['product_name', 'current_stock', 'batch_reference', 'batch_received_date', 'supplier_name', 'location_name', 'entry_type'];
        foreach ($headers as $header) {
            echo "<th>" . str_replace('_', ' ', ucfirst($header)) . "</th>\n";
        }
        echo "</tr></thead><tbody>\n";
        
        // Show first 5 records
        $displayCount = min(5, count($audit));
        for ($i = 0; $i < $displayCount; $i++) {
            echo "<tr>\n";
            foreach ($headers as $header) {
                $value = $audit[$i][$header] ?? 'N/A';
                echo "<td>" . htmlspecialchars($value) . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        if (count($audit) > 5) {
            echo "<p class='info'>Showing first 5 of " . count($audit) . " records</p>\n";
        }
    }
    echo "</div>\n";
    
    // Test 5: Filtered Queries
    echo "<div class='section'>\n";
    echo "<h2>5. Filtered Queries Examples</h2>\n";
    
    // Test with specific location
    echo "<h3>Products in Location ID 2 (Warehouse)</h3>\n";
    $warehouseProducts = getProductStockSummary(2);
    echo "<div class='summary'>\n";
    echo "<strong>Warehouse Products:</strong> " . count($warehouseProducts) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    // Test with date range
    echo "<h3>Recent Stock Additions (Last 30 days)</h3>\n";
    $recentHistory = getProductStockHistory(null, null, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
    echo "<div class='summary'>\n";
    echo "<strong>Recent Additions:</strong> " . count($recentHistory) . "<br>\n";
    echo "<strong>Status:</strong> <span class='success'>✓ Success</span>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
    // Test 6: API Endpoints
    echo "<div class='section'>\n";
    echo "<h2>6. API Endpoints Available</h2>\n";
    echo "<div class='summary'>\n";
    echo "<strong>Available Actions:</strong><br>\n";
    echo "• <code>?action=stock_history</code> - Get stock history<br>\n";
    echo "• <code>?action=movement_history</code> - Get movement history<br>\n";
    echo "• <code>?action=stock_summary</code> - Get stock summary<br>\n";
    echo "• <code>?action=products_by_batch</code> - Get products by batch reference<br>\n";
    echo "• <code>?action=audit_report</code> - Generate audit report<br>\n";
    echo "<br><strong>Example:</strong> <code>product_stock_tracking.php?action=stock_history&location_id=2</code>\n";
    echo "</div>\n";
    echo "</div>\n";
    
    echo "<div class='section'>\n";
    echo "<h2>7. Summary</h2>\n";
    echo "<div class='summary'>\n";
    echo "<strong>✓ All tests completed successfully!</strong><br>\n";
    echo "• Stock History: " . count($history) . " records<br>\n";
    echo "• Movement History: " . count($movements) . " records<br>\n";
    echo "• Stock Summary: " . count($summary) . " products<br>\n";
    echo "• Audit Report: " . count($audit) . " records<br>\n";
    echo "• Warehouse Products: " . count($warehouseProducts) . " products<br>\n";
    echo "• Recent Additions: " . count($recentHistory) . " records<br>\n";
    echo "<br><strong>Next Steps:</strong><br>\n";
    echo "1. Use the PHP functions in your application<br>\n";
    echo "2. Run the SQL queries directly in your database<br>\n";
    echo "3. Access the web interface at <code>product_stock_tracking.php</code><br>\n";
    echo "4. Customize the queries for your specific needs\n";
    echo "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'>\n";
    echo "<h2>Error</h2>\n";
    echo "<div class='error'>\n";
    echo "An error occurred: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
    echo "</div>\n";
}
?> 
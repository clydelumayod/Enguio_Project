<?php
/**
 * FIFO Test Script
 * 
 * This script tests the FIFO stock operations functionality
 * Run this to verify everything is working correctly
 */

require_once 'fifo_stock_operations.php';

// Database connection
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $fifo = new FIFOStockOperations($pdo);
    $fifo->setDebug(true);
    
    echo "<h1>FIFO Stock Operations Test</h1>\n";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>\n";
    
    // Test 1: Check if required tables exist
    echo "<div class='test'>\n";
    echo "<h2>Test 1: Database Tables Check</h2>\n";
    
    $required_tables = ['tbl_stock_movements', 'tbl_stock_summary', 'tbl_batch', 'tbl_product'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        echo "<p class='success'>✓ All required tables exist</p>\n";
    } else {
        echo "<p class='error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</p>\n";
        echo "<p>Please run the FIFO migration script first.</p>\n";
    }
    echo "</div>\n";
    
    // Test 2: Check if products exist
    echo "<div class='test'>\n";
    echo "<h2>Test 2: Product Availability Check</h2>\n";
    
    $stmt = $pdo->prepare("SELECT product_id, product_name, quantity FROM tbl_product WHERE status = 'active' AND quantity > 0 LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p class='error'>✗ No active products with stock found</p>\n";
        echo "<p>Please add some products with stock before testing FIFO operations.</p>\n";
    } else {
        echo "<p class='success'>✓ Found " . count($products) . " products with stock</p>\n";
        echo "<ul>\n";
        foreach ($products as $product) {
            echo "<li>ID: {$product['product_id']}, Name: {$product['product_name']}, Stock: {$product['quantity']}</li>\n";
        }
        echo "</ul>\n";
    }
    echo "</div>\n";
    
    // Test 3: Test FIFO stock report
    if (!empty($products)) {
        echo "<div class='test'>\n";
        echo "<h2>Test 3: FIFO Stock Report</h2>\n";
        
        $test_product_id = $products[0]['product_id'];
        $report_result = $fifo->getFIFOStockReport($test_product_id);
        
        if ($report_result['success']) {
            echo "<p class='success'>✓ FIFO report generated successfully</p>\n";
            echo "<p>Product ID: $test_product_id</p>\n";
            echo "<p>Available batches: " . count($report_result['data']) . "</p>\n";
            
            if (!empty($report_result['data'])) {
                echo "<h4>FIFO Stock Details:</h4>\n";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
                echo "<tr><th>FIFO Order</th><th>Batch Reference</th><th>Available Qty</th><th>Unit Cost</th><th>Expiration</th></tr>\n";
                
                foreach ($report_result['data'] as $batch) {
                    echo "<tr>\n";
                    echo "<td>#" . $batch['fifo_order'] . "</td>\n";
                    echo "<td>" . $batch['batch_reference'] . "</td>\n";
                    echo "<td>" . $batch['available_quantity'] . "</td>\n";
                    echo "<td>₱" . number_format($batch['unit_cost'], 2) . "</td>\n";
                    echo "<td>" . ($batch['expiration_date'] ?? 'N/A') . "</td>\n";
                    echo "</tr>\n";
                }
                echo "</table>\n";
            } else {
                echo "<p class='info'>No FIFO stock data available for this product</p>\n";
            }
        } else {
            echo "<p class='error'>✗ FIFO report failed: " . $report_result['message'] . "</p>\n";
        }
        echo "</div>\n";
    }
    
    // Test 4: Test stock consumption (if stock available)
    if (!empty($products)) {
        echo "<div class='test'>\n";
        echo "<h2>Test 4: Stock Consumption Test</h2>\n";
        
        $test_product_id = $products[0]['product_id'];
        $test_quantity = 1; // Test with 1 unit
        
        // Check stock availability first
        $availability_result = $fifo->getTotalAvailableStock($test_product_id);
        
        if ($availability_result >= $test_quantity) {
            echo "<p class='info'>Testing consumption of $test_quantity unit(s) from product ID: $test_product_id</p>\n";
            
            $consume_result = $fifo->consumeStockFIFO(
                $test_product_id,
                $test_quantity,
                'TEST-' . date('YmdHis'),
                'TEST',
                'Test consumption',
                'test_user'
            );
            
            if ($consume_result['success']) {
                echo "<p class='success'>✓ Stock consumption test successful</p>\n";
                echo "<p>Quantity consumed: " . $consume_result['quantity_consumed'] . "</p>\n";
                echo "<p>Total cost: ₱" . number_format($consume_result['total_cost'], 2) . "</p>\n";
                echo "<p>Batches used: " . count($consume_result['consumed_batches']) . "</p>\n";
                
                foreach ($consume_result['consumed_batches'] as $batch) {
                    echo "<p>  - Batch: " . $batch['batch_reference'] . 
                         ", Qty: " . $batch['quantity_consumed'] . 
                         ", Cost: ₱" . number_format($batch['total_cost'], 2) . "</p>\n";
                }
            } else {
                echo "<p class='error'>✗ Stock consumption test failed: " . $consume_result['message'] . "</p>\n";
            }
        } else {
            echo "<p class='error'>✗ Insufficient stock for test (Available: $availability_result, Needed: $test_quantity)</p>\n";
        }
        echo "</div>\n";
    }
    
    // Test 5: Test expiring products report
    echo "<div class='test'>\n";
    echo "<h2>Test 5: Expiring Products Report</h2>\n";
    
    $expiring_result = $fifo->getExpiringProductsReport(90); // 90 days threshold
    
    if ($expiring_result['success']) {
        echo "<p class='success'>✓ Expiring products report generated</p>\n";
        echo "<p>Products expiring within 90 days: " . count($expiring_result['data']) . "</p>\n";
        
        if (!empty($expiring_result['data'])) {
            echo "<h4>Expiring Products:</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Product Name</th><th>Barcode</th><th>Available Qty</th><th>Expiration Date</th><th>Days Until Expiry</th></tr>\n";
            
            foreach ($expiring_result['data'] as $product) {
                $days_color = $product['days_until_expiry'] <= 7 ? 'red' : 
                             ($product['days_until_expiry'] <= 30 ? 'orange' : 'green');
                
                echo "<tr>\n";
                echo "<td>" . $product['product_name'] . "</td>\n";
                echo "<td>" . $product['barcode'] . "</td>\n";
                echo "<td>" . $product['available_quantity'] . "</td>\n";
                echo "<td>" . $product['expiration_date'] . "</td>\n";
                echo "<td style='color: $days_color;'>" . $product['days_until_expiry'] . " days</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p class='info'>No products expiring within 90 days</p>\n";
        }
    } else {
        echo "<p class='error'>✗ Expiring products report failed: " . $expiring_result['message'] . "</p>\n";
    }
    echo "</div>\n";
    
    // Test 6: API endpoint test
    echo "<div class='test'>\n";
    echo "<h2>Test 6: API Endpoint Test</h2>\n";
    
    // Simulate API call
    $api_data = [
        'action' => 'get_fifo_report',
        'product_id' => !empty($products) ? $products[0]['product_id'] : 1
    ];
    
    // This would normally be a POST request to fifo_api.php
    echo "<p class='info'>API endpoint test would be performed via POST request to fifo_api.php</p>\n";
    echo "<p>Test data: " . json_encode($api_data, JSON_PRETTY_PRINT) . "</p>\n";
    echo "</div>\n";
    
    echo "<div class='test'>\n";
    echo "<h2>Test Summary</h2>\n";
    echo "<p>All tests completed. Check the results above for any issues.</p>\n";
    echo "<p>If all tests pass, your FIFO system is ready for production use.</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='test'>\n";
    echo "<h2>Test Error</h2>\n";
    echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection settings.</p>\n";
    echo "</div>\n";
}
?>

<script>
// JavaScript test for API endpoint
async function testAPI() {
    try {
        const response = await fetch('fifo_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_fifo_report',
                product_id: 82 // Replace with actual product ID
            })
        });
        
        const result = await response.json();
        console.log('API Test Result:', result);
        
        if (result.success) {
            console.log('✓ API endpoint working correctly');
        } else {
            console.error('✗ API endpoint error:', result.message);
        }
    } catch (error) {
        console.error('✗ API test failed:', error);
    }
}

// Uncomment to test API endpoint
// testAPI();
</script> 
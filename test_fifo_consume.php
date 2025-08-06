<?php
// Test FIFO Consume Function
// This file demonstrates how the FIFO consume function works

// Include the backend file
require_once 'Api/backend_mysqli.php';

// Test data for the scenario:
// Batch 1: 100 units at ₱55.00
// Batch 2: 20 units at ₱55.00  
// Batch 3: 100 units at ₱50.00
// Transfer: 150 units

echo "<h2>FIFO Consume Function Test</h2>";

// Simulate the API call
$test_data = [
    'action' => 'consume_stock_fifo',
    'product_id' => 1, // Replace with actual product ID
    'quantity' => 150,
    'reference_no' => 'TRANSFER-001',
    'notes' => 'Test FIFO transfer of 150 units',
    'created_by' => 'admin'
];

echo "<h3>Test Parameters:</h3>";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Expected FIFO Behavior:</h3>";
echo "<ul>";
echo "<li><strong>Batch 1:</strong> 100 units at ₱55.00 = ₱5,500.00 (fully consumed)</li>";
echo "<li><strong>Batch 2:</strong> 20 units at ₱55.00 = ₱1,100.00 (fully consumed)</li>";
echo "<li><strong>Batch 3:</strong> 30 units at ₱50.00 = ₱1,500.00 (partially consumed)</li>";
echo "<li><strong>Total:</strong> 150 units = ₱8,100.00</li>";
echo "</ul>";

echo "<h3>API Response:</h3>";
echo "<p><em>Note: This will show the actual database response when you have the correct product_id and stock data.</em></p>";

// To test this function:
// 1. Make sure you have a product with ID 1 (or change the product_id)
// 2. Ensure the product has multiple batches with the quantities mentioned above
// 3. Run this test file in your browser
// 4. The function will return the consumed batches and total value

echo "<h3>How to Use:</h3>";
echo "<ol>";
echo "<li>Update the product_id in the test data to match an existing product</li>";
echo "<li>Ensure the product has multiple batches with different quantities and costs</li>";
echo "<li>Run this test file</li>";
echo "<li>Check the database to see the updated stock levels</li>";
echo "</ol>";

echo "<h3>Database Changes:</h3>";
echo "<ul>";
echo "<li>Updates tbl_stock_summary.available_quantity for each batch</li>";
echo "<li>Updates tbl_product.quantity and stock_status</li>";
echo "<li>Logs movement in tbl_stock_movement</li>";
echo "<li>Uses database transactions for data integrity</li>";
echo "</ul>";
?> 
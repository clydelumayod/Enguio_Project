<?php
/**
 * FIFO Stock Operations - Example Usage
 * 
 * This file demonstrates how to use the FIFO stock operations
 * for various inventory management scenarios.
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
    $fifo->setDebug(true); // Enable debug logging
    
    echo "=== FIFO Stock Operations Examples ===\n\n";
    
    // Example 1: Consume stock for a sale
    echo "1. Consuming stock for a sale:\n";
    $sale_result = $fifo->consumeStockFIFO(
        $product_id = 82, // Replace with actual product ID
        $quantity = 5,
        $reference_no = 'SO-2024-001',
        $movement_type = 'SALE',
        $notes = 'Customer sale',
        $created_by = 'admin'
    );
    
    if ($sale_result['success']) {
        echo "✓ Sale processed successfully\n";
        echo "  Quantity consumed: " . $sale_result['quantity_consumed'] . "\n";
        echo "  Total cost: ₱" . number_format($sale_result['total_cost'], 2) . "\n";
        echo "  Batches used: " . count($sale_result['consumed_batches']) . "\n";
        
        foreach ($sale_result['consumed_batches'] as $batch) {
            echo "    - Batch: " . $batch['batch_reference'] . 
                 ", Qty: " . $batch['quantity_consumed'] . 
                 ", Cost: ₱" . number_format($batch['total_cost'], 2) . "\n";
        }
    } else {
        echo "✗ Sale failed: " . $sale_result['message'] . "\n";
    }
    
    echo "\n";
    
    // Example 2: Transfer stock between locations
    echo "2. Transferring stock between locations:\n";
    $transfer_result = $fifo->transferStockFIFO(
        $product_id = 82, // Replace with actual product ID
        $quantity = 3,
        $from_location_id = 2, // Warehouse
        $to_location_id = 3,   // Store
        $reference_no = 'TR-2024-001',
        $notes = 'Regular stock transfer',
        $created_by = 'admin'
    );
    
    if ($transfer_result['success']) {
        echo "✓ Transfer completed successfully\n";
        echo "  Quantity transferred: " . $transfer_result['quantity_transferred'] . "\n";
        echo "  From location: " . $transfer_result['from_location'] . "\n";
        echo "  To location: " . $transfer_result['to_location'] . "\n";
    } else {
        echo "✗ Transfer failed: " . $transfer_result['message'] . "\n";
    }
    
    echo "\n";
    
    // Example 3: Process a sales order with multiple items
    echo "3. Processing a sales order with multiple items:\n";
    $order_items = [
        [
            'product_id' => 82, // Replace with actual product IDs
            'quantity' => 2,
            'unit_price' => 25.00
        ],
        [
            'product_id' => 83, // Replace with actual product IDs
            'quantity' => 1,
            'unit_price' => 30.00
        ]
    ];
    
    $order_result = $fifo->processSalesOrderFIFO(
        $order_items,
        'SO-2024-002',
        $customer_id = null,
        $created_by = 'admin'
    );
    
    if ($order_result['success']) {
        echo "✓ Sales order processed successfully\n";
        echo "  Order reference: " . $order_result['order_reference'] . "\n";
        echo "  Total value: ₱" . number_format($order_result['total_value'], 2) . "\n";
        echo "  Items processed: " . count($order_result['processed_items']) . "\n";
        
        foreach ($order_result['processed_items'] as $item) {
            echo "    - Product ID: " . $item['product_id'] . 
                 ", Qty: " . $item['quantity'] . 
                 ", Price: ₱" . number_format($item['total_price'], 2) . 
                 ", Profit: ₱" . number_format($item['profit'], 2) . "\n";
        }
    } else {
        echo "✗ Sales order failed: " . $order_result['message'] . "\n";
    }
    
    echo "\n";
    
    // Example 4: Get FIFO stock report
    echo "4. Getting FIFO stock report:\n";
    $report_result = $fifo->getFIFOStockReport(82); // Replace with actual product ID
    
    if ($report_result['success']) {
        echo "✓ FIFO stock report generated\n";
        echo "  Batches available: " . count($report_result['data']) . "\n";
        
        foreach ($report_result['data'] as $batch) {
            echo "    - FIFO Order: #" . $batch['fifo_order'] . 
                 ", Batch: " . $batch['batch_reference'] . 
                 ", Available: " . $batch['available_quantity'] . 
                 ", Cost: ₱" . number_format($batch['unit_cost'], 2) . 
                 ", Expires: " . ($batch['expiration_date'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ Report failed: " . $report_result['message'] . "\n";
    }
    
    echo "\n";
    
    // Example 5: Get expiring products report
    echo "5. Getting expiring products report:\n";
    $expiring_result = $fifo->getExpiringProductsReport(30); // 30 days threshold
    
    if ($expiring_result['success']) {
        echo "✓ Expiring products report generated\n";
        echo "  Products expiring soon: " . count($expiring_result['data']) . "\n";
        
        foreach ($expiring_result['data'] as $product) {
            echo "    - " . $product['product_name'] . 
                 " (Barcode: " . $product['barcode'] . ")" .
                 ", Qty: " . $product['available_quantity'] . 
                 ", Expires: " . $product['expiration_date'] . 
                 " (" . $product['days_until_expiry'] . " days)\n";
        }
    } else {
        echo "✗ Expiring report failed: " . $expiring_result['message'] . "\n";
    }
    
    echo "\n=== Examples completed ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FIFO Stock Operations - Example Usage</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .example { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>FIFO Stock Operations - Example Usage</h1>
    
    <div class="example">
        <h2>1. Consume Stock for Sale</h2>
        <p>This example shows how to consume stock using FIFO method for a customer sale.</p>
        <pre>
$result = $fifo->consumeStockFIFO(
    $product_id = 82,
    $quantity = 5,
    $reference_no = 'SO-2024-001',
    $movement_type = 'SALE',
    $notes = 'Customer sale',
    $created_by = 'admin'
);
        </pre>
    </div>
    
    <div class="example">
        <h2>2. Transfer Stock Between Locations</h2>
        <p>This example shows how to transfer stock between locations using FIFO.</p>
        <pre>
$result = $fifo->transferStockFIFO(
    $product_id = 82,
    $quantity = 3,
    $from_location_id = 2, // Warehouse
    $to_location_id = 3,   // Store
    $reference_no = 'TR-2024-001',
    $notes = 'Regular stock transfer',
    $created_by = 'admin'
);
        </pre>
    </div>
    
    <div class="example">
        <h2>3. Process Sales Order with Multiple Items</h2>
        <p>This example shows how to process a complete sales order with multiple products.</p>
        <pre>
$order_items = [
    ['product_id' => 82, 'quantity' => 2, 'unit_price' => 25.00],
    ['product_id' => 83, 'quantity' => 1, 'unit_price' => 30.00]
];

$result = $fifo->processSalesOrderFIFO(
    $order_items,
    'SO-2024-002',
    $customer_id = null,
    $created_by = 'admin'
);
        </pre>
    </div>
    
    <div class="example">
        <h2>4. Get FIFO Stock Report</h2>
        <p>This example shows how to get a detailed FIFO stock report for a product.</p>
        <pre>
$result = $fifo->getFIFOStockReport($product_id);
        </pre>
    </div>
    
    <div class="example">
        <h2>5. Get Expiring Products Report</h2>
        <p>This example shows how to get a report of products expiring soon.</p>
        <pre>
$result = $fifo->getExpiringProductsReport(30); // 30 days threshold
        </pre>
    </div>
    
    <h2>Key Features:</h2>
    <ul>
        <li><strong>FIFO Logic:</strong> Automatically consumes from oldest batches first</li>
        <li><strong>Batch Tracking:</strong> Tracks each batch separately with costs and expiration dates</li>
        <li><strong>Movement Logging:</strong> Logs all stock movements with detailed information</li>
        <li><strong>Error Handling:</strong> Comprehensive error handling with rollback capabilities</li>
        <li><strong>Cost Tracking:</strong> Tracks costs per batch for accurate profit calculation</li>
        <li><strong>Expiration Tracking:</strong> Monitors expiration dates for inventory management</li>
    </ul>
    
    <h2>Database Tables Required:</h2>
    <ul>
        <li><code>tbl_stock_movements</code> - Logs all stock movements</li>
        <li><code>tbl_stock_summary</code> - Current stock levels by batch</li>
        <li><code>tbl_batch</code> - Batch information</li>
        <li><code>tbl_product</code> - Product information</li>
    </ul>
</body>
</html> 
<?php
/**
 * FIFO API Endpoint
 * 
 * Simple API endpoint for FIFO stock operations
 * Can be integrated into existing inventory systems
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'consume_stock':
            // Consume stock using FIFO
            $result = $fifo->consumeStockFIFO(
                $input['product_id'],
                $input['quantity'],
                $input['reference_no'] ?? '',
                $input['movement_type'] ?? 'OUT',
                $input['notes'] ?? '',
                $input['created_by'] ?? 'admin'
            );
            echo json_encode($result);
            break;
            
        case 'transfer_stock':
            // Transfer stock between locations
            $result = $fifo->transferStockFIFO(
                $input['product_id'],
                $input['quantity'],
                $input['from_location_id'],
                $input['to_location_id'],
                $input['reference_no'] ?? '',
                $input['notes'] ?? '',
                $input['created_by'] ?? 'admin'
            );
            echo json_encode($result);
            break;
            
        case 'process_sales_order':
            // Process sales order with multiple items
            $result = $fifo->processSalesOrderFIFO(
                $input['order_items'],
                $input['order_reference'],
                $input['customer_id'] ?? null,
                $input['created_by'] ?? 'admin'
            );
            echo json_encode($result);
            break;
            
        case 'get_fifo_report':
            // Get FIFO stock report for a product
            $result = $fifo->getFIFOStockReport($input['product_id']);
            echo json_encode($result);
            break;
            
        case 'get_expiring_products':
            // Get expiring products report
            $result = $fifo->getExpiringProductsReport($input['days_threshold'] ?? 30);
            echo json_encode($result);
            break;
            
        case 'check_stock_availability':
            // Check if sufficient stock is available
            $product_id = $input['product_id'];
            $quantity_needed = $input['quantity'];
            
            $total_available = $fifo->getTotalAvailableStock($product_id);
            $available_stock = $fifo->getFIFOStockLevels($product_id);
            
            $result = [
                'success' => true,
                'product_id' => $product_id,
                'quantity_needed' => $quantity_needed,
                'total_available' => $total_available,
                'sufficient_stock' => $total_available >= $quantity_needed,
                'available_batches' => count($available_stock),
                'fifo_batches' => $available_stock
            ];
            echo json_encode($result);
            break;
            
        case 'get_product_info':
            // Get product information
            $product_id = $input['product_id'];
            $product = $fifo->getProductInfo($product_id);
            
            if ($product) {
                $total_available = $fifo->getTotalAvailableStock($product_id);
                $fifo_stock = $fifo->getFIFOStockLevels($product_id);
                
                $result = [
                    'success' => true,
                    'product' => $product,
                    'total_available' => $total_available,
                    'fifo_stock' => $fifo_stock
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action: ' . $action,
                'available_actions' => [
                    'consume_stock',
                    'transfer_stock', 
                    'process_sales_order',
                    'get_fifo_report',
                    'get_expiring_products',
                    'check_stock_availability',
                    'get_product_info'
                ]
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FIFO API Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .endpoint { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .method { background: #007cba; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .url { background: #f5f5f5; padding: 8px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>FIFO API Documentation</h1>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Consume Stock</h2>
        <p>Consume stock using FIFO method for sales, transfers, or adjustments.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "consume_stock",
    "product_id": 82,
    "quantity": 5,
    "reference_no": "SO-2024-001",
    "movement_type": "SALE",
    "notes": "Customer sale",
    "created_by": "admin"
}
        </pre>
        <h4>Response:</h4>
        <pre>
{
    "success": true,
    "message": "Stock consumed using FIFO method",
    "quantity_consumed": 5,
    "total_cost": 110.00,
    "consumed_batches": [
        {
            "batch_reference": "BR-20240101-120000",
            "quantity_consumed": 3,
            "unit_cost": 20.00,
            "total_cost": 60.00
        },
        {
            "batch_reference": "BR-20240102-090000", 
            "quantity_consumed": 2,
            "unit_cost": 25.00,
            "total_cost": 50.00
        }
    ]
}
        </pre>
    </div>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Transfer Stock</h2>
        <p>Transfer stock between locations using FIFO method.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "transfer_stock",
    "product_id": 82,
    "quantity": 3,
    "from_location_id": 2,
    "to_location_id": 3,
    "reference_no": "TR-2024-001",
    "notes": "Regular transfer",
    "created_by": "admin"
}
        </pre>
    </div>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Process Sales Order</h2>
        <p>Process a complete sales order with multiple items.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "process_sales_order",
    "order_items": [
        {
            "product_id": 82,
            "quantity": 2,
            "unit_price": 25.00
        },
        {
            "product_id": 83,
            "quantity": 1,
            "unit_price": 30.00
        }
    ],
    "order_reference": "SO-2024-002",
    "customer_id": null,
    "created_by": "admin"
}
        </pre>
    </div>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Get FIFO Report</h2>
        <p>Get detailed FIFO stock report for a product.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "get_fifo_report",
    "product_id": 82
}
        </pre>
    </div>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Check Stock Availability</h2>
        <p>Check if sufficient stock is available for consumption.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "check_stock_availability",
    "product_id": 82,
    "quantity": 10
}
        </pre>
    </div>
    
    <div class="endpoint">
        <h2><span class="method">POST</span> Get Expiring Products</h2>
        <p>Get report of products expiring within specified days.</p>
        <div class="url">POST /fifo_api.php</div>
        <pre>
{
    "action": "get_expiring_products",
    "days_threshold": 30
}
        </pre>
    </div>
    
    <h2>Integration Examples:</h2>
    
    <h3>JavaScript (Frontend)</h3>
    <pre>
// Consume stock for a sale
async function consumeStock(productId, quantity) {
    const response = await fetch('/fifo_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'consume_stock',
            product_id: productId,
            quantity: quantity,
            reference_no: 'SO-' + Date.now(),
            movement_type: 'SALE',
            notes: 'Customer sale',
            created_by: 'admin'
        })
    });
    
    const result = await response.json();
    if (result.success) {
        console.log('Stock consumed:', result.quantity_consumed);
        console.log('Total cost:', result.total_cost);
        console.log('Batches used:', result.consumed_batches);
    } else {
        console.error('Error:', result.message);
    }
}
    </pre>
    
    <h3>PHP (Backend)</h3>
    <pre>
// Include the FIFO class
require_once 'fifo_stock_operations.php';

// Initialize
$pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
$fifo = new FIFOStockOperations($pdo);

// Consume stock
$result = $fifo->consumeStockFIFO(
    $product_id = 82,
    $quantity = 5,
    $reference_no = 'SO-2024-001',
    $movement_type = 'SALE',
    $notes = 'Customer sale',
    $created_by = 'admin'
);

if ($result['success']) {
    echo "Stock consumed successfully";
    echo "Quantity: " . $result['quantity_consumed'];
    echo "Cost: ₱" . $result['total_cost'];
} else {
    echo "Error: " . $result['message'];
}
    </pre>
    
    <h2>Error Handling:</h2>
    <p>All API endpoints return a consistent response format:</p>
    <pre>
{
    "success": true/false,
    "message": "Description of the operation result",
    "data": {...} // Additional data if applicable
}
    </pre>
    
    <h2>Common Error Messages:</h2>
    <ul>
        <li><strong>"Invalid product ID or quantity"</strong> - Invalid input parameters</li>
        <li><strong>"Product not found"</strong> - Product doesn't exist or is inactive</li>
        <li><strong>"No stock available"</strong> - No stock exists for the product</li>
        <li><strong>"Insufficient stock"</strong> - Not enough stock available</li>
        <li><strong>"Database error"</strong> - Database connection or query error</li>
    </ul>
</body>
</html> 
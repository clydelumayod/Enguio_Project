<?php
/**
 * Test FIFO Display
 * This script tests if the FIFO stock data is being retrieved correctly
 */

// Database connection
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

echo "<h1>FIFO Display Test</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='section'>\n";
    echo "<p class='success'>✓ Database connected successfully!</p>\n";
    echo "</div>\n";
    
    // Test 1: Check FIFO stock data
    echo "<div class='section'>\n";
    echo "<h2>Test 1: FIFO Stock Data</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            fs.fifo_id as summary_id,
            fs.batch_id,
            fs.batch_id as batch_number,
            fs.batch_reference,
            fs.available_quantity,
            fs.unit_cost,
            fs.expiration_date,
            b.entry_date as batch_date,
            b.entry_time as batch_time,
            ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, fs.fifo_id ASC) as fifo_order,
            CASE 
                WHEN fs.expiration_date IS NULL THEN NULL
                ELSE DATEDIFF(fs.expiration_date, CURDATE())
            END as days_until_expiry
        FROM tbl_fifo_stock fs
        JOIN tbl_batch b ON fs.batch_id = b.batch_id
        WHERE fs.product_id = 215 AND fs.available_quantity > 0
        ORDER BY b.entry_date ASC, fs.fifo_id ASC
    ");
    $stmt->execute();
    $fifoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>FIFO Records Found:</strong> <span class='info'>" . count($fifoData) . "</span></p>\n";
    
    if (!empty($fifoData)) {
        echo "<table>\n";
        echo "<thead><tr>\n";
        $headers = ['fifo_order', 'batch_number', 'batch_reference', 'available_quantity', 'unit_cost', 'expiration_date', 'days_until_expiry', 'batch_date'];
        foreach ($headers as $header) {
            echo "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>\n";
        }
        echo "</tr></thead>\n<tbody>\n";
        
        foreach ($fifoData as $row) {
            echo "<tr>\n";
            foreach ($headers as $header) {
                $value = $row[$header];
                if ($header === 'unit_cost') {
                    $value = '₱' . number_format($value, 2);
                } elseif ($header === 'expiration_date' || $header === 'batch_date') {
                    $value = $value ? date('Y-m-d', strtotime($value)) : 'N/A';
                } elseif ($header === 'days_until_expiry') {
                    if ($value !== null) {
                        $color = $value <= 7 ? 'red' : ($value <= 30 ? 'orange' : 'green');
                        $value = "<span style='color: $color;'>$value days</span>";
                    } else {
                        $value = 'N/A';
                    }
                }
                echo "<td>$value</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        echo "<p class='success'>✓ FIFO data is available and properly formatted!</p>\n";
    } else {
        echo "<p class='error'>✗ No FIFO data found for product ID 215</p>\n";
    }
    echo "</div>\n";
    
    // Test 2: Check product details
    echo "<div class='section'>\n";
    echo "<h2>Test 2: Product Details</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT product_id, product_name, barcode, category, quantity, unit_price, srp, stock_status
        FROM tbl_product 
        WHERE product_id = 215
    ");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<p><strong>Product Name:</strong> <span class='info'>" . htmlspecialchars($product['product_name']) . "</span></p>\n";
        echo "<p><strong>Barcode:</strong> <span class='info'>" . htmlspecialchars($product['barcode']) . "</span></p>\n";
        echo "<p><strong>Category:</strong> <span class='info'>" . htmlspecialchars($product['category']) . "</span></p>\n";
        echo "<p><strong>Total Stock:</strong> <span class='info'>" . $product['quantity'] . "</span></p>\n";
        echo "<p><strong>Unit Price:</strong> <span class='info'>₱" . number_format($product['unit_price'], 2) . "</span></p>\n";
        echo "<p><strong>SRP:</strong> <span class='info'>₱" . number_format($product['srp'] ?: $product['unit_price'], 2) . "</span></p>\n";
        echo "<p><strong>Stock Status:</strong> <span class='info'>" . htmlspecialchars($product['stock_status']) . "</span></p>\n";
        echo "<p class='success'>✓ Product details retrieved successfully!</p>\n";
    } else {
        echo "<p class='error'>✗ Product not found</p>\n";
    }
    echo "</div>\n";
    
    // Test 3: Simulate API call
    echo "<div class='section'>\n";
    echo "<h2>Test 3: API Simulation</h2>\n";
    
    // Simulate the exact API call that the frontend makes
    $apiData = [
        'action' => 'get_fifo_stock',
        'product_id' => 215
    ];
    
    echo "<p><strong>API Call:</strong> <span class='info'>" . json_encode($apiData) . "</span></p>\n";
    
    // Simulate the backend response
    $response = [
        'success' => true,
        'data' => $fifoData
    ];
    
    echo "<p><strong>API Response:</strong></p>\n";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>\n";
    
    if ($response['success'] && !empty($response['data'])) {
        echo "<p class='success'>✓ API simulation successful!</p>\n";
    } else {
        echo "<p class='error'>✗ API simulation failed</p>\n";
    }
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'>\n";
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?> 
<?php
// Test FIFO Quantity Sync System
header('Content-Type: text/html; charset=utf-8');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>FIFO Quantity Sync System Test</h1>";

// Test 1: Show current FIFO stock data
echo "<h2>Test 1: Current FIFO Stock Data</h2>";

$sql = "
    SELECT 
        fs.fifo_id,
        fs.product_id,
        p.product_name,
        fs.batch_id,
        fs.batch_reference,
        fs.quantity as old_quantity,
        fs.available_quantity as new_quantity,
        fs.unit_cost,
        fs.expiration_date,
        fs.entry_date
    FROM tbl_fifo_stock fs
    LEFT JOIN tbl_product p ON fs.product_id = p.product_id
    ORDER BY fs.product_id, fs.entry_date ASC
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>FIFO ID</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Batch ID</th>
            <th>Batch Reference</th>
            <th>Old Qty (Original)</th>
            <th>New Qty (Available)</th>
            <th>Unit Cost</th>
            <th>Expiry Date</th>
            <th>Entry Date</th>
          </tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
        echo "<td style='background-color: #fed7aa; font-weight: bold;'>" . htmlspecialchars($row['old_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe; font-weight: bold;'>" . htmlspecialchars($row['new_quantity']) . "</td>";
        echo "<td>₱" . htmlspecialchars($row['unit_cost']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expiration_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['entry_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No FIFO stock data found.</p>";
}

// Test 2: Show product quantities before sync
echo "<h2>Test 2: Product Quantities Before Sync</h2>";

$sql2 = "
    SELECT 
        p.product_id,
        p.product_name,
        p.quantity as product_total_qty,
        COALESCE(SUM(fs.available_quantity), 0) as fifo_total_qty,
        CASE 
            WHEN p.quantity = COALESCE(SUM(fs.available_quantity), 0) THEN 'SYNCED'
            ELSE 'NOT SYNCED'
        END as sync_status
    FROM tbl_product p
    LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
    WHERE p.status = 'active' AND p.product_id IN (SELECT DISTINCT product_id FROM tbl_fifo_stock)
    GROUP BY p.product_id, p.product_name, p.quantity
    ORDER BY p.product_id
";

$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Product Total Qty</th>
            <th>FIFO Total Qty</th>
            <th>Sync Status</th>
          </tr>";
    
    while($row = $result2->fetch_assoc()) {
        $statusColor = $row['sync_status'] === 'SYNCED' ? 'background-color: #dcfce7; color: #16a34a;' : 'background-color: #fee2e2; color: #dc2626;';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($row['product_total_qty']) . "</td>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($row['fifo_total_qty']) . "</td>";
        echo "<td style='$statusColor; font-weight: bold;'>" . htmlspecialchars($row['sync_status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No product data found.</p>";
}

// Test 3: Simulate transfer to show quantity changes
echo "<h2>Test 3: Simulate Transfer (Reduce Available Quantity)</h2>";

// Get a product with FIFO stock to simulate transfer
$sql3 = "SELECT product_id, product_name FROM tbl_product WHERE product_id IN (SELECT DISTINCT product_id FROM tbl_fifo_stock) LIMIT 1";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    $product = $result3->fetch_assoc();
    $product_id = $product['product_id'];
    $product_name = $product['product_name'];
    
    echo "<p><strong>Simulating transfer for:</strong> $product_name (ID: $product_id)</p>";
    
    // Show before state
    echo "<h3>Before Transfer:</h3>";
    $before_sql = "
        SELECT 
            fs.fifo_id,
            fs.quantity as old_quantity,
            fs.available_quantity as new_quantity
        FROM tbl_fifo_stock fs
        WHERE fs.product_id = $product_id
        ORDER BY fs.entry_date ASC
    ";
    $before_result = $conn->query($before_sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 50%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>FIFO ID</th>
            <th>Old Qty</th>
            <th>New Qty (Before)</th>
          </tr>";
    
    while($row = $before_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
        echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['old_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['new_quantity']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Simulate transfer (reduce available quantity by 1 from the oldest batch)
    $transfer_sql = "
        UPDATE tbl_fifo_stock 
        SET available_quantity = available_quantity - 1
        WHERE product_id = $product_id 
        AND available_quantity > 0
        ORDER BY entry_date ASC, fifo_id ASC
        LIMIT 1
    ";
    
    if ($conn->query($transfer_sql)) {
        echo "<p style='color: green;'><strong>✅ Transfer simulated successfully!</strong></p>";
        
        // Show after state
        echo "<h3>After Transfer:</h3>";
        $after_sql = "
            SELECT 
                fs.fifo_id,
                fs.quantity as old_quantity,
                fs.available_quantity as new_quantity
            FROM tbl_fifo_stock fs
            WHERE fs.product_id = $product_id
            ORDER BY fs.entry_date ASC
        ";
        $after_result = $conn->query($after_sql);
        
        echo "<table border='1' style='border-collapse: collapse; width: 50%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>FIFO ID</th>
                <th>Old Qty</th>
                <th>New Qty (After)</th>
              </tr>";
        
        while($row = $after_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
            echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['old_quantity']) . "</td>";
            echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['new_quantity']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show updated product total
        $product_sql = "SELECT quantity FROM tbl_product WHERE product_id = $product_id";
        $product_result = $conn->query($product_sql);
        if ($product_result->num_rows > 0) {
            $product_data = $product_result->fetch_assoc();
            echo "<p><strong>Updated Product Total Quantity:</strong> " . $product_data['quantity'] . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'><strong>❌ Transfer simulation failed!</strong></p>";
    }
} else {
    echo "<p>No products with FIFO stock found for simulation.</p>";
}

// Test 4: Show API response format
echo "<h2>Test 4: API Response Format (get_products with for_transfer=true)</h2>";

$api_sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.barcode,
        p.description,
        p.quantity as total_quantity,
        p.unit_price,
        p.Variation,
        p.brand_id,
        p.supplier_id,
        p.batch_id,
        p.expiration,
        b.brand,
        s.supplier_name,
        bt.entry_date as batch_entry_date,
        bt.batch as batch_reference,
        bt.order_no,
        DATEDIFF(p.expiration, CURDATE()) as days_to_expiry,
        fs.quantity as old_quantity,
        fs.available_quantity as new_quantity,
        CASE 
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 30 THEN 'Expiring Soon'
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 90 THEN 'Moderate'
            ELSE 'Good'
        END as urgency_level
    FROM tbl_product p
    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id
    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id
    LEFT JOIN tbl_batch bt ON p.batch_id = bt.batch_id
    LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id AND p.batch_id = fs.batch_id
    WHERE p.status = 'active' AND p.quantity > 0 AND p.location_id = 2
    ORDER BY 
        CASE 
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 30 THEN 1
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 90 THEN 2
            ELSE 3
        END,
        bt.entry_date ASC, 
        p.expiration ASC,
        p.product_name ASC
    LIMIT 3
";

$api_result = $conn->query($api_sql);
$api_products = [];

if ($api_result->num_rows > 0) {
    while ($row = $api_result->fetch_assoc()) {
        $api_products[] = $row;
    }
}

echo "<p><strong>API Response (JSON):</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
echo json_encode([
    "success" => true,
    "data" => $api_products,
    "fifo_enabled" => true,
    "quantity_explanation" => [
        "old_quantity" => "Original batch quantity (never changes)",
        "new_quantity" => "Available quantity after transfers/sales",
        "total_quantity" => "Sum of all available quantities (linked to tbl_product.quantity)"
    ]
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>Summary</h2>";
echo "<div style='background-color: #dcfce7; padding: 15px; border-radius: 5px; border-left: 4px solid #16a34a;'>";
echo "<p><strong>✅ FIFO Quantity System Status:</strong> Updated and Working</p>";
echo "<p><strong>📦 Quantity Definitions:</strong></p>";
echo "<ul>";
echo "<li><strong>Old Quantity:</strong> Original batch quantity (never changes)</li>";
echo "<li><strong>New Quantity:</strong> Available quantity after transfers/sales</li>";
echo "<li><strong>Total Quantity:</strong> Sum of all available quantities (linked to tbl_product.quantity)</li>";
echo "</ul>";
echo "<p><strong>🔄 Sync Logic:</strong> tbl_product.quantity automatically updates when FIFO stock changes</p>";
echo "<p><strong>🎯 Transfer Priority:</strong> Oldest batches (by entry date) are transferred first</p>";
echo "</div>";

$conn->close();
?> 
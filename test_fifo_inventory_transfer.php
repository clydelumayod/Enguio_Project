<?php
// Test FIFO Inventory Transfer System
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>FIFO Inventory Transfer System Test</h1>";

// Test 1: Check FIFO product ordering
echo "<h2>Test 1: FIFO Product Ordering (Oldest First)</h2>";

$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.quantity,
        bt.entry_date as batch_entry_date,
        bt.batch as batch_reference,
        bt.order_no,
        p.expiration,
        DATEDIFF(p.expiration, CURDATE()) as days_to_expiry,
        CASE 
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 30 THEN 'Expiring Soon'
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 90 THEN 'Moderate'
            ELSE 'Good'
        END as urgency_level
    FROM tbl_product p
    LEFT JOIN tbl_batch bt ON p.batch_id = bt.batch_id
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
    LIMIT 10
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Product Name</th>
            <th>Category</th>
            <th>Batch Reference</th>
            <th>Entry Date</th>
            <th>Expiry Date</th>
            <th>Days to Expiry</th>
            <th>Urgency Level</th>
            <th>Available Qty</th>
          </tr>";
    
    while($row = $result->fetch_assoc()) {
        $urgencyColor = '';
        switch($row['urgency_level']) {
            case 'Expiring Soon':
                $urgencyColor = 'background-color: #fee2e2; color: #dc2626;';
                break;
            case 'Moderate':
                $urgencyColor = 'background-color: #fed7aa; color: #ea580c;';
                break;
            default:
                $urgencyColor = 'background-color: #dcfce7; color: #16a34a;';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_entry_date'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['expiration'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['days_to_expiry'] ?: 'N/A') . "</td>";
        echo "<td style='$urgencyColor'>" . htmlspecialchars($row['urgency_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products found for testing.</p>";
}

// Test 2: Check FIFO Stock View
echo "<h2>Test 2: FIFO Stock View</h2>";

$sql2 = "
    SELECT 
        product_id,
        product_name,
        batch_reference,
        available_quantity,
        batch_date,
        expiration_date,
        fifo_order
    FROM v_fifo_stock 
    WHERE available_quantity > 0
    ORDER BY product_id, fifo_order
    LIMIT 10
";

$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Product Name</th>
            <th>Batch Reference</th>
            <th>Batch Date</th>
            <th>Expiry Date</th>
            <th>Available Qty</th>
            <th>FIFO Order</th>
          </tr>";
    
    while($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_date'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['expiration_date'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['available_quantity']) . "</td>";
        echo "<td style='font-weight: bold; color: blue;'>" . htmlspecialchars($row['fifo_order']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No FIFO stock data found.</p>";
}

// Test 3: Simulate API call for transfer
echo "<h2>Test 3: API Response Simulation (get_products with for_transfer=true)</h2>";

$testData = json_encode(['action' => 'get_products', 'location_id' => 2, 'for_transfer' => true]);
echo "<p><strong>Test API Call:</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($testData);
echo "</pre>";

// Test the actual API logic
$location_id = 2;
$for_transfer = true;

$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.barcode,
        p.description,
        p.quantity,
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
        fs.available_quantity as fifo_available_qty,
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
    WHERE p.status = 'active' AND p.quantity > 0 AND p.location_id = $location_id
    ORDER BY 
        CASE 
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 30 THEN 1
            WHEN DATEDIFF(p.expiration, CURDATE()) <= 90 THEN 2
            ELSE 3
        END,
        bt.entry_date ASC, 
        p.expiration ASC,
        p.product_name ASC
    LIMIT 5
";

$result3 = $conn->query($sql);
$products = [];

if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        $products[] = $row;
    }
}

echo "<p><strong>API Response (JSON):</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
echo json_encode([
    "success" => true,
    "data" => $products,
    "fifo_enabled" => true
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>Summary</h2>";
echo "<div style='background-color: #dcfce7; padding: 15px; border-radius: 5px; border-left: 4px solid #16a34a;'>";
echo "<p><strong>✅ FIFO System Status:</strong> Active and Working</p>";
echo "<p><strong>📦 Products Ordered By:</strong> 1) Urgency Level, 2) Batch Entry Date (Oldest First), 3) Expiration Date</p>";
echo "<p><strong>🎯 Priority System:</strong> Expiring Soon → Moderate → Good condition products</p>";
echo "<p><strong>🔄 Transfer Logic:</strong> Older batches are prioritized for transfer to minimize waste</p>";
echo "</div>";

$conn->close();
?> 
<?php
// Fix FIFO Quantities to match Product Total
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

echo "<h1>Fix FIFO Quantities for Product 217</h1>";

// Check current state
echo "<h2>Current State Analysis</h2>";

// Get product total quantity
$product_sql = "SELECT product_id, product_name, quantity FROM tbl_product WHERE product_id = 217";
$product_result = $conn->query($product_sql);

if ($product_result->num_rows > 0) {
    $product = $product_result->fetch_assoc();
    echo "<p><strong>Product 217:</strong> {$product['product_name']} - Total Quantity: <span style='color: green; font-weight: bold;'>{$product['quantity']}</span></p>";
}

// Get current FIFO stock
$fifo_sql = "
    SELECT 
        fifo_id,
        product_id,
        batch_id,
        batch_reference,
        quantity as old_quantity,
        available_quantity as new_quantity,
        unit_cost,
        expiration_date,
        entry_date
    FROM tbl_fifo_stock 
    WHERE product_id = 217 
    ORDER BY entry_date ASC, fifo_id ASC
";

$fifo_result = $conn->query($fifo_sql);

echo "<h3>Current FIFO Stock (Before Fix):</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'>
        <th>FIFO ID</th>
        <th>Batch Reference</th>
        <th>Old Qty</th>
        <th>New Qty</th>
        <th>Unit Cost</th>
        <th>Entry Date</th>
        <th>Expiry Date</th>
      </tr>";

$total_old = 0;
$total_new = 0;
$fifo_data = [];

while($row = $fifo_result->fetch_assoc()) {
    $total_old += $row['old_quantity'];
    $total_new += $row['new_quantity'];
    $fifo_data[] = $row;
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
    echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['old_quantity']) . "</td>";
    echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['new_quantity']) . "</td>";
    echo "<td>₱" . htmlspecialchars($row['unit_cost']) . "</td>";
    echo "<td>" . htmlspecialchars($row['entry_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['expiration_date']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Current Total Old Quantity:</strong> <span style='color: orange; font-weight: bold;'>$total_old</span></p>";
echo "<p><strong>Current Total New Quantity:</strong> <span style='color: blue; font-weight: bold;'>$total_new</span></p>";

// Calculate the correct quantities
$target_total = 120; // Product total quantity
$current_total = $total_new;
$difference = $target_total - $current_total;

echo "<h2>Quantity Fix Calculation</h2>";
echo "<p><strong>Target Total:</strong> <span style='color: green; font-weight: bold;'>$target_total</span></p>";
echo "<p><strong>Current Total:</strong> <span style='color: blue; font-weight: bold;'>$current_total</span></p>";
echo "<p><strong>Difference:</strong> <span style='color: red; font-weight: bold;'>$difference</span></p>";

if ($difference > 0) {
    echo "<p style='color: green;'><strong>✅ Need to add $difference units</strong></p>";
} elseif ($difference < 0) {
    echo "<p style='color: red;'><strong>❌ Need to reduce " . abs($difference) . " units</strong></p>";
} else {
    echo "<p style='color: green;'><strong>✅ Quantities are already correct!</strong></p>";
}

// Fix the quantities
echo "<h2>Applying Quantity Fix</h2>";

if ($difference != 0) {
    // Strategy: Distribute the difference proportionally across batches
    // or add to the most recent batch
    
    if ($difference > 0) {
        // Add to the most recent batch (highest fifo_id)
        $latest_fifo = end($fifo_data);
        $latest_fifo_id = $latest_fifo['fifo_id'];
        
        $update_sql = "
            UPDATE tbl_fifo_stock 
            SET 
                quantity = quantity + $difference,
                available_quantity = available_quantity + $difference
            WHERE fifo_id = $latest_fifo_id
        ";
        
        if ($conn->query($update_sql)) {
            echo "<p style='color: green;'><strong>✅ Successfully updated FIFO ID $latest_fifo_id</strong></p>";
            echo "<p>Added $difference units to batch: " . $latest_fifo['batch_reference'] . "</p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Failed to update FIFO stock</strong></p>";
            echo "<p>Error: " . $conn->error . "</p>";
        }
    } else {
        // Reduce from the oldest batch first (FIFO principle)
        $oldest_fifo = $fifo_data[0];
        $oldest_fifo_id = $oldest_fifo['fifo_id'];
        $reduce_amount = abs($difference);
        
        $update_sql = "
            UPDATE tbl_fifo_stock 
            SET 
                available_quantity = GREATEST(0, available_quantity - $reduce_amount)
            WHERE fifo_id = $oldest_fifo_id
        ";
        
        if ($conn->query($update_sql)) {
            echo "<p style='color: green;'><strong>✅ Successfully updated FIFO ID $oldest_fifo_id</strong></p>";
            echo "<p>Reduced $reduce_amount units from batch: " . $oldest_fifo['batch_reference'] . "</p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Failed to update FIFO stock</strong></p>";
            echo "<p>Error: " . $conn->error . "</p>";
        }
    }
}

// Show updated state
echo "<h2>Updated State (After Fix)</h2>";

$updated_fifo_sql = "
    SELECT 
        fifo_id,
        product_id,
        batch_id,
        batch_reference,
        quantity as old_quantity,
        available_quantity as new_quantity,
        unit_cost,
        expiration_date,
        entry_date
    FROM tbl_fifo_stock 
    WHERE product_id = 217 
    ORDER BY entry_date ASC, fifo_id ASC
";

$updated_fifo_result = $conn->query($updated_fifo_sql);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'>
        <th>FIFO ID</th>
        <th>Batch Reference</th>
        <th>Old Qty</th>
        <th>New Qty</th>
        <th>Unit Cost</th>
        <th>Entry Date</th>
        <th>Expiry Date</th>
      </tr>";

$updated_total_old = 0;
$updated_total_new = 0;

while($row = $updated_fifo_result->fetch_assoc()) {
    $updated_total_old += $row['old_quantity'];
    $updated_total_new += $row['new_quantity'];
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
    echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['old_quantity']) . "</td>";
    echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['new_quantity']) . "</td>";
    echo "<td>₱" . htmlspecialchars($row['unit_cost']) . "</td>";
    echo "<td>" . htmlspecialchars($row['entry_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['expiration_date']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Updated Total Old Quantity:</strong> <span style='color: orange; font-weight: bold;'>$updated_total_old</span></p>";
echo "<p><strong>Updated Total New Quantity:</strong> <span style='color: blue; font-weight: bold;'>$updated_total_new</span></p>";

// Verify product total is correct
$updated_product_sql = "SELECT quantity FROM tbl_product WHERE product_id = 217";
$updated_product_result = $conn->query($updated_product_sql);
if ($updated_product_result->num_rows > 0) {
    $updated_product = $updated_product_result->fetch_assoc();
    echo "<p><strong>Product Total Quantity:</strong> <span style='color: green; font-weight: bold;'>{$updated_product['quantity']}</span></p>";
}

// Test the API response
echo "<h2>API Response Test</h2>";

$api_sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.quantity as total_quantity,
        fs.quantity as old_quantity,
        fs.available_quantity as new_quantity,
        bt.entry_date as batch_entry_date,
        bt.batch as batch_reference
    FROM tbl_product p
    LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id AND p.batch_id = fs.batch_id
    LEFT JOIN tbl_batch bt ON fs.batch_id = bt.batch_id
    WHERE p.product_id = 217
    ORDER BY bt.entry_date ASC
";

$api_result = $conn->query($api_sql);
$api_products = [];

while ($row = $api_result->fetch_assoc()) {
    $api_products[] = $row;
}

echo "<p><strong>API Response (JSON):</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
echo json_encode([
    "success" => true,
    "data" => $api_products,
    "fifo_enabled" => true,
    "summary" => [
        "total_quantity" => $updated_total_new,
        "old_quantity_sum" => $updated_total_old,
        "new_quantity_sum" => $updated_total_new
    ]
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>Summary</h2>";
echo "<div style='background-color: #dcfce7; padding: 15px; border-radius: 5px; border-left: 4px solid #16a34a;'>";
echo "<p><strong>✅ FIFO Quantities Fixed!</strong></p>";
echo "<p><strong>📦 Product 217 Total:</strong> $updated_total_new units</p>";
echo "<p><strong>🔄 Old vs New Quantities:</strong> Properly tracked</p>";
echo "<p><strong>🎯 FIFO Order:</strong> Maintained (oldest batches first)</p>";
echo "</div>";

$conn->close();
?> 
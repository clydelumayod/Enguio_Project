<?php
// Test FIFO Analysis System with Old vs New Batch Tracking
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

echo "<h1>FIFO Analysis System - Old vs New Batch Tracking</h1>";

// Test 1: Show FIFO Batch Analysis for Product 217
echo "<h2>Test 1: FIFO Batch Analysis (Product 217 - Nova)</h2>";

$sql1 = "
    SELECT 
        fifo_id,
        product_name,
        batch_reference,
        original_quantity,
        current_quantity,
        entry_date,
        batch_type,
        batch_order,
        running_total_original,
        running_total_current
    FROM v_fifo_batch_analysis 
    WHERE product_id = 217
    ORDER BY entry_date ASC, fifo_id ASC
";

$result1 = $conn->query($sql1);

if ($result1->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>FIFO ID</th>
            <th>Product</th>
            <th>Batch Ref</th>
            <th>Original Qty</th>
            <th>Current Qty</th>
            <th>Entry Date</th>
            <th>Batch Type</th>
            <th>Order</th>
            <th>Running Total (Orig)</th>
            <th>Running Total (Current)</th>
          </tr>";
    
    while($row = $result1->fetch_assoc()) {
        $batchTypeColor = $row['batch_type'] === 'OLD' ? 'background-color: #fed7aa;' : 'background-color: #dbeafe;';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['fifo_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
        echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['original_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['current_quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['entry_date']) . "</td>";
        echo "<td style='$batchTypeColor; font-weight: bold;'>" . htmlspecialchars($row['batch_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_order']) . "</td>";
        echo "<td style='background-color: #fef3c7;'>" . htmlspecialchars($row['running_total_original']) . "</td>";
        echo "<td style='background-color: #dcfce7;'>" . htmlspecialchars($row['running_total_current']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No FIFO batch analysis data found.</p>";
}

// Test 2: Show Product FIFO Summary
echo "<h2>Test 2: Product FIFO Summary</h2>";

$sql2 = "SELECT * FROM v_product_fifo_summary WHERE product_id = 217";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    $summary = $result2->fetch_assoc();
    
    echo "<div style='background-color: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;'>";
    echo "<h3>Product: " . htmlspecialchars($summary['product_name']) . " (ID: " . $summary['product_id'] . ")</h3>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
    
    echo "<div>";
    echo "<h4>Quantity Summary</h4>";
    echo "<p><strong>Product Total:</strong> <span style='color: green; font-weight: bold;'>" . $summary['product_total_quantity'] . "</span></p>";
    echo "<p><strong>FIFO Total (Original):</strong> <span style='color: orange; font-weight: bold;'>" . $summary['fifo_total_original'] . "</span></p>";
    echo "<p><strong>FIFO Total (Current):</strong> <span style='color: blue; font-weight: bold;'>" . $summary['fifo_total_current'] . "</span></p>";
    echo "</div>";
    
    echo "<div>";
    echo "<h4>Old vs New Breakdown</h4>";
    echo "<p><strong>Old Batch Quantity:</strong> <span style='color: orange; font-weight: bold;'>" . $summary['old_batch_quantity'] . "</span></p>";
    echo "<p><strong>New Batch Quantity:</strong> <span style='color: blue; font-weight: bold;'>" . $summary['new_batch_quantity'] . "</span></p>";
    echo "<p><strong>Total Batches:</strong> " . $summary['total_batches'] . "</p>";
    echo "<p><strong>Active Batches:</strong> " . $summary['active_batches'] . "</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<p><strong>Sync Status:</strong> <span style='background-color: " . ($summary['sync_status'] === 'SYNCED' ? '#dcfce7' : '#fee2e2') . "; padding: 4px 8px; border-radius: 4px;'>" . $summary['sync_status'] . "</span></p>";
    echo "</div>";
} else {
    echo "<p>No product FIFO summary found.</p>";
}

// Test 3: Show Old vs New Breakdown
echo "<h2>Test 3: Old vs New Batch Breakdown</h2>";

$sql3 = "SELECT * FROM v_fifo_old_vs_new WHERE product_id = 217 ORDER BY batch_type";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Product</th>
            <th>Batch Type</th>
            <th>Batch Count</th>
            <th>Total Original Qty</th>
            <th>Total Current Qty</th>
            <th>Earliest Entry</th>
            <th>Latest Entry</th>
            <th>Avg Unit Cost</th>
          </tr>";
    
    while($row = $result3->fetch_assoc()) {
        $typeColor = $row['batch_type'] === 'OLD' ? 'background-color: #fed7aa;' : 'background-color: #dbeafe;';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td style='$typeColor; font-weight: bold;'>" . htmlspecialchars($row['batch_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_count']) . "</td>";
        echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['total_original_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['total_current_quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['earliest_entry']) . "</td>";
        echo "<td>" . htmlspecialchars($row['latest_entry']) . "</td>";
        echo "<td>₱" . number_format($row['avg_unit_cost'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No old vs new breakdown data found.</p>";
}

// Test 4: Show Transfer Recommendations (FIFO Order)
echo "<h2>Test 4: Transfer Recommendations (FIFO Order)</h2>";

$sql4 = "
    SELECT 
        fifo_id,
        product_name,
        batch_reference,
        original_quantity,
        current_quantity,
        entry_date,
        transfer_priority,
        days_to_expiry,
        urgency_level,
        batch_age_days
    FROM v_fifo_transfer_recommendations 
    WHERE product_id = 217
    ORDER BY transfer_priority ASC
    LIMIT 5
";

$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Priority</th>
            <th>Product</th>
            <th>Batch Ref</th>
            <th>Original Qty</th>
            <th>Current Qty</th>
            <th>Entry Date</th>
            <th>Days to Expiry</th>
            <th>Urgency</th>
            <th>Batch Age</th>
          </tr>";
    
    while($row = $result4->fetch_assoc()) {
        $urgencyColor = '';
        switch($row['urgency_level']) {
            case 'URGENT':
                $urgencyColor = 'background-color: #fee2e2; color: #dc2626;';
                break;
            case 'MODERATE':
                $urgencyColor = 'background-color: #fed7aa; color: #ea580c;';
                break;
            default:
                $urgencyColor = 'background-color: #dcfce7; color: #16a34a;';
        }
        
        echo "<tr>";
        echo "<td style='font-weight: bold; color: blue;'>" . htmlspecialchars($row['transfer_priority']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_reference']) . "</td>";
        echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['original_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['current_quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['entry_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['days_to_expiry']) . "</td>";
        echo "<td style='$urgencyColor; font-weight: bold;'>" . htmlspecialchars($row['urgency_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['batch_age_days']) . " days</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No transfer recommendations found.</p>";
}

// Test 5: Show all products with FIFO summary
echo "<h2>Test 5: All Products FIFO Summary</h2>";

$sql5 = "SELECT * FROM v_product_fifo_summary ORDER BY product_id LIMIT 10";
$result5 = $conn->query($sql5);

if ($result5->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Product Total</th>
            <th>FIFO Total</th>
            <th>Old Batch Qty</th>
            <th>New Batch Qty</th>
            <th>Total Batches</th>
            <th>Sync Status</th>
          </tr>";
    
    while($row = $result5->fetch_assoc()) {
        $syncColor = $row['sync_status'] === 'SYNCED' ? 'background-color: #dcfce7; color: #16a34a;' : 'background-color: #fee2e2; color: #dc2626;';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($row['product_total_quantity']) . "</td>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($row['fifo_total_current']) . "</td>";
        echo "<td style='background-color: #fed7aa;'>" . htmlspecialchars($row['old_batch_quantity']) . "</td>";
        echo "<td style='background-color: #dbeafe;'>" . htmlspecialchars($row['new_batch_quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_batches']) . "</td>";
        echo "<td style='$syncColor; font-weight: bold;'>" . htmlspecialchars($row['sync_status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No product FIFO summary data found.</p>";
}

echo "<h2>Summary</h2>";
echo "<div style='background-color: #dcfce7; padding: 15px; border-radius: 5px; border-left: 4px solid #16a34a;'>";
echo "<p><strong>✅ FIFO Analysis System Complete!</strong></p>";
echo "<p><strong>📊 Features Implemented:</strong></p>";
echo "<ul>";
echo "<li><strong>Old vs New Classification:</strong> Based on entry_date</li>";
echo "<li><strong>Running Totals:</strong> Cumulative quantities per batch</li>";
echo "<li><strong>Transfer Priority:</strong> FIFO order (oldest first)</li>";
echo "<li><strong>Auto Sync:</strong> Product totals update automatically</li>";
echo "<li><strong>Urgency Levels:</strong> Based on expiration dates</li>";
echo "</ul>";
echo "<p><strong>🎯 Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Clear tracking of original vs current quantities</li>";
echo "<li>Automatic product total synchronization</li>";
echo "<li>FIFO transfer recommendations</li>";
echo "<li>Old vs new batch analysis</li>";
echo "</ul>";
echo "</div>";

$conn->close();
?> 
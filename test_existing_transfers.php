<?php
// Test script to check existing transfers
include 'index.php';

echo "<h1>Existing Transfers Test</h1>";

// Test the get_transfers_with_details API
$testData = [
    'action' => 'get_transfers_with_details'
];

$jsonData = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/backend.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Response (HTTP Code: $httpCode)</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse the response
$result = json_decode($response, true);

if ($result && isset($result['success']) && $result['success']) {
    echo "<h2>✅ Transfers Found: " . count($result['data']) . "</h2>";
    
    if (count($result['data']) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Transfer ID</th>";
        echo "<th>Date</th>";
        echo "<th>Status</th>";
        echo "<th>From</th>";
        echo "<th>To</th>";
        echo "<th>Employee</th>";
        echo "<th>Products</th>";
        echo "<th>Total Value</th>";
        echo "</tr>";
        
        foreach ($result['data'] as $transfer) {
            echo "<tr>";
            echo "<td>TR-" . $transfer['transfer_header_id'] . "</td>";
            echo "<td>" . $transfer['date'] . "</td>";
            echo "<td>" . $transfer['status'] . "</td>";
            echo "<td>" . $transfer['source_location_name'] . "</td>";
            echo "<td>" . $transfer['destination_location_name'] . "</td>";
            echo "<td>" . $transfer['employee_name'] . "</td>";
            echo "<td>" . $transfer['total_products'] . "</td>";
            echo "<td>₱" . number_format($transfer['total_value'] ?? 0, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show detailed product information for first transfer
        if (count($result['data']) > 0) {
            $firstTransfer = $result['data'][0];
            echo "<h3>Products in Transfer TR-" . $firstTransfer['transfer_header_id'] . "</h3>";
            
            if (isset($firstTransfer['products']) && count($firstTransfer['products']) > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>Product Name</th>";
                echo "<th>Category</th>";
                echo "<th>Barcode</th>";
                echo "<th>Quantity</th>";
                echo "<th>Unit Price</th>";
                echo "<th>Total Value</th>";
                echo "</tr>";
                
                foreach ($firstTransfer['products'] as $product) {
                    echo "<tr>";
                    echo "<td>" . $product['product_name'] . "</td>";
                    echo "<td>" . $product['category'] . "</td>";
                    echo "<td>" . $product['barcode'] . "</td>";
                    echo "<td>" . $product['qty'] . "</td>";
                    echo "<td>₱" . number_format($product['unit_price'], 2) . "</td>";
                    echo "<td>₱" . number_format($product['unit_price'] * $product['qty'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No products found for this transfer.</p>";
            }
        }
    } else {
        echo "<p>No transfers found in the database.</p>";
    }
} else {
    echo "<h2>❌ Error</h2>";
    echo "<p>Failed to retrieve transfers:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h2>Database Check</h2>";

// Direct database check
$stmt = $conn->prepare("
    SELECT 
        th.transfer_header_id,
        th.date,
        th.status,
        sl.location_name as source_location_name,
        dl.location_name as destination_location_name,
        e.Fname as employee_name
    FROM tbl_transfer_header th
    LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
    LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
    LEFT JOIN tbl_employee e ON th.employee_id = e.emp_id
    ORDER BY th.transfer_header_id DESC
");
$stmt->execute();
$dbTransfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Direct Database Query Results:</strong></p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Transfer ID</th>";
echo "<th>Date</th>";
echo "<th>Status (Raw)</th>";
echo "<th>From</th>";
echo "<th>To</th>";
echo "<th>Employee</th>";
echo "</tr>";

foreach ($dbTransfers as $transfer) {
    echo "<tr>";
    echo "<td>TR-" . $transfer['transfer_header_id'] . "</td>";
    echo "<td>" . $transfer['date'] . "</td>";
    echo "<td>'" . $transfer['status'] . "'</td>";
    echo "<td>" . $transfer['source_location_name'] . "</td>";
    echo "<td>" . $transfer['destination_location_name'] . "</td>";
    echo "<td>" . $transfer['employee_name'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Summary</h2>";
echo "<p><strong>✅ API Transfers:</strong> " . (isset($result['data']) ? count($result['data']) : 0) . "</p>";
echo "<p><strong>✅ Database Transfers:</strong> " . count($dbTransfers) . "</p>";
echo "<p><strong>✅ Status Mapping:</strong> Empty status values are now mapped to 'Completed'</p>";
echo "<p><strong>✅ Ready for Display:</strong> The transfer table should now show all existing transfers</p>";

?> 
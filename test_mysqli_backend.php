<?php
// Test the mysqli backend
echo "<h1>Testing MySQLi Backend</h1>";

$testData = [
    'action' => 'get_transfers_with_details'
];

$jsonData = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
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

// Parse and display the response
$result = json_decode($response, true);
if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "<h2>✅ API call successful!</h2>";
        echo "<p>Number of transfers returned: " . count($result['data']) . "</p>";
        
        if (count($result['data']) > 0) {
            echo "<h3>Sample Transfer Data:</h3>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Transfer ID</th><th>Date</th><th>From</th><th>To</th><th>Status</th><th>Employee</th><th>Products</th><th>Total Value</th></tr>";
            
            foreach (array_slice($result['data'], 0, 5) as $transfer) {
                echo "<tr>";
                echo "<td>" . $transfer['transfer_header_id'] . "</td>";
                echo "<td>" . $transfer['date'] . "</td>";
                echo "<td>" . $transfer['source_location_name'] . "</td>";
                echo "<td>" . $transfer['destination_location_name'] . "</td>";
                echo "<td>" . $transfer['status'] . "</td>";
                echo "<td>" . $transfer['employee_name'] . "</td>";
                echo "<td>" . $transfer['total_products'] . "</td>";
                echo "<td>₱" . number_format($transfer['total_value'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show products for first transfer
            if (isset($result['data'][0]['products']) && count($result['data'][0]['products']) > 0) {
                echo "<h3>Products in Transfer #" . $result['data'][0]['transfer_header_id'] . ":</h3>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Product Name</th><th>Category</th><th>Quantity</th><th>Unit Price</th><th>Brand</th></tr>";
                
                foreach ($result['data'][0]['products'] as $product) {
                    echo "<tr>";
                    echo "<td>" . $product['product_name'] . "</td>";
                    echo "<td>" . $product['category'] . "</td>";
                    echo "<td>" . $product['qty'] . "</td>";
                    echo "<td>₱" . number_format($product['unit_price'], 2) . "</td>";
                    echo "<td>" . ($product['brand'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    } else {
        echo "<h2>❌ API call failed</h2>";
        echo "<p>Error: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<h2>❌ Invalid API response format</h2>";
}
?> 
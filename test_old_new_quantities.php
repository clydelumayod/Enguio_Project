<?php
// Test the updated get_products API for old quantity only
echo "<h1>Testing Updated get_products API (Old Quantity Only)</h1>";

// Test data
$data = json_encode([
    'action' => 'get_products',
    'location_id' => 2, // Warehouse
    'for_transfer' => true
]);

// Make API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

echo "<h2>API Response:</h2>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<h2>Products with Old Quantity and Total Quantity:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Product Name</th>";
    echo "<th>Batch ID</th>";
    echo "<th>Old Qty (First Batch)</th>";
    echo "<th>Total Qty</th>";
    echo "<th>SRP</th>";
    echo "</tr>";
    
    foreach ($result['data'] as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['batch_id']) . "</td>";
        echo "<td style='color: orange; font-weight: bold;'>" . htmlspecialchars($product['old_quantity']) . "</td>";
        echo "<td style='color: green; font-weight: bold;'>" . htmlspecialchars($product['total_quantity']) . "</td>";
        echo "<td>₱" . number_format($product['srp'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $result['message'] . "</p>";
}
?> 
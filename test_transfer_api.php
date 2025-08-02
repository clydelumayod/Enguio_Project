<?php
// Test the transfer products API
$url = 'http://localhost/Enguio_Project/Api/backend.php';

$data = [
    'action' => 'get_products',
    'location_id' => 2,
    'for_transfer' => true
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error: Could not connect to API";
} else {
    $response = json_decode($result, true);
    
    echo "<h2>Transfer Products API Test Results</h2>";
    
    if ($response['success']) {
        echo "<p>✅ API call successful</p>";
        echo "<p>Total products available for transfer: " . count($response['data']) . "</p>";
        
        if (count($response['data']) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Product Name</th><th>Category</th><th>Brand</th><th>Available Qty</th><th>Batch ID</th><th>Entry Date</th></tr>";
            
            foreach ($response['data'] as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                echo "<td>" . htmlspecialchars($product['category']) . "</td>";
                echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
                echo "<td>" . $product['quantity'] . "</td>";
                echo "<td>" . $product['batch_id'] . "</td>";
                echo "<td>" . $product['entry_date'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No products found for transfer</p>";
        }
    } else {
        echo "<p>❌ API call failed: " . $response['message'] . "</p>";
    }
    
    echo "<h3>Raw API Response:</h3>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
}
?> 
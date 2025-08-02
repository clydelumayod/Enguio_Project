<?php
// Test to compare regular products vs transfer products
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// Test 1: Regular products (all products)
$data1 = [
    'action' => 'get_products',
    'location_id' => 2
];

// Test 2: Transfer products (only newest batches)
$data2 = [
    'action' => 'get_products',
    'location_id' => 2,
    'for_transfer' => true
];

function callAPI($data) {
    global $url;
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
        return ['success' => false, 'message' => 'Could not connect to API'];
    }
    
    return json_decode($result, true);
}

// Call both APIs
$regularProducts = callAPI($data1);
$transferProducts = callAPI($data2);

echo "<h2>Product Comparison Test</h2>";

// Display regular products
echo "<h3>Regular Products (All Products)</h3>";
if ($regularProducts['success']) {
    echo "<p>Total regular products: " . count($regularProducts['data']) . "</p>";
    
    if (count($regularProducts['data']) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr><th>Product Name</th><th>Category</th><th>Brand</th><th>Total Qty</th><th>Batch ID</th><th>Entry Date</th></tr>";
        
        foreach (array_slice($regularProducts['data'], 0, 10) as $product) { // Show first 10
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
    }
} else {
    echo "<p>❌ Regular products API failed: " . $regularProducts['message'] . "</p>";
}

// Display transfer products
echo "<h3>Transfer Products (Newest Batches Only)</h3>";
if ($transferProducts['success']) {
    echo "<p>Total transfer products: " . count($transferProducts['data']) . "</p>";
    
    if (count($transferProducts['data']) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr><th>Product Name</th><th>Category</th><th>Brand</th><th>Available Qty</th><th>Batch ID</th><th>Entry Date</th></tr>";
        
        foreach (array_slice($transferProducts['data'], 0, 10) as $product) { // Show first 10
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
    }
} else {
    echo "<p>❌ Transfer products API failed: " . $transferProducts['message'] . "</p>";
}

// Show summary
echo "<h3>Summary</h3>";
if ($regularProducts['success'] && $transferProducts['success']) {
    $regularCount = count($regularProducts['data']);
    $transferCount = count($transferProducts['data']);
    
    echo "<p><strong>Regular Products:</strong> $regularCount</p>";
    echo "<p><strong>Transfer Products:</strong> $transferCount</p>";
    echo "<p><strong>Difference:</strong> " . ($regularCount - $transferCount) . " products filtered out</p>";
    
    if ($transferCount < $regularCount) {
        echo "<p style='color: green;'>✅ Success! Transfer products are showing fewer products (only newest batches)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Transfer products showing same or more products - may need adjustment</p>";
    }
}
?> 
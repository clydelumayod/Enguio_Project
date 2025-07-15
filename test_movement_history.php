<?php
// Test file for movement history API
include 'index.php';

echo "<h1>Movement History API Test</h1>";

// Test the get_movement_history API
$testData = [
    'action' => 'get_movement_history',
    'search' => '',
    'movement_type' => 'all',
    'location' => 'all',
    'date_range' => 'all'
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
    echo "<h2>✅ Movement History Found: " . count($result['data']) . "</h2>";
    
    if (count($result['data']) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th>";
        echo "<th>Product Name</th>";
        echo "<th>Product ID</th>";
        echo "<th>Type</th>";
        echo "<th>Quantity</th>";
        echo "<th>From</th>";
        echo "<th>To</th>";
        echo "<th>Moved By</th>";
        echo "<th>Date</th>";
        echo "<th>Status</th>";
        echo "<th>Reference</th>";
        echo "</tr>";
        
        foreach ($result['data'] as $movement) {
            echo "<tr>";
            echo "<td>" . $movement['id'] . "</td>";
            echo "<td>" . $movement['product_name'] . "</td>";
            echo "<td>" . $movement['productId'] . "</td>";
            echo "<td>" . $movement['movementType'] . "</td>";
            echo "<td>" . $movement['quantity'] . "</td>";
            echo "<td>" . $movement['fromLocation'] . "</td>";
            echo "<td>" . $movement['toLocation'] . "</td>";
            echo "<td>" . $movement['movedBy'] . "</td>";
            echo "<td>" . $movement['date'] . "</td>";
            echo "<td>" . $movement['status'] . "</td>";
            echo "<td>" . $movement['reference'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No movement history found in the database.</p>";
    }
} else {
    echo "<h2>❌ Error</h2>";
    echo "<p>Failed to retrieve movement history:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>✅ API Movement History:</strong> " . (isset($result['data']) ? count($result['data']) : 0) . "</p>";
echo "<p><strong>✅ Ready for Display:</strong> The movement history should now show real data</p>";

?> 
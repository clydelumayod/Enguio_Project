<?php
// Simple API test
$url = "http://localhost/Enguio_Project/Api/backend.php";

// Test data
$data = [
    'action' => 'get_warehouse_kpis',
    'product' => 'All',
    'location' => 'All'
];

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Test Results</h2>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Raw Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Try to decode JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "<p style='color: green;'>✓ Valid JSON response</p>";
    echo "<p><strong>Decoded Data:</strong></p>";
    echo "<ul>";
    foreach ($decoded as $key => $value) {
        echo "<li><strong>" . $key . ":</strong> " . $value . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Invalid JSON response</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
}
?> 
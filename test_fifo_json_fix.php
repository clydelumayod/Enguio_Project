<?php
// Test script to verify FIFO availability API returns valid JSON
$url = 'http://localhost/Enguio_Project/Api/backend_mysqli.php';

// Test data for Century Tuna product
$testData = [
    'action' => 'check_fifo_availability',
    'product_id' => 82, // Century Tuna product ID
    'location_id' => 2, // Warehouse location ID
    'requested_quantity' => 150
];

// Make the API call using file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData)
    ]
]);

$response = file_get_contents($url, false, $context);

echo "=== FIFO Availability Test ===\n";
echo "Raw Response Length: " . strlen($response) . " bytes\n";
echo "Raw Response:\n";
echo $response . "\n\n";

// Try to decode JSON
$decoded = json_decode($response, true);
if ($decoded === null) {
    echo "❌ JSON Decode Error: " . json_last_error_msg() . "\n";
    echo "JSON Error Code: " . json_last_error() . "\n";
} else {
    echo "✅ JSON Decoded Successfully\n";
    echo "Response Structure:\n";
    print_r($decoded);
}

// Test with a different product
echo "\n=== Testing with Product ID 91 ===\n";
$testData2 = [
    'action' => 'check_fifo_availability',
    'product_id' => 91, // Different product
    'location_id' => 2,
    'requested_quantity' => 10
];

$context2 = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData2)
    ]
]);

$response2 = file_get_contents($url, false, $context2);

echo "Response for Product 91:\n";
echo $response2 . "\n\n";

$decoded2 = json_decode($response2, true);
if ($decoded2 === null) {
    echo "❌ JSON Decode Error for Product 91: " . json_last_error_msg() . "\n";
} else {
    echo "✅ JSON Decoded Successfully for Product 91\n";
    echo "Response Structure:\n";
    print_r($decoded2);
}
?> 
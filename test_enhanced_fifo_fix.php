<?php
// Test script to verify enhanced FIFO transfer API returns valid JSON
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// Test data for enhanced FIFO transfer
$testData = [
    'action' => 'enhanced_fifo_transfer',
    'source_location_id' => 2, // Warehouse
    'destination_location_id' => 3, // Convenience Store
    'employee_id' => 1,
    'delivery_date' => '2025-07-15',
    'products' => [
        [
            'product_id' => 3, // Century Tuna Flakes in Oil
            'quantity' => 10
        ]
    ]
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

echo "=== Enhanced FIFO Transfer Test ===\n";
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

// Test check_fifo_availability
echo "\n=== Testing check_fifo_availability ===\n";
$testData2 = [
    'action' => 'check_fifo_availability',
    'product_id' => 82,
    'location_id' => 2,
    'requested_quantity' => 150
];

$context2 = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData2)
    ]
]);

$response2 = file_get_contents($url, false, $context2);

echo "FIFO Availability Response:\n";
echo $response2 . "\n\n";

$decoded2 = json_decode($response2, true);
if ($decoded2 === null) {
    echo "❌ JSON Decode Error for FIFO availability: " . json_last_error_msg() . "\n";
} else {
    echo "✅ JSON Decoded Successfully for FIFO availability\n";
    print_r($decoded2);
}
?> 
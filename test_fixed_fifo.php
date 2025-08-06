<?php
// Test script to verify fixed enhanced FIFO transfer
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// Test data for enhanced FIFO transfer
$testData = [
    'action' => 'enhanced_fifo_transfer',
    'source_location_id' => 2, // Warehouse
    'destination_location_id' => 3, // Convenience Store
    'employee_id' => 19,
    'products' => [
        [
            'product_id' => 82, // Century Tuna
            'quantity' => 5
        ]
    ]
];

echo "=== Testing Fixed Enhanced FIFO Transfer ===\n";
echo "URL: $url\n";
echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Make the API call using file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData),
        'timeout' => 30 // 30 second timeout
    ]
]);

echo "Making API request...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to connect to API\n";
    echo "Make sure XAMPP is running and Apache is started\n";
    
    // Check if we can access the file directly
    echo "\nChecking if API file exists...\n";
    if (file_exists('Api/backend.php')) {
        echo "✅ Api/backend.php exists\n";
    } else {
        echo "❌ Api/backend.php not found\n";
    }
} else {
    echo "✅ API connection successful\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "Raw Response:\n";
    echo $response . "\n\n";
    
    // Try to decode JSON
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo "❌ JSON Decode Error: " . json_last_error_msg() . "\n";
        echo "JSON Error Code: " . json_last_error() . "\n";
        
        // Show the first 500 characters of the response to debug
        echo "Response preview (first 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
    } else {
        echo "✅ JSON Decoded Successfully\n";
        echo "Response Structure:\n";
        print_r($decoded);
        
        if (isset($decoded['success'])) {
            if ($decoded['success']) {
                echo "\n🎉 Enhanced FIFO Transfer Successful!\n";
                echo "Transfer ID: " . ($decoded['transfer_id'] ?? 'N/A') . "\n";
                echo "Products Transferred: " . ($decoded['products_transferred'] ?? 'N/A') . "\n";
                echo "Message: " . ($decoded['message'] ?? 'N/A') . "\n";
            } else {
                echo "\n❌ Enhanced FIFO Transfer Failed:\n";
                echo "Error: " . ($decoded['message'] ?? 'Unknown error') . "\n";
            }
        }
    }
}

echo "\n=== Test Complete ===\n";
?> 
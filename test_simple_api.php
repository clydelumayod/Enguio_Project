<?php
// Simple test to check if the API is working
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// Test with a simple action
$testData = [
    'action' => 'get_locations'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData)
    ]
]);

echo "Testing API connection...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to connect to API\n";
    echo "Make sure XAMPP is running and Apache is started\n";
} else {
    echo "✅ API connection successful\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "Response preview: " . substr($response, 0, 200) . "...\n";
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo "❌ JSON Decode Error: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ JSON decoded successfully\n";
        if (isset($decoded['success'])) {
            echo "API Success: " . ($decoded['success'] ? 'Yes' : 'No') . "\n";
        }
    }
}
?> 
<?php
// Test script to verify transfer system works after barcode constraint removal
include 'Api/index.php';

echo "Testing Transfer System After Barcode Constraint Removal\n";
echo "=====================================================\n\n";

// Test data
$testData = [
    'action' => 'create_transfer',
    'source_location_id' => 1, // Warehouse
    'destination_location_id' => 2, // Convenience Store
    'employee_id' => 1,
    'status' => 'Completed',
    'products' => [
        [
            'product_id' => 1,
            'quantity' => 5
        ]
    ]
];

echo "Sending transfer request...\n";
echo "Source Location: " . $testData['source_location_id'] . "\n";
echo "Destination Location: " . $testData['destination_location_id'] . "\n";
echo "Products: " . json_encode($testData['products']) . "\n\n";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n\n";

// Parse response
$result = json_decode($response, true);

if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "✅ Transfer test PASSED!\n";
        echo "Transfer ID: " . ($result['transfer_id'] ?? 'N/A') . "\n";
        echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Transfer test FAILED!\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Invalid response format\n";
    echo "Raw response: $response\n";
}

echo "\nTest completed.\n";
?> 
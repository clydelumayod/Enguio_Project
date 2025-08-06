<?php
// Test script with valid employee ID
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// First, let's get the available staff
$staffData = [
    'action' => 'get_inventory_staff'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($staffData)
    ]
]);

echo "=== Getting Available Staff ===\n";
$staffResponse = file_get_contents($url, false, $context);

if ($staffResponse !== false) {
    $staffDecoded = json_decode($staffResponse, true);
    if ($staffDecoded && isset($staffDecoded['data']) && is_array($staffDecoded['data'])) {
        echo "Available staff:\n";
        foreach ($staffDecoded['data'] as $staff) {
            echo "ID: " . $staff['emp_id'] . " | Name: " . $staff['name'] . "\n";
        }
        
        // Use the first available employee ID
        $validEmployeeId = $staffDecoded['data'][0]['emp_id'] ?? 1;
        echo "\nUsing employee ID: $validEmployeeId\n";
        
        // Test enhanced FIFO transfer with valid employee ID
        $testData = [
            'action' => 'enhanced_fifo_transfer',
            'source_location_id' => 2, // Warehouse
            'destination_location_id' => 3, // Convenience Store
            'employee_id' => $validEmployeeId,
            'products' => [
                [
                    'product_id' => 82, // Century Tuna
                    'quantity' => 5
                ]
            ]
        ];
        
        echo "\n=== Testing Enhanced FIFO Transfer with Valid Employee ===\n";
        echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
        
        $context2 = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($testData)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context2);
        
        if ($response !== false) {
            echo "✅ API connection successful\n";
            echo "Response length: " . strlen($response) . " bytes\n";
            echo "Raw Response:\n";
            echo $response . "\n\n";
            
            $decoded = json_decode($response, true);
            if ($decoded === null) {
                echo "❌ JSON Decode Error: " . json_last_error_msg() . "\n";
            } else {
                echo "✅ JSON Decoded Successfully\n";
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
        } else {
            echo "❌ Failed to connect to API\n";
        }
    } else {
        echo "❌ Failed to get staff data\n";
        echo "Response: $staffResponse\n";
    }
} else {
    echo "❌ Failed to connect to API for staff data\n";
}

echo "\n=== Test Complete ===\n";
?> 
<?php
// Check available products in the database
$url = 'http://localhost/Enguio_Project/Api/backend.php';

// Get products from warehouse (location_id = 2)
$productData = [
    'action' => 'get_products_oldest_batch_for_transfer',
    'location_id' => 2
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($productData)
    ]
]);

echo "=== Checking Available Products in Warehouse ===\n";
$response = file_get_contents($url, false, $context);

if ($response !== false) {
    $decoded = json_decode($response, true);
    if ($decoded && isset($decoded['data']) && is_array($decoded['data'])) {
        echo "Available products in warehouse:\n";
        foreach ($decoded['data'] as $product) {
            echo "ID: " . $product['product_id'] . " | Name: " . $product['product_name'] . " | Qty: " . ($product['total_quantity'] ?? $product['quantity'] ?? 0) . "\n";
        }
        
        // Use the first available product for testing
        if (count($decoded['data']) > 0) {
            $testProduct = $decoded['data'][0];
            $validProductId = $testProduct['product_id'];
            echo "\nUsing product ID: $validProductId (" . $testProduct['product_name'] . ")\n";
            
            // Test enhanced FIFO transfer with valid product ID
            $testData = [
                'action' => 'enhanced_fifo_transfer',
                'source_location_id' => 2, // Warehouse
                'destination_location_id' => 3, // Convenience Store
                'employee_id' => 1, // Valid employee ID
                'products' => [
                    [
                        'product_id' => $validProductId,
                        'quantity' => 1 // Small quantity for testing
                    ]
                ]
            ];
            
            echo "\n=== Testing Enhanced FIFO Transfer with Valid Product ===\n";
            echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
            
            $context2 = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($testData)
                ]
            ]);
            
            $transferResponse = file_get_contents($url, false, $context2);
            
            if ($transferResponse !== false) {
                echo "✅ API connection successful\n";
                echo "Response length: " . strlen($transferResponse) . " bytes\n";
                echo "Raw Response:\n";
                echo $transferResponse . "\n\n";
                
                $transferDecoded = json_decode($transferResponse, true);
                if ($transferDecoded === null) {
                    echo "❌ JSON Decode Error: " . json_last_error_msg() . "\n";
                } else {
                    echo "✅ JSON Decoded Successfully\n";
                    print_r($transferDecoded);
                    
                    if (isset($transferDecoded['success'])) {
                        if ($transferDecoded['success']) {
                            echo "\n🎉 Enhanced FIFO Transfer Successful!\n";
                            echo "Transfer ID: " . ($transferDecoded['transfer_id'] ?? 'N/A') . "\n";
                            echo "Products Transferred: " . ($transferDecoded['products_transferred'] ?? 'N/A') . "\n";
                            echo "Message: " . ($transferDecoded['message'] ?? 'N/A') . "\n";
                        } else {
                            echo "\n❌ Enhanced FIFO Transfer Failed:\n";
                            echo "Error: " . ($transferDecoded['message'] ?? 'Unknown error') . "\n";
                        }
                    }
                }
            } else {
                echo "❌ Failed to connect to API for transfer\n";
            }
        } else {
            echo "No products found in warehouse\n";
        }
    } else {
        echo "❌ Failed to get products data\n";
        echo "Response: $response\n";
    }
} else {
    echo "❌ Failed to connect to API for products\n";
}

echo "\n=== Test Complete ===\n";
?> 
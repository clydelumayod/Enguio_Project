<?php
// Test script to verify transfer flow from Warehouse to Convenience Store
include 'Api/index.php';

echo "Testing Transfer Flow: Warehouse -> Convenience Store\n";
echo "==================================================\n\n";

// First, let's check what locations are available
echo "1. Checking available locations...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'get_locations']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$locations = json_decode($response, true);
if ($locations && $locations['success']) {
    echo "Available locations:\n";
    foreach ($locations['data'] as $location) {
        echo "- ID: {$location['location_id']}, Name: {$location['location_name']}\n";
    }
    echo "\n";
} else {
    echo "Failed to get locations\n";
    exit;
}

// Find Warehouse and Convenience Store
$warehouse = null;
$convenience = null;
foreach ($locations['data'] as $location) {
    if (stripos($location['location_name'], 'warehouse') !== false) {
        $warehouse = $location;
    }
    if (stripos($location['location_name'], 'convenience') !== false) {
        $convenience = $location;
    }
}

if (!$warehouse || !$convenience) {
    echo "Error: Could not find Warehouse or Convenience Store locations\n";
    exit;
}

echo "2. Found locations:\n";
echo "- Source (Warehouse): {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
echo "- Destination (Convenience): {$convenience['location_name']} (ID: {$convenience['location_id']})\n\n";

// Check products in Warehouse
echo "3. Checking products in Warehouse...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'action' => 'get_products',
    'location_id' => $warehouse['location_id']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$products = json_decode($response, true);
if ($products && $products['success']) {
    echo "Products in Warehouse: " . count($products['data']) . "\n";
    if (count($products['data']) > 0) {
        echo "Sample products:\n";
        foreach (array_slice($products['data'], 0, 3) as $product) {
            echo "- {$product['product_name']} (Qty: {$product['quantity']}, Barcode: {$product['barcode']})\n";
        }
    }
} else {
    echo "No products found in Warehouse\n";
    exit;
}

// Test transfer
echo "\n4. Testing transfer from Warehouse to Convenience Store...\n";
$testData = [
    'action' => 'create_transfer',
    'source_location_id' => $warehouse['location_id'],
    'destination_location_id' => $convenience['location_id'],
    'employee_id' => 1,
    'status' => 'Completed',
    'products' => [
        [
            'product_id' => $products['data'][0]['product_id'],
            'quantity' => 2
        ]
    ]
];

echo "Transfer details:\n";
echo "- From: {$warehouse['location_name']}\n";
echo "- To: {$convenience['location_name']}\n";
echo "- Product: {$products['data'][0]['product_name']}\n";
echo "- Quantity: 2\n\n";

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

$result = json_decode($response, true);

if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "✅ Transfer test PASSED!\n";
        echo "Transfer ID: " . ($result['transfer_id'] ?? 'N/A') . "\n";
        echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
        
        // Verify the transfer worked by checking product quantities
        echo "\n5. Verifying transfer results...\n";
        
        // Check source location (should have reduced quantity)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'action' => 'get_products',
            'location_id' => $warehouse['location_id']
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $sourceProducts = json_decode($response, true);
        $transferredProduct = null;
        foreach ($sourceProducts['data'] as $product) {
            if ($product['product_id'] == $products['data'][0]['product_id']) {
                $transferredProduct = $product;
                break;
            }
        }
        
        if ($transferredProduct) {
            echo "Source location quantity: {$transferredProduct['quantity']}\n";
        }
        
        // Check destination location (should have the product now)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'action' => 'get_products',
            'location_id' => $convenience['location_id']
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $destProducts = json_decode($response, true);
        echo "Products in destination: " . count($destProducts['data']) . "\n";
        
        $foundInDest = false;
        foreach ($destProducts['data'] as $product) {
            if ($product['barcode'] == $products['data'][0]['barcode']) {
                echo "✅ Product found in destination with quantity: {$product['quantity']}\n";
                $foundInDest = true;
                break;
            }
        }
        
        if (!$foundInDest) {
            echo "❌ Product not found in destination\n";
        }
        
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
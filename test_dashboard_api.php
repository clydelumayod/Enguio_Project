<?php
// Test script to check if the Dashboard API endpoints are working

echo "Testing Dashboard API endpoints...\n\n";

$api_url = "http://localhost/Enguio_Project/Api/backend.php";

// Test get_categories
echo "1. Testing get_categories...\n";
$data = json_encode(['action' => 'get_categories']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 200) . "...\n";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ JSON is valid\n";
    if (isset($decoded['success']) && $decoded['success']) {
        echo "✓ API call successful\n";
        echo "Categories found: " . count($decoded['data']) . "\n";
    } else {
        echo "✗ API call failed: " . ($decoded['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ JSON parsing error: " . json_last_error_msg() . "\n";
    echo "Raw response: $response\n";
}

echo "\n";

// Test get_locations
echo "2. Testing get_locations...\n";
$data = json_encode(['action' => 'get_locations']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 200) . "...\n";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ JSON is valid\n";
    if (isset($decoded['success']) && $decoded['success']) {
        echo "✓ API call successful\n";
        echo "Locations found: " . count($decoded['data']) . "\n";
    } else {
        echo "✗ API call failed: " . ($decoded['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ JSON parsing error: " . json_last_error_msg() . "\n";
    echo "Raw response: $response\n";
}

echo "\n";

// Test get_warehouse_kpis
echo "3. Testing get_warehouse_kpis...\n";
$data = json_encode(['action' => 'get_warehouse_kpis']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 200) . "...\n";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ JSON is valid\n";
    if (isset($decoded['totalProducts'])) {
        echo "✓ API call successful\n";
        echo "Total Products: " . $decoded['totalProducts'] . "\n";
    } else {
        echo "✗ API call failed: " . ($decoded['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ JSON parsing error: " . json_last_error_msg() . "\n";
    echo "Raw response: $response\n";
}

echo "\nTest completed.\n";
?> 
<?php
// Test the get_products API
$url = 'http://localhost/Enguio_Project/Api/backend.php';
$data = json_encode(['action' => 'get_products']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo "API Response:\n";
echo $response . "\n\n";

// Decode and check for Mang Juan
$decoded = json_decode($response, true);
if (isset($decoded['data']) && is_array($decoded['data'])) {
    $mangJuan = array_filter($decoded['data'], function($product) {
        return strpos($product['product_name'], 'Mang Juan') !== false;
    });
    
    echo "Mang Juan products found: " . count($mangJuan) . "\n";
    if (count($mangJuan) > 0) {
        echo json_encode($mangJuan, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\nTotal products returned: " . count($decoded['data']) . "\n";
}
?> 
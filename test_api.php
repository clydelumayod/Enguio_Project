<?php
// Simple test to check backend response
$url = 'http://localhost/Enguio_Project/backend.php';
$data = json_encode(['action' => 'get_suppliers']);

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Raw response:\n";
echo $result;
echo "\n\nResponse length: " . strlen($result);
echo "\nFirst 100 characters: " . substr($result, 0, 100);
?> 
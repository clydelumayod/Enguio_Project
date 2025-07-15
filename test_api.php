<?php
include 'Api/index.php';
// Simple test to check backend response
$url = 'http://localhost/Enguio_Project/backend.php';
$data = json_encode(['action' => 'get_suppliers']);

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    die('Error occurred while calling API');
}

echo "API Response:\n";
echo $result;
?> 
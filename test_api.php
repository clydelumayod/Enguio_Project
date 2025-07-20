<?php
// Simple test to verify API is working
$url = 'http://localhost/Enguio_Project/backend.php';

$data = [
    'action' => 'get_movement_history',
    'search' => '',
    'movement_type' => 'all',
    'location' => 'all',
    'date_range' => 'all'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response:\n";
echo $result;
echo "\n\n";

// Try to decode as JSON
$decoded = json_decode($result, true);
if ($decoded) {
    echo "JSON decoded successfully\n";
    echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    if (isset($decoded['data'])) {
        echo "Data count: " . count($decoded['data']) . "\n";
    }
} else {
    echo "Failed to decode JSON\n";
    echo "JSON error: " . json_last_error_msg() . "\n";
}
?> 
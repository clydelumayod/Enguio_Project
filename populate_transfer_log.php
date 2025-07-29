<?php
// Script to populate transfer log with existing transfer data
echo "<h1>Populate Transfer Log</h1>";

$testData = [
    'action' => 'populate_transfer_log'
];

$jsonData = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Response (HTTP Code: $httpCode)</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse and display the response
$result = json_decode($response, true);
if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "<h2>✅ Transfer log populated successfully!</h2>";
        echo "<p>" . $result['message'] . "</p>";
        echo "<p>Records inserted: " . $result['records_inserted'] . "</p>";
        
        // Now show the transfer log data
        echo "<h3>Transfer Log Data:</h3>";
        
        $logData = [
            'action' => 'get_transfer_log'
        ];
        
        $logJsonData = json_encode($logData);
        
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $logJsonData);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($logJsonData)
        ]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        
        $logResponse = curl_exec($ch2);
        curl_close($ch2);
        
        $logResult = json_decode($logResponse, true);
        
        if ($logResult && isset($logResult['success']) && $logResult['success']) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Transfer ID</th><th>Product ID</th><th>Product Name</th><th>From</th><th>To</th><th>Quantity</th><th>Transfer Date</th><th>Created At</th></tr>";
            
            foreach (array_slice($logResult['data'], 0, 10) as $log) {
                echo "<tr>";
                echo "<td>" . $log['transfer_id'] . "</td>";
                echo "<td>" . $log['product_id'] . "</td>";
                echo "<td>" . $log['product_name'] . "</td>";
                echo "<td>" . $log['from_location'] . "</td>";
                echo "<td>" . $log['to_location'] . "</td>";
                echo "<td>" . $log['quantity'] . "</td>";
                echo "<td>" . $log['transfer_date'] . "</td>";
                echo "<td>" . $log['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if (count($logResult['data']) > 10) {
                echo "<p>Showing first 10 records. Total records: " . count($logResult['data']) . "</p>";
            }
        }
        
    } else {
        echo "<h2>❌ Failed to populate transfer log</h2>";
        echo "<p>Error: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<h2>❌ Invalid API response format</h2>";
}
?> 
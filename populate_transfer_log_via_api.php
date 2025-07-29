<?php
// Transfer log population via existing backend API
echo "=== Transfer Log Population via API ===\n";

// Function to make API calls
function callAPI($action, $data = []) {
    $url = "http://localhost/Enguio_Project/Api/backend_mysqli.php";
    $payload = json_encode(['action' => $action, ...$data]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return ['success' => false, 'message' => 'cURL error'];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => "HTTP error: $httpCode"];
    }
    
    $decoded = json_decode($response, true);
    return $decoded ?: ['success' => false, 'message' => 'Invalid JSON response'];
}

// Check current transfer log
echo "📊 Checking current transfer log...\n";
$currentLogs = callAPI('get_transfer_log');
if ($currentLogs['success']) {
    echo "Current transfer log entries: " . count($currentLogs['data']) . "\n";
} else {
    echo "❌ Failed to get current transfer logs: " . $currentLogs['message'] . "\n";
}

// Get existing transfers
echo "📋 Getting existing transfers...\n";
$transfers = callAPI('get_transfers_with_details');
if (!$transfers['success']) {
    echo "❌ Failed to get transfers: " . $transfers['message'] . "\n";
    exit;
}

echo "Found " . count($transfers['data']) . " transfers\n\n";

// Populate transfer log
echo "🔄 Populating transfer log...\n";
$populateResult = callAPI('populate_transfer_log');
if ($populateResult['success']) {
    echo "✅ Transfer log populated successfully!\n";
    echo "Message: " . ($populateResult['message'] ?? 'No message') . "\n";
} else {
    echo "❌ Failed to populate transfer log: " . $populateResult['message'] . "\n";
}

// Check final transfer log
echo "\n📊 Checking final transfer log...\n";
$finalLogs = callAPI('get_transfer_log');
if ($finalLogs['success']) {
    echo "Final transfer log entries: " . count($finalLogs['data']) . "\n";
    
    if (count($finalLogs['data']) > 0) {
        echo "\n=== Sample Transfer Log Entries ===\n";
        $sampleCount = min(3, count($finalLogs['data']));
        for ($i = 0; $i < $sampleCount; $i++) {
            $log = $finalLogs['data'][$i];
            echo "TR-" . $log['transfer_id'] . " | " . 
                 $log['product_name'] . " | " . 
                 $log['from_location'] . " → " . $log['to_location'] . " | " . 
                 $log['quantity'] . " units | " . 
                 $log['transfer_date'] . "\n";
        }
    }
} else {
    echo "❌ Failed to get final transfer logs: " . $finalLogs['message'] . "\n";
}

echo "\n=== Population Complete ===\n";
?> 
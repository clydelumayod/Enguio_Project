<?php
// Test script to check transfer data in database
require_once 'Api/conn.php';

echo "<h1>Transfer Data Test</h1>";

try {
    // Test database connection
    echo "<h2>1. Database Connection Test</h2>";
    if ($conn) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }

    // Check if transfer tables exist
    echo "<h2>2. Table Structure Check</h2>";
    $tables = ['tbl_transfer_header', 'tbl_transfer_dtl', 'tbl_product', 'tbl_location', 'tbl_employee'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    }

    // Check transfer header data
    echo "<h2>3. Transfer Header Data</h2>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_transfer_header");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total transfers in header: " . $result['count'] . "<br>";

    if ($result['count'] > 0) {
        $stmt = $conn->prepare("SELECT * FROM tbl_transfer_header ORDER BY transfer_header_id DESC LIMIT 5");
        $stmt->execute();
        $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Source</th><th>Destination</th><th>Employee</th><th>Status</th></tr>";
        foreach ($transfers as $transfer) {
            echo "<tr>";
            echo "<td>" . $transfer['transfer_header_id'] . "</td>";
            echo "<td>" . $transfer['date'] . "</td>";
            echo "<td>" . $transfer['source_location_id'] . "</td>";
            echo "<td>" . $transfer['destination_location_id'] . "</td>";
            echo "<td>" . $transfer['employee_id'] . "</td>";
            echo "<td>" . $transfer['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check transfer detail data
    echo "<h2>4. Transfer Detail Data</h2>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_transfer_dtl");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total transfer details: " . $result['count'] . "<br>";

    if ($result['count'] > 0) {
        $stmt = $conn->prepare("SELECT * FROM tbl_transfer_dtl ORDER BY transfer_header_id DESC LIMIT 10");
        $stmt->execute();
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Transfer ID</th><th>Product ID</th><th>Quantity</th></tr>";
        foreach ($details as $detail) {
            echo "<tr>";
            echo "<td>" . $detail['transfer_header_id'] . "</td>";
            echo "<td>" . $detail['product_id'] . "</td>";
            echo "<td>" . $detail['qty'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test the API endpoint
    echo "<h2>5. API Endpoint Test</h2>";
    $testData = [
        'action' => 'get_transfers_with_details'
    ];
    
    $jsonData = json_encode($testData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
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
    
    echo "API Response (HTTP Code: $httpCode):<br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse and display the response
    $result = json_decode($response, true);
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo "✅ API call successful<br>";
            echo "Number of transfers returned: " . count($result['data']) . "<br>";
            
            if (count($result['data']) > 0) {
                echo "<h3>Sample Transfer Data:</h3>";
                echo "<pre>" . print_r($result['data'][0], true) . "</pre>";
            }
        } else {
            echo "❌ API call failed: " . ($result['message'] ?? 'Unknown error') . "<br>";
        }
    } else {
        echo "❌ Invalid API response format<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 
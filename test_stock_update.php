<?php
// Test script for stock update functionality
echo "<h2>Testing Stock Update Functionality</h2>";

// Test data
$testData = [
    'action' => 'update_product_stock',
    'product_id' => 169, // Corned Beef from your database
    'new_quantity' => 10,
    'batch_reference' => 'TEST-BATCH-001',
    'expiration_date' => '2026-12-31',
    'unit_cost' => 55.00,
    'new_srp' => 65.00,
    'entry_by' => 'admin'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Make API call
$url = 'http://localhost/Enguio_Project/Api/backend_mysqli.php';
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($testData)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h3>API Response:</h3>";
echo "<pre>" . $result . "</pre>";

// Parse response
$response = json_decode($result, true);

if ($response && $response['success']) {
    echo "<h3>✅ Test Successful!</h3>";
    echo "<p>Stock updated successfully:</p>";
    echo "<ul>";
    echo "<li>Old Quantity: " . $response['old_quantity'] . "</li>";
    echo "<li>New Quantity: " . $response['new_quantity'] . "</li>";
    echo "<li>Added Quantity: " . $response['added_quantity'] . "</li>";
    echo "<li>Batch ID: " . $response['batch_id'] . "</li>";
    echo "</ul>";
} else {
    echo "<h3>❌ Test Failed!</h3>";
    echo "<p>Error: " . ($response['message'] ?? 'Unknown error') . "</p>";
}

// Show current product status
echo "<h3>Current Product Status:</h3>";
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT product_id, product_name, quantity, stock_status, srp FROM tbl_product WHERE product_id = 169";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Stock Status</th><th>SRP</th></tr>";
    echo "<tr>";
    echo "<td>" . $product['product_id'] . "</td>";
    echo "<td>" . $product['product_name'] . "</td>";
    echo "<td>" . $product['quantity'] . "</td>";
    echo "<td>" . $product['stock_status'] . "</td>";
    echo "<td>" . $product['srp'] . "</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p>Product not found</p>";
}

$conn->close();
?> 
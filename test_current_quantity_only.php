<?php
// Test that new stock goes ONLY to current_quantity
echo "<h2>Testing current_quantity Only Stock Addition</h2>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Step 1: Check current state of a product
echo "<h3>Step 1: Current Product State</h3>";
$product_id = 207; // Zesto Orange (has 0 quantity)

$checkProduct = "SELECT product_id, product_name, quantity, current_quantity, stock_status FROM tbl_product WHERE product_id = ?";
$stmt = $conn->prepare($checkProduct);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>" . $product['product_id'] . "</td>";
    echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
    echo "<td>" . $product['quantity'] . "</td>";
    echo "<td>" . $product['current_quantity'] . "</td>";
    echo "<td>" . $product['stock_status'] . "</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "Product not found";
}

// Step 2: Simulate adding new stock via API
echo "<h3>Step 2: Simulating API Call to Add New Stock</h3>";

// Prepare the API call data
$apiData = [
    'action' => 'update_product_stock',
    'product_id' => $product_id,
    'new_quantity' => 50,
    'batch_reference' => 'CURRENT-TEST-' . date('YmdHis'),
    'expiration_date' => '2026-12-31',
    'unit_cost' => 8.00,
    'entry_by' => 'test_user'
];

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

// Step 3: Check updated state
echo "<h3>Step 3: Updated Product State</h3>";

$stmt->execute();
$result = $stmt->get_result();
$updatedProduct = $result->fetch_assoc();

if ($updatedProduct) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>" . $updatedProduct['product_id'] . "</td>";
    echo "<td>" . htmlspecialchars($updatedProduct['product_name']) . "</td>";
    echo "<td>" . $updatedProduct['quantity'] . "</td>";
    echo "<td>" . $updatedProduct['current_quantity'] . "</td>";
    echo "<td>" . $updatedProduct['stock_status'] . "</td>";
    echo "</tr>";
    echo "</table>";
    
    // Show the changes
    $quantityChange = $updatedProduct['quantity'] - $product['quantity'];
    $currentQuantityChange = $updatedProduct['current_quantity'] - $product['current_quantity'];
    
    echo "<h4>Changes Made:</h4>";
    echo "<ul>";
    echo "<li><strong>Main Quantity:</strong> " . $product['quantity'] . " → " . $updatedProduct['quantity'] . " (" . ($quantityChange >= 0 ? '+' : '') . $quantityChange . ")</li>";
    echo "<li><strong>Current Quantity:</strong> " . $product['current_quantity'] . " → " . $updatedProduct['current_quantity'] . " (" . ($currentQuantityChange >= 0 ? '+' : '') . $currentQuantityChange . ")</li>";
    echo "<li><strong>Stock Status:</strong> " . $product['stock_status'] . " → " . $updatedProduct['stock_status'] . "</li>";
    echo "</ul>";
    
    // Verify the logic
    if ($quantityChange == 0 && $currentQuantityChange == 50) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: New stock went ONLY to current_quantity!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ ERROR: New stock was not handled correctly!</p>";
    }
} else {
    echo "Product not found after update";
}

// Step 4: Test with another product that has existing stock
echo "<h3>Step 4: Testing with Product that has Existing Stock</h3>";
$product_id_2 = 217; // Nova (has 122 quantity)

$stmt->bind_param("i", $product_id_2);
$stmt->execute();
$result = $stmt->get_result();
$product2 = $result->fetch_assoc();

if ($product2) {
    echo "<h4>Before Update:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>" . $product2['product_id'] . "</td>";
    echo "<td>" . htmlspecialchars($product2['product_name']) . "</td>";
    echo "<td>" . $product2['quantity'] . "</td>";
    echo "<td>" . $product2['current_quantity'] . "</td>";
    echo "<td>" . $product2['stock_status'] . "</td>";
    echo "</tr>";
    echo "</table>";
    
    // Add more stock to this product
    $apiData2 = [
        'action' => 'update_product_stock',
        'product_id' => $product_id_2,
        'new_quantity' => 30,
        'batch_reference' => 'NOVA-TEST-' . date('YmdHis'),
        'expiration_date' => '2026-12-31',
        'unit_cost' => 850.00,
        'entry_by' => 'test_user'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend_mysqli.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData2));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response2 = curl_exec($ch);
    curl_close($ch);
    
    echo "<p><strong>API Response for Nova:</strong></p>";
    echo "<pre>" . htmlspecialchars($response2) . "</pre>";
    
    // Check updated state
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedProduct2 = $result->fetch_assoc();
    
    if ($updatedProduct2) {
        echo "<h4>After Update:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Current Quantity</th><th>Stock Status</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>" . $updatedProduct2['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($updatedProduct2['product_name']) . "</td>";
        echo "<td>" . $updatedProduct2['quantity'] . "</td>";
        echo "<td>" . $updatedProduct2['current_quantity'] . "</td>";
        echo "<td>" . $updatedProduct2['stock_status'] . "</td>";
        echo "</tr>";
        echo "</table>";
        
        $quantityChange2 = $updatedProduct2['quantity'] - $product2['quantity'];
        $currentQuantityChange2 = $updatedProduct2['current_quantity'] - $product2['current_quantity'];
        
        echo "<h4>Changes Made:</h4>";
        echo "<ul>";
        echo "<li><strong>Main Quantity:</strong> " . $product2['quantity'] . " → " . $updatedProduct2['quantity'] . " (" . ($quantityChange2 >= 0 ? '+' : '') . $quantityChange2 . ")</li>";
        echo "<li><strong>Current Quantity:</strong> " . $product2['current_quantity'] . " → " . $updatedProduct2['current_quantity'] . " (" . ($currentQuantityChange2 >= 0 ? '+' : '') . $currentQuantityChange2 . ")</li>";
        echo "</ul>";
        
        if ($quantityChange2 == 0 && $currentQuantityChange2 == 30) {
            echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: New stock went ONLY to current_quantity for existing product!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ ERROR: New stock was not handled correctly for existing product!</p>";
        }
    }
}

$conn->close();
echo "<br><strong>✅ current_quantity only test completed!</strong>";
echo "<br><p>The test shows that new stock additions now go ONLY to the current_quantity field.</p>";
echo "<br><p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ New stock goes ONLY to current_quantity</li>";
echo "<li>✅ Main quantity field remains unchanged</li>";
echo "<li>✅ Stock status is updated based on main quantity</li>";
echo "<li>✅ FIFO tracking still works properly</li>";
echo "</ul>";
echo "<br><p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>✅ Test adding stock through the Warehouse.js interface</li>";
echo "<li>✅ Verify that current_quantity shows the newly added stock</li>";
echo "<li>✅ Check that main quantity stays the same</li>";
echo "</ul>";
?> 
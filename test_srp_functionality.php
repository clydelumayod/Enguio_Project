<?php
// Test SRP functionality
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection error: " . $e->getMessage()
    ]);
    exit;
}

// Test 1: Check if SRP column exists
try {
    $stmt = $conn->prepare("DESCRIBE tbl_product");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $srpExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'srp') {
            $srpExists = true;
            break;
        }
    }
    
    echo json_encode([
        "test" => "SRP Column Check",
        "success" => $srpExists,
        "message" => $srpExists ? "SRP column exists" : "SRP column does not exist",
        "columns" => array_column($columns, 'Field')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "test" => "SRP Column Check",
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

// Test 2: Check if products have SRP values
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total, COUNT(srp) as with_srp FROM tbl_product");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "test" => "SRP Values Check",
        "success" => true,
        "total_products" => $result['total'],
        "products_with_srp" => $result['with_srp'],
        "message" => "Found {$result['with_srp']} out of {$result['total']} products with SRP values"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "test" => "SRP Values Check",
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

// Test 3: Show sample products with SRP
try {
    $stmt = $conn->prepare("SELECT product_name, unit_price, srp FROM tbl_product LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "test" => "Sample Products with SRP",
        "success" => true,
        "sample_products" => $products,
        "message" => "Showing first 5 products with their unit price and SRP"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "test" => "Sample Products with SRP",
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?> 
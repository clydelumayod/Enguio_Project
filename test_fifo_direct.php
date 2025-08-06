<?php
// Direct test of FIFO availability logic
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Test parameters
$product_id = 82; // Century Tuna
$location_id = 2; // Warehouse
$requested_quantity = 150;

echo "=== Direct FIFO Availability Test ===\n";
echo "Product ID: $product_id\n";
echo "Location ID: $location_id\n";
echo "Requested Quantity: $requested_quantity\n\n";

try {
    // First, let's check if the product exists and has stock
    $checkProductStmt = $conn->prepare("
        SELECT p.product_id, p.product_name, p.quantity, p.location_id
        FROM tbl_product p
        WHERE p.product_id = ?
    ");
    $checkProductStmt->bind_param("i", $product_id);
    $checkProductStmt->execute();
    $productResult = $checkProductStmt->get_result();
    $productData = $productResult->fetch_assoc();
    
    echo "Product Data:\n";
    print_r($productData);
    echo "\n";
    
    // Check stock summary directly
    $checkStockStmt = $conn->prepare("
        SELECT ss.summary_id, ss.product_id, ss.available_quantity, ss.batch_reference
        FROM tbl_stock_summary ss
        WHERE ss.product_id = ?
    ");
    $checkStockStmt->bind_param("i", $product_id);
    $checkStockStmt->execute();
    $stockResult = $checkStockStmt->get_result();
    $stockData = [];
    while ($row = $stockResult->fetch_assoc()) {
        $stockData[] = $row;
    }
    
    echo "Stock Summary Data:\n";
    print_r($stockData);
    echo "\n";
    
    // Get FIFO stock for availability check
    $stmt = $conn->prepare("
        SELECT 
            ss.available_quantity,
            ss.batch_reference,
            b.entry_date,
            ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_rank
        FROM tbl_stock_summary ss
        JOIN tbl_batch b ON ss.batch_id = b.batch_id
        WHERE ss.product_id = ? 
        AND b.location_id = ?
        AND ss.available_quantity > 0
        ORDER BY b.entry_date ASC, ss.summary_id ASC
    ");
    
    $stmt->bind_param("ii", $product_id, $location_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_available = 0;
    $batches = [];
    
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
        $total_available += $row['available_quantity'];
    }
    
    echo "FIFO Query Results:\n";
    echo "Total Available: $total_available\n";
    echo "Batches Found: " . count($batches) . "\n";
    echo "Batches:\n";
    print_r($batches);
    echo "\n";
    
    $is_available = $total_available >= $requested_quantity;
    
    $response = [
        "success" => true,
        "is_available" => $is_available,
        "total_available" => $total_available,
        "requested_quantity" => $requested_quantity,
        "batches_count" => count($batches),
        "next_batches" => array_slice($batches, 0, 3),
        "debug_info" => [
            "product_data" => $productData,
            "stock_summary_data" => $stockData,
            "location_id_used" => $location_id
        ]
    ];
    
    echo "Final Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test JSON encoding
    $json_response = json_encode($response);
    if ($json_response === false) {
        echo "❌ JSON Encode Error: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ JSON Encoded Successfully\n";
        echo "JSON Length: " . strlen($json_response) . " bytes\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?> 
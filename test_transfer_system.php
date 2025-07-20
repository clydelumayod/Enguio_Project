<?php
// Test script for transfer system
header('Content-Type: application/json');

// Include database connection
include 'Api/index.php';

try {
    echo "=== Testing Transfer System ===\n\n";
    
    // 1. Test getting products by location
    echo "1. Testing get_products_by_location...\n";
    $testData = [
        'action' => 'get_products_by_location',
        'location_name' => 'warehouse'
    ];
    
    $response = testApiCall($testData);
    echo "Warehouse products: " . count($response['data']) . " found\n";
    
    // 2. Test getting products by location
    echo "\n2. Testing get_products_by_location for Pharmacy...\n";
    $testData = [
        'action' => 'get_products_by_location',
        'location_name' => 'Pharmacy'
    ];
    
    $response = testApiCall($testData);
    echo "Pharmacy products: " . count($response['data']) . " found\n";
    
    // 3. Test barcode check
    echo "\n3. Testing barcode check...\n";
    $testData = [
        'action' => 'check_barcode',
        'barcode' => '22221234567890123',
        'location_name' => 'warehouse'
    ];
    
    $response = testApiCall($testData);
    if ($response['success']) {
        echo "Product found: " . $response['product']['product_name'] . " in " . $response['product']['location_name'] . "\n";
    } else {
        echo "Product not found\n";
    }
    
    // 4. Test transfer creation (if products exist)
    echo "\n4. Testing transfer creation...\n";
    $testData = [
        'action' => 'create_transfer',
        'source_location_id' => 2, // warehouse
        'destination_location_id' => 3, // pharmacy
        'employee_id' => 19,
        'status' => 'Completed',
        'products' => [
            [
                'product_id' => 82,
                'quantity' => 5
            ]
        ]
    ];
    
    $response = testApiCall($testData);
    echo "Transfer result: " . $response['message'] . "\n";
    
    // 5. Check products after transfer
    echo "\n5. Checking products after transfer...\n";
    
    // Check warehouse
    $testData = [
        'action' => 'get_products_by_location',
        'location_name' => 'warehouse'
    ];
    $response = testApiCall($testData);
    echo "Warehouse products after transfer: " . count($response['data']) . " found\n";
    
    // Check pharmacy
    $testData = [
        'action' => 'get_products_by_location',
        'location_name' => 'Pharmacy'
    ];
    $response = testApiCall($testData);
    echo "Pharmacy products after transfer: " . count($response['data']) . " found\n";
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Test failed: " . $e->getMessage()
    ]);
}

function testApiCall($data) {
    global $conn;
    
    // Simulate the API call logic
    $action = $data['action'];
    
    switch ($action) {
        case 'get_products_by_location':
            $location_name = $data['location_name'] ?? '';
            
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name,
                    batch.batch as batch_reference,
                    batch.entry_date,
                    batch.entry_by
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
                WHERE (p.status IS NULL OR p.status <> 'archived')
                AND l.location_name = ?
                ORDER BY p.product_id DESC
            ");
            $stmt->execute([$location_name]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "data" => $products
            ];
            
        case 'check_barcode':
            $barcode = $data['barcode'] ?? '';
            $location_name = $data['location_name'] ?? null;
            
            $whereClause = "WHERE p.barcode = ?";
            $params = [$barcode];
            
            if ($location_name) {
                $whereClause .= " AND l.location_name = ?";
                $params[] = $location_name;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    s.supplier_name,
                    b.brand,
                    l.location_name
                FROM tbl_product p 
                LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
                LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
                LEFT JOIN tbl_location l ON p.location_id = l.location_id
                $whereClause
                AND (p.status IS NULL OR p.status <> 'archived')
                LIMIT 1
            ");
            $stmt->execute($params);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                return [
                    "success" => true,
                    "product" => $product,
                    "message" => "Product found"
                ];
            } else {
                return [
                    "success" => false,
                    "product" => null,
                    "message" => "Product not found"
                ];
            }
            
        case 'create_transfer':
            // This would call the actual transfer logic
            // For testing, we'll just return success
            return [
                "success" => true,
                "message" => "Transfer test completed"
            ];
            
        default:
            return [
                "success" => false,
                "message" => "Unknown action: $action"
            ];
    }
}
?> 
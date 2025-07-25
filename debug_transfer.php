<?php
// Debug script to check transfer data flow
include 'Api/index.php';

echo "🔍 Debugging Transfer Data Flow\n";
echo "==============================\n\n";

// Check what locations exist
echo "1. Available Locations:\n";
$stmt = $conn->prepare("SELECT * FROM tbl_location ORDER BY location_name");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($locations as $location) {
    echo "- ID: {$location['location_id']}, Name: {$location['location_name']}\n";
}
echo "\n";

// Check products in each location
echo "2. Products by Location:\n";
foreach ($locations as $location) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE location_id = ?");
    $stmt->execute([$location['location_id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- {$location['location_name']}: {$count['count']} products\n";
}
echo "\n";

// Check recent transfers
echo "3. Recent Transfers:\n";
$stmt = $conn->prepare("
    SELECT 
        th.transfer_header_id,
        th.date,
        sl.location_name as source_name,
        dl.location_name as dest_name,
        th.status,
        COUNT(td.product_id) as product_count
    FROM tbl_transfer_header th
    LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
    LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
    LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
    GROUP BY th.transfer_header_id
    ORDER BY th.date DESC
    LIMIT 5
");
$stmt->execute();
$transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($transfers as $transfer) {
    echo "- Transfer {$transfer['transfer_header_id']}: {$transfer['source_name']} → {$transfer['dest_name']} ({$transfer['product_count']} products, {$transfer['status']})\n";
}
echo "\n";

// Check specific product movement
echo "4. Sample Product Movement:\n";
$stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.barcode,
        p.quantity,
        l.location_name
    FROM tbl_product p
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    WHERE p.quantity > 0
    ORDER BY p.product_name
    LIMIT 5
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    echo "- {$product['product_name']} (Barcode: {$product['barcode']}): {$product['quantity']} in {$product['location_name']}\n";
}
echo "\n";

// Test a simple transfer
echo "5. Testing Simple Transfer:\n";
$warehouse = null;
$convenience = null;

foreach ($locations as $location) {
    if (stripos($location['location_name'], 'warehouse') !== false) {
        $warehouse = $location;
    }
    if (stripos($location['location_name'], 'convenience') !== false) {
        $convenience = $location;
    }
}

if ($warehouse && $convenience) {
    echo "Found locations:\n";
    echo "- Source (Warehouse): {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
    echo "- Destination (Convenience): {$convenience['location_name']} (ID: {$convenience['location_id']})\n\n";
    
    // Check products in warehouse
    $stmt = $conn->prepare("SELECT product_id, product_name, quantity FROM tbl_product WHERE location_id = ? AND quantity > 0 LIMIT 1");
    $stmt->execute([$warehouse['location_id']]);
    $warehouseProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($warehouseProduct) {
        echo "Testing transfer of: {$warehouseProduct['product_name']} (Qty: {$warehouseProduct['quantity']})\n";
        
        // Simulate transfer data
        $transferData = [
            'source_location_id' => $warehouse['location_id'],
            'destination_location_id' => $convenience['location_id'],
            'employee_id' => 1,
            'status' => 'Completed',
            'products' => [
                [
                    'product_id' => $warehouseProduct['product_id'],
                    'quantity' => 1
                ]
            ]
        ];
        
        echo "Transfer data:\n";
        echo json_encode($transferData, JSON_PRETTY_PRINT) . "\n\n";
        
        // Check before transfer
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE location_id = ?");
        $stmt->execute([$convenience['location_id']]);
        $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Products in destination before: {$beforeCount['count']}\n";
        
        // Make the transfer
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'create_transfer'] + $transferData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "Transfer response: $response\n\n";
        
        // Check after transfer
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE location_id = ?");
        $stmt->execute([$convenience['location_id']]);
        $afterCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Products in destination after: {$afterCount['count']}\n";
        
        if ($afterCount['count'] > $beforeCount['count']) {
            echo "✅ Transfer successful - products moved to destination\n";
        } else {
            echo "❌ Transfer failed - no products moved to destination\n";
        }
        
    } else {
        echo "No products found in warehouse for testing\n";
    }
} else {
    echo "Could not find required locations\n";
}

echo "\nDebug completed.\n";
?> 
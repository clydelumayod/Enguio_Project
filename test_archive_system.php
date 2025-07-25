<?php
// Test script for archive system
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing Archive System\n";
    echo "=====================\n\n";
    
    // Test 1: Check if archive table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'tbl_archive'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ Archive table exists\n";
    } else {
        echo "❌ Archive table does not exist\n";
        exit;
    }
    
    // Test 2: Check table structure
    $stmt = $conn->prepare("DESCRIBE tbl_archive");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Archive table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']}\n";
    }
    
    // Test 3: Test API endpoints
    echo "\nTesting API endpoints...\n";
    
    // Test get_archived_items
    $data = json_encode(['action' => 'get_archived_items']);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data
        ]
    ]);
    
    $response = file_get_contents('http://localhost/Enguio_Project/Api/backend.php', false, $context);
    $result = json_decode($response, true);
    
    if ($result && isset($result['success'])) {
        echo "✅ get_archived_items API working\n";
        echo "  - Found " . count($result['data']) . " archived items\n";
    } else {
        echo "❌ get_archived_items API failed\n";
        echo "  - Response: " . $response . "\n";
    }
    
    // Test 4: Check if there are any products or suppliers to test archiving
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE status != 'archived' LIMIT 1");
    $stmt->execute();
    $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_supplier WHERE status != 'archived' LIMIT 1");
    $stmt->execute();
    $supplierCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\nAvailable items for testing:\n";
    echo "  - Products: $productCount\n";
    echo "  - Suppliers: $supplierCount\n";
    
    if ($productCount > 0 || $supplierCount > 0) {
        echo "\n✅ Archive system is ready for testing!\n";
        echo "You can now archive items from the warehouse and they will appear in the archive.\n";
    } else {
        echo "\n⚠️  No items available for testing. Add some products or suppliers first.\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 
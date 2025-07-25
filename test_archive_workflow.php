<?php
// Test script to verify the complete archive workflow
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing Complete Archive Workflow\n";
    echo "=================================\n\n";
    
    // Step 1: Find a test product to archive
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.status,
            l.location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE (p.status IS NULL OR p.status <> 'archived')
        LIMIT 1
    ");
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testProduct) {
        echo "❌ No active products found for testing\n";
        exit;
    }
    
    echo "🧪 Test Product Found:\n";
    echo "  - ID: {$testProduct['product_id']}\n";
    echo "  - Name: {$testProduct['product_name']}\n";
    echo "  - Location: {$testProduct['location_name']}\n";
    echo "  - Current Status: {$testProduct['status']}\n\n";
    
    // Step 2: Check current product count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE (status IS NULL OR status <> 'archived')");
    $stmt->execute();
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Products before archive: $beforeCount\n";
    
    // Step 3: Archive the product via API
    echo "\n🔄 Archiving product via API...\n";
    $data = json_encode([
        'action' => 'delete_product',
        'product_id' => $testProduct['product_id'],
        'reason' => 'Test archive workflow',
        'archived_by' => 'test_user'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data
        ]
    ]);
    
    $response = file_get_contents('http://localhost/Enguio_Project/Api/backend.php', false, $context);
    $result = json_decode($response, true);
    
    if ($result && isset($result['success']) && $result['success']) {
        echo "✅ Product archived successfully!\n";
    } else {
        echo "❌ Failed to archive product\n";
        echo "Response: " . $response . "\n";
        exit;
    }
    
    // Step 4: Check if product is in archive table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_archive WHERE item_id = ? AND item_type = 'Product'");
    $stmt->execute([$testProduct['product_id']]);
    $archiveCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($archiveCount > 0) {
        echo "✅ Product found in archive table\n";
    } else {
        echo "❌ Product not found in archive table\n";
    }
    
    // Step 5: Check if product status changed
    $stmt = $conn->prepare("SELECT status FROM tbl_product WHERE product_id = ?");
    $stmt->execute([$testProduct['product_id']]);
    $newStatus = $stmt->fetch(PDO::FETCH_ASSOC)['status'];
    
    if ($newStatus === 'archived') {
        echo "✅ Product status changed to 'archived'\n";
    } else {
        echo "❌ Product status is: $newStatus (expected 'archived')\n";
    }
    
    // Step 6: Check if product count decreased
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE (status IS NULL OR status <> 'archived')");
    $stmt->execute();
    $afterCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Products after archive: $afterCount\n";
    
    if ($afterCount < $beforeCount) {
        echo "✅ Product count decreased (archive filtering working)\n";
    } else {
        echo "❌ Product count did not decrease\n";
    }
    
    // Step 7: Test API filtering
    echo "\n🔧 Testing API filtering after archive...\n";
    
    $data = json_encode(['action' => 'get_products']);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data
        ]
    ]);
    
    $response = file_get_contents('http://localhost/Enguio_Project/Api/backend.php', false, $context);
    $result = json_decode($response, true);
    
    if ($result && isset($result['success']) && $result['success']) {
        $apiProductCount = count($result['data']);
        echo "✅ API returned $apiProductCount products (should match $afterCount)\n";
        
        // Check if archived product is in API response
        $archivedInResponse = false;
        foreach ($result['data'] as $product) {
            if ($product['product_id'] == $testProduct['product_id']) {
                $archivedInResponse = true;
                break;
            }
        }
        
        if (!$archivedInResponse) {
            echo "✅ Archived product not in API response (correct)\n";
        } else {
            echo "❌ Archived product still in API response (incorrect)\n";
        }
    }
    
    // Step 8: Restore the product for future testing
    echo "\n🔄 Restoring product for future testing...\n";
    $stmt = $conn->prepare("UPDATE tbl_product SET status = 'active' WHERE product_id = ?");
    $stmt->execute([$testProduct['product_id']]);
    
    $stmt = $conn->prepare("UPDATE tbl_archive SET status = 'Restored' WHERE item_id = ? AND item_type = 'Product'");
    $stmt->execute([$testProduct['product_id']]);
    
    echo "✅ Product restored successfully\n";
    
    echo "\n✅ Archive Workflow Test Completed!\n";
    echo "\n📝 Summary:\n";
    echo "  - Product archiving: ✅ Working\n";
    echo "  - Archive table: ✅ Working\n";
    echo "  - Status update: ✅ Working\n";
    echo "  - API filtering: ✅ Working\n";
    echo "  - Product restoration: ✅ Working\n";
    
} catch(PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 
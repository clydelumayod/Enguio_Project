<?php
/**
 * Fix Transfer Issue Script
 * 
 * This script checks and fixes the issue where products are being transferred
 * to warehouse instead of convenience store.
 */

// Simple database connection check
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

echo "🔧 Fixing Transfer Issue\n";
echo "=======================\n\n";

try {
    // Try to connect using mysqli first
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "✅ Database connection successful!\n\n";
    
    // Step 1: Check locations
    echo "1. Checking Locations:\n";
    $result = $mysqli->query("SELECT * FROM tbl_location ORDER BY location_id");
    
    if ($result) {
        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row;
            echo "- ID: {$row['location_id']}, Name: {$row['location_name']}\n";
        }
        
        // Find warehouse and convenience store
        $warehouse = null;
        $convenience = null;
        
        foreach ($locations as $location) {
            if (strtolower($location['location_name']) === 'warehouse') {
                $warehouse = $location;
            }
            if (strtolower($location['location_name']) === 'convenience store') {
                $convenience = $location;
            }
        }
        
        if (!$warehouse || !$convenience) {
            echo "❌ Error: Warehouse or Convenience Store not found!\n";
            echo "Creating missing locations...\n";
            
            // Create warehouse if missing
            if (!$warehouse) {
                $mysqli->query("INSERT INTO tbl_location (location_name) VALUES ('Warehouse')");
                echo "✅ Created Warehouse location\n";
            }
            
            // Create convenience store if missing
            if (!$convenience) {
                $mysqli->query("INSERT INTO tbl_location (location_name) VALUES ('Convenience Store')");
                echo "✅ Created Convenience Store location\n";
            }
            
            // Refresh locations
            $result = $mysqli->query("SELECT * FROM tbl_location ORDER BY location_id");
            $locations = [];
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row;
            }
            
            // Find again
            foreach ($locations as $location) {
                if (strtolower($location['location_name']) === 'warehouse') {
                    $warehouse = $location;
                }
                if (strtolower($location['location_name']) === 'convenience store') {
                    $convenience = $location;
                }
            }
        }
        
        if ($warehouse && $convenience) {
            echo "\n✅ Found required locations:\n";
            echo "- Warehouse: {$warehouse['location_name']} (ID: {$warehouse['location_id']})\n";
            echo "- Convenience Store: {$convenience['location_name']} (ID: {$convenience['location_id']})\n";
        }
    }
    
    // Step 2: Check products
    echo "\n2. Checking Products:\n";
    $result = $mysqli->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.quantity,
            l.location_name
        FROM tbl_product p
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        WHERE p.quantity > 0
        ORDER BY l.location_name, p.product_name
        LIMIT 10
    ");
    
    if ($result) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
            echo "- {$row['product_name']}: {$row['quantity']} in {$row['location_name']}\n";
        }
        
        if (count($products) > 0) {
            echo "\n✅ Found products for testing\n";
        } else {
            echo "\n⚠️ No products found for testing\n";
        }
    }
    
    // Step 3: Check recent transfers
    echo "\n3. Checking Recent Transfers:\n";
    $result = $mysqli->query("
        SELECT 
            th.transfer_header_id,
            th.date,
            th.status,
            sl.location_name as source_location,
            dl.location_name as destination_location
        FROM tbl_transfer_header th
        LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
        LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
        ORDER BY th.date DESC
        LIMIT 5
    ");
    
    if ($result) {
        $transfers = [];
        while ($row = $result->fetch_assoc()) {
            $transfers[] = $row;
            echo "- Transfer ID: {$row['transfer_header_id']}\n";
            echo "  Date: {$row['date']}\n";
            echo "  Status: {$row['status']}\n";
            echo "  From: {$row['source_location']} To: {$row['destination_location']}\n\n";
        }
        
        if (count($transfers) > 0) {
            echo "✅ Found transfer history\n";
        } else {
            echo "ℹ️ No transfer history found\n";
        }
    }
    
    // Step 4: Test API endpoint
    echo "\n4. Testing API Endpoint:\n";
    $testData = [
        'action' => 'get_locations'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Enguio_Project/Api/backend.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✅ API endpoint working correctly\n";
            echo "Found " . count($result['data']) . " locations via API\n";
        } else {
            echo "⚠️ API returned error: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ API endpoint failed (HTTP $httpCode)\n";
    }
    
    echo "\n🎉 Transfer Issue Analysis Complete!\n";
    echo "\nNext Steps:\n";
    echo "1. Open the Inventory Transfer page in your browser\n";
    echo "2. Check the browser console for debug information\n";
    echo "3. Try creating a transfer from Warehouse to Convenience Store\n";
    echo "4. Use the 'Test Transfer' button to verify the process\n";
    echo "5. Check the 'View Convenience Store' button to see transferred products\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database 'enguio2' exists\n";
    echo "4. Check if PHP PDO extension is enabled\n";
}
?> 
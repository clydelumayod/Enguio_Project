<?php
// Simple database test
echo "<h1>Simple Database Test</h1>";

// Check if PDO is available
echo "<h2>1. PDO Check</h2>";
if (extension_loaded('pdo')) {
    echo "✅ PDO extension is loaded<br>";
    echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
} else {
    echo "❌ PDO extension is not loaded<br>";
}

// Check if MySQL PDO driver is available
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL driver is loaded<br>";
} else {
    echo "❌ PDO MySQL driver is not loaded<br>";
}

// Try to connect to database
echo "<h2>2. Database Connection Test</h2>";
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful<br>";
    
    // Check if tables exist
    echo "<h2>3. Table Check</h2>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables:<br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    // Check transfer tables specifically
    echo "<h2>4. Transfer Tables Check</h2>";
    if (in_array('tbl_transfer_header', $tables)) {
        echo "✅ tbl_transfer_header exists<br>";
        
        // Count transfers
        $stmt = $conn->query("SELECT COUNT(*) FROM tbl_transfer_header");
        $count = $stmt->fetchColumn();
        echo "Number of transfers: $count<br>";
        
        if ($count > 0) {
            // Show sample data
            $stmt = $conn->query("SELECT * FROM tbl_transfer_header ORDER BY transfer_header_id DESC LIMIT 3");
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Transfer Data:</h3>";
            echo "<pre>" . print_r($transfers, true) . "</pre>";
        }
    } else {
        echo "❌ tbl_transfer_header does not exist<br>";
    }
    
    if (in_array('tbl_transfer_dtl', $tables)) {
        echo "✅ tbl_transfer_dtl exists<br>";
        
        // Count transfer details
        $stmt = $conn->query("SELECT COUNT(*) FROM tbl_transfer_dtl");
        $count = $stmt->fetchColumn();
        echo "Number of transfer details: $count<br>";
    } else {
        echo "❌ tbl_transfer_dtl does not exist<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
?> 
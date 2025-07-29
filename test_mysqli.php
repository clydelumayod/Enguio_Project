<?php
// Test mysqli connection
echo "<h1>MySQLi Connection Test</h1>";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ MySQLi connection successful<br>";
    
    // Check if transfer tables exist
    $result = $conn->query("SHOW TABLES LIKE 'tbl_transfer_header'");
    if ($result->num_rows > 0) {
        echo "✅ tbl_transfer_header table exists<br>";
        
        // Count transfers
        $result = $conn->query("SELECT COUNT(*) as count FROM tbl_transfer_header");
        $row = $result->fetch_assoc();
        echo "Number of transfers: " . $row['count'] . "<br>";
        
        if ($row['count'] > 0) {
            // Show sample data
            $result = $conn->query("SELECT * FROM tbl_transfer_header ORDER BY transfer_header_id DESC LIMIT 3");
            echo "<h3>Sample Transfer Data:</h3>";
            while ($row = $result->fetch_assoc()) {
                echo "<pre>" . print_r($row, true) . "</pre>";
            }
        }
    } else {
        echo "❌ tbl_transfer_header table does not exist<br>";
    }
    
    $conn->close();
    
} catch(Exception $e) {
    echo "❌ MySQLi connection failed: " . $e->getMessage() . "<br>";
}
?> 
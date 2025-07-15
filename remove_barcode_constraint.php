<?php
// Database connection using mysqli
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Removing barcode unique constraints...\n";
    
    // Remove unique constraints on barcode field
    $sql1 = "ALTER TABLE tbl_product DROP INDEX barcode";
    $sql2 = "ALTER TABLE tbl_product DROP INDEX barcode_2";
    
    // Execute the first command
    if ($conn->query($sql1)) {
        echo "✓ Dropped index 'barcode'\n";
    } else {
        echo "⚠ Index 'barcode' may not exist: " . $conn->error . "\n";
    }
    
    // Execute the second command
    if ($conn->query($sql2)) {
        echo "✓ Dropped index 'barcode_2'\n";
    } else {
        echo "⚠ Index 'barcode_2' may not exist: " . $conn->error . "\n";
    }
    
    // Verify the constraints are removed
    $checkSql = "SHOW INDEX FROM tbl_product WHERE Key_name LIKE '%barcode%'";
    $result = $conn->query($checkSql);
    
    if ($result && $result->num_rows == 0) {
        echo "✓ All barcode unique constraints have been removed successfully!\n";
        echo "✓ You can now transfer products with the same barcode multiple times.\n";
    } else {
        echo "⚠ Warning: Some barcode indexes still exist:\n";
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                echo "  - " . $row['Key_name'] . "\n";
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "If the indexes don't exist, that's fine - the constraint is already removed.\n";
}
?> 
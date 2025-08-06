<?php
// Fix shift_id constraint to allow NULL values
// This script modifies the database to allow NULL shift_id for admin and inventory roles

try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "enguio2";

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL to modify shift_id column to allow NULL
    $sql = "ALTER TABLE `tbl_employee` MODIFY `shift_id` int(11) NULL";
    
    $conn->exec($sql);
    echo "Successfully modified shift_id column to allow NULL values.\n";
    echo "Admin and inventory roles can now have NULL shift_id.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn = null;
?> 
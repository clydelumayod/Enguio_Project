<?php
// Script to add date_added column to tbl_product table
include 'Api/index.php';

try {
    // Check if date_added column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM tbl_product LIKE 'date_added'");
    $stmt->execute();
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the date_added column
        $conn->exec("ALTER TABLE tbl_product ADD COLUMN date_added DATE DEFAULT CURRENT_DATE");
        echo "✅ date_added column added successfully!\n";
        
        // Update existing records
        $conn->exec("UPDATE tbl_product SET date_added = CURRENT_DATE WHERE date_added IS NULL");
        echo "✅ Updated existing records with current date.\n";
    } else {
        echo "✅ date_added column already exists.\n";
    }
    
    echo "✅ Database structure is now correct!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 
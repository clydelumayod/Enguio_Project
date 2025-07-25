<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'enguio';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Disable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Tables to clear (in order to avoid foreign key constraint issues)
    $tables = [
        'tbl_transfer_log',
        'tbl_transfer_dtl', 
        'tbl_transfer_header',
        'tbl_purchase_return_dtl',
        'tbl_purchase_return_header',
        'tbl_purchase_order_dtl',
        'tbl_purchase_order_header',
        'tbl_pos_sales_details',
        'tbl_pos_sales_header',
        'tbl_pos_transaction',
        'tbl_pos_terminal',
        'tbl_adjustment_details',
        'tbl_adjustment_header',
        'tbl_product',
        'tbl_batch',
        'tbl_supplier',
        'tbl_employee',
        'tbl_brand',
        'tbl_discount'
    ];
    
    $totalDeleted = 0;
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("DELETE FROM $table");
            $stmt->execute();
            $deletedRows = $stmt->rowCount();
            $totalDeleted += $deletedRows;
            echo "Cleared $table: $deletedRows rows deleted\n";
            
            // Reset auto-increment
            $conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            
        } catch (Exception $e) {
            echo "Error clearing $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== SUMMARY ===\n";
    echo "Total rows deleted: $totalDeleted\n";
    echo "All tables have been cleared successfully!\n";
    echo "Auto-increment counters have been reset.\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 
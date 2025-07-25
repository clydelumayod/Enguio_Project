<?php
// Migration script to populate FIFO tables with existing product data
// Run this script once to migrate existing products to FIFO system

// Database connection
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting FIFO migration...\n";
    
    // Get all active products with batch information
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.quantity,
            p.unit_price,
            p.expiration,
            p.batch_id,
            b.batch as batch_reference,
            b.entry_date,
            b.entry_time
        FROM tbl_product p
        LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
        WHERE p.status = 'active' AND p.quantity > 0
        ORDER BY p.product_id
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products to migrate.\n";
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($products as $product) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Create stock movement record
            $movementStmt = $pdo->prepare("
                INSERT INTO tbl_stock_movements (
                    product_id, batch_id, movement_type, quantity, remaining_quantity,
                    unit_cost, expiration_date, reference_no, created_by
                ) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, 'migration')
            ");
            
            $movementStmt->execute([
                $product['product_id'],
                $product['batch_id'] ?: 1, // Use batch_id if exists, otherwise use 1
                $product['quantity'],
                $product['quantity'],
                $product['unit_price'],
                $product['expiration'],
                $product['batch_reference'] ?: 'MIGRATED-' . $product['product_id']
            ]);
            
            // Create stock summary record
            $summaryStmt = $pdo->prepare("
                INSERT INTO tbl_stock_summary (
                    product_id, batch_id, available_quantity, unit_cost, 
                    expiration_date, batch_reference, total_quantity
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    available_quantity = VALUES(available_quantity),
                    total_quantity = VALUES(total_quantity),
                    last_updated = CURRENT_TIMESTAMP
            ");
            
            $summaryStmt->execute([
                $product['product_id'],
                $product['batch_id'] ?: 1,
                $product['quantity'],
                $product['unit_price'],
                $product['expiration'],
                $product['batch_reference'] ?: 'MIGRATED-' . $product['product_id'],
                $product['quantity']
            ]);
            
            $pdo->commit();
            $migrated++;
            echo "Migrated product: " . $product['product_name'] . " (ID: " . $product['product_id'] . ")\n";
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors++;
            echo "Error migrating product " . $product['product_name'] . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nMigration completed!\n";
    echo "Successfully migrated: $migrated products\n";
    echo "Errors: $errors products\n";
    
    // Verify migration
    $verifyStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_stock_summary");
    $verifyStmt->execute();
    $count = $verifyStmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total records in tbl_stock_summary: $count\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 
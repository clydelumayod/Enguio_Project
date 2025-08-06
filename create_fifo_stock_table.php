<?php
/**
 * Create tbl_fifo_stock Table Script
 * This script creates the missing tbl_fifo_stock table for FIFO inventory tracking
 */

// Database configuration
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = ''; // Update this if you have a password set

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully\n";
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'tbl_fifo_stock'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Table tbl_fifo_stock already exists\n";
        exit;
    }
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Create the tbl_fifo_stock table
    $sql = "
    CREATE TABLE `tbl_fifo_stock` (
        `fifo_id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL,
        `batch_id` INT(11) NOT NULL,
        `batch_reference` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) NOT NULL DEFAULT 0,
        `available_quantity` INT(11) NOT NULL DEFAULT 0,
        `unit_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `expiration_date` DATE DEFAULT NULL,
        `entry_date` DATE NOT NULL,
        `entry_by` VARCHAR(100) DEFAULT 'admin',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`fifo_id`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_batch_id` (`batch_id`),
        KEY `idx_expiration_date` (`expiration_date`),
        KEY `idx_entry_date` (`entry_date`),
        CONSTRAINT `fk_fifo_stock_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_fifo_stock_batch` FOREIGN KEY (`batch_id`) REFERENCES `tbl_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Table tbl_fifo_stock created successfully\n";
    
    // Verify the table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'tbl_fifo_stock'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Verification: Table exists in database\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE tbl_fifo_stock");
        echo "\n📋 Table Structure:\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-20s %-15s %-10s %-10s %-10s %-10s\n", 
                    "Field", "Type", "Null", "Key", "Default", "Extra");
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-20s %-15s %-10s %-10s %-10s %-10s\n",
                        $row['Field'],
                        $row['Type'],
                        $row['Null'],
                        $row['Key'],
                        $row['Default'] ?? 'NULL',
                        $row['Extra']);
        }
        
        echo "\n🎉 tbl_fifo_stock table is ready for FIFO inventory tracking!\n";
        
    } else {
        echo "❌ Error: Table was not created properly\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    
    // If foreign key constraint fails, try without constraints
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo "\n🔄 Trying to create table without foreign key constraints...\n";
        
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $sql_simple = "
            CREATE TABLE `tbl_fifo_stock` (
                `fifo_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_id` INT(11) NOT NULL,
                `batch_id` INT(11) NOT NULL,
                `batch_reference` VARCHAR(100) DEFAULT NULL,
                `quantity` INT(11) NOT NULL DEFAULT 0,
                `available_quantity` INT(11) NOT NULL DEFAULT 0,
                `unit_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `expiration_date` DATE DEFAULT NULL,
                `entry_date` DATE NOT NULL,
                `entry_by` VARCHAR(100) DEFAULT 'admin',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`fifo_id`),
                KEY `idx_product_id` (`product_id`),
                KEY `idx_batch_id` (`batch_id`),
                KEY `idx_expiration_date` (`expiration_date`),
                KEY `idx_entry_date` (`entry_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $pdo->exec($sql_simple);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "✅ Table created successfully (without foreign key constraints)\n";
            echo "⚠️  Note: Foreign key constraints were not added due to missing referenced tables\n";
            
        } catch (PDOException $e2) {
            echo "❌ Failed to create table even without constraints: " . $e2->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}

echo "\n📝 Next Steps:\n";
echo "1. The tbl_fifo_stock table has been created\n";
echo "2. You can now use FIFO inventory tracking features\n";
echo "3. If you encounter foreign key issues, ensure tbl_product and tbl_batch tables exist\n";
echo "4. Test the FIFO functionality in your application\n";
?> 
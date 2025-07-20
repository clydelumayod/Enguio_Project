<?php
// Script to create the missing tbl_location table
// This will fix the "Table 'enguio2.tbl_location' doesn't exist" error

// Database connection settings
$host = 'localhost';
$dbname = 'enguio2'; // Change this to your database name
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // SQL to create the location table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `tbl_location` (
      `location_id` int(11) NOT NULL AUTO_INCREMENT,
      `location_name` varchar(255) NOT NULL,
      `location_type` enum('warehouse','store','pharmacy','office','other') DEFAULT 'other',
      `address` text DEFAULT NULL,
      `contact_person` varchar(255) DEFAULT NULL,
      `contact_number` varchar(50) DEFAULT NULL,
      `email` varchar(255) DEFAULT NULL,
      `status` enum('active','inactive') DEFAULT 'active',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`location_id`),
      UNIQUE KEY `location_name` (`location_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    // Execute the create table SQL
    $pdo->exec($createTableSQL);
    echo "Table tbl_location created successfully.\n";
    
    // Check if table has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_location");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default locations
        $insertSQL = "
        INSERT INTO `tbl_location` (`location_name`, `location_type`, `address`, `status`) VALUES
        ('Main Warehouse', 'warehouse', '123 Main Street, City', 'active'),
        ('Convenience Store', 'store', '456 Retail Avenue, City', 'active'),
        ('Pharmacy Branch', 'pharmacy', '789 Health Road, City', 'active'),
        ('Office Location', 'office', '321 Business Blvd, City', 'active')
        ";
        
        $pdo->exec($insertSQL);
        echo "Default locations inserted successfully.\n";
    } else {
        echo "Table already has data. Skipping default insertions.\n";
    }
    
    // Try to add location_id columns to existing tables (if they don't exist)
    $tables = ['tbl_product', 'tbl_brand', 'tbl_notification'];
    
    foreach ($tables as $table) {
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Check if location_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'location_id'");
                if ($stmt->rowCount() == 0) {
                    // Add location_id column
                    $alterSQL = "ALTER TABLE `$table` ADD COLUMN `location_id` int(11) DEFAULT NULL";
                    $pdo->exec($alterSQL);
                    echo "Added location_id column to $table.\n";
                } else {
                    echo "location_id column already exists in $table.\n";
                }
            } else {
                echo "Table $table does not exist. Skipping.\n";
            }
        } catch (Exception $e) {
            echo "Error with table $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify the table was created
    $stmt = $pdo->query("SELECT * FROM tbl_location");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent locations in database:\n";
    foreach ($locations as $location) {
        echo "- {$location['location_name']} ({$location['location_type']})\n";
    }
    
    echo "\nTable creation completed successfully!\n";
    echo "The MovementHistory page should now work without errors.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
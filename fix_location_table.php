<!DOCTYPE html>
<html>
<head>
    <title>Fix Location Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Fix Missing tbl_location Table</h1>
    
    <?php
    // Database connection settings
    $host = 'localhost';
    $dbname = 'enguio2'; // Change this to your database name
    $username = 'root';
    $password = '';

    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p class='success'>✓ Connected to database successfully.</p>";
        
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
        echo "<p class='success'>✓ Table tbl_location created successfully.</p>";
        
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
            echo "<p class='success'>✓ Default locations inserted successfully.</p>";
        } else {
            echo "<p class='info'>ℹ Table already has data. Skipping default insertions.</p>";
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
                        echo "<p class='success'>✓ Added location_id column to $table.</p>";
                    } else {
                        echo "<p class='info'>ℹ location_id column already exists in $table.</p>";
                    }
                } else {
                    echo "<p class='info'>ℹ Table $table does not exist. Skipping.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Error with table $table: " . $e->getMessage() . "</p>";
            }
        }
        
        // Verify the table was created
        $stmt = $pdo->query("SELECT * FROM tbl_location");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current locations in database:</h3>";
        echo "<ul>";
        foreach ($locations as $location) {
            echo "<li><strong>{$location['location_name']}</strong> ({$location['location_type']})</li>";
        }
        echo "</ul>";
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✓ Table creation completed successfully!</h3>";
        echo "<p style='color: #155724; margin-bottom: 0;'>The MovementHistory page should now work without errors.</p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3>Next Steps:</h3>
        <ol>
            <li>Go back to your MovementHistory page</li>
            <li>The error should now be resolved</li>
            <li>You should see location options in the filter dropdown</li>
        </ol>
    </div>
</body>
</html> 
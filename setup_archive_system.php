<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully\n";
    
    // Create archive table
    $sql = "CREATE TABLE IF NOT EXISTS `tbl_archive` (
        `archive_id` int(11) NOT NULL AUTO_INCREMENT,
        `item_id` int(11) NOT NULL,
        `item_type` enum('Product','Category','Supplier') NOT NULL,
        `item_name` varchar(255) NOT NULL,
        `item_description` text,
        `category` varchar(255),
        `archived_by` varchar(100) NOT NULL,
        `archived_date` date NOT NULL,
        `archived_time` time NOT NULL,
        `reason` text,
        `status` enum('Archived','Deleted','Restored') DEFAULT 'Archived',
        `original_data` json,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`archive_id`),
        KEY `idx_item_type` (`item_type`),
        KEY `idx_archived_date` (`archived_date`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->exec($sql);
    echo "Archive table created successfully\n";
    
    // Check if there are any existing archived items to migrate
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_product WHERE status = 'archived'");
    $stmt->execute();
    $archivedProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_supplier WHERE status = 'archived'");
    $stmt->execute();
    $archivedSuppliers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Found $archivedProducts archived products and $archivedSuppliers archived suppliers\n";
    
    if ($archivedProducts > 0 || $archivedSuppliers > 0) {
        echo "Would you like to migrate existing archived items to the new archive table? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim($line) === 'y' || trim($line) === 'Y') {
            // Migrate archived products
            if ($archivedProducts > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO tbl_archive (
                        item_id, item_type, item_name, item_description, category, 
                        archived_by, archived_date, archived_time, reason, status, original_data
                    ) 
                    SELECT 
                        product_id, 'Product', product_name, description, category,
                        'System Migration', CURDATE(), CURTIME(), 'Migrated from existing archived status', 'Archived',
                        JSON_OBJECT(
                            'product_id', product_id,
                            'product_name', product_name,
                            'description', description,
                            'category', category,
                            'barcode', barcode,
                            'unit_price', unit_price,
                            'quantity', quantity,
                            'brand_id', brand_id,
                            'supplier_id', supplier_id
                        )
                    FROM tbl_product 
                    WHERE status = 'archived'
                ");
                $stmt->execute();
                echo "Migrated $archivedProducts archived products\n";
            }
            
            // Migrate archived suppliers
            if ($archivedSuppliers > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO tbl_archive (
                        item_id, item_type, item_name, item_description, category, 
                        archived_by, archived_date, archived_time, reason, status, original_data
                    ) 
                    SELECT 
                        supplier_id, 'Supplier', supplier_name, supplier_address, 'Suppliers',
                        'System Migration', CURDATE(), CURTIME(), 'Migrated from existing archived status', 'Archived',
                        JSON_OBJECT(
                            'supplier_id', supplier_id,
                            'supplier_name', supplier_name,
                            'supplier_address', supplier_address,
                            'supplier_contact', supplier_contact,
                            'supplier_email', supplier_email
                        )
                    FROM tbl_supplier 
                    WHERE status = 'archived'
                ");
                $stmt->execute();
                echo "Migrated $archivedSuppliers archived suppliers\n";
            }
            
            echo "Migration completed successfully!\n";
        }
    }
    
    echo "Archive system setup completed!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
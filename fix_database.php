<?php
// Database fix script for Enguio Project
// This script fixes the SRP column issue and foreign key constraint violations

// Database connection settings
$host = 'localhost';
$username = 'root'; // Change if different
$password = ''; // Change if different
$database = 'enguio2';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Disable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Foreign key checks disabled.\n";
    
    // Check if SRP column exists
    $stmt = $conn->query("SHOW COLUMNS FROM tbl_product LIKE 'srp'");
    if ($stmt->rowCount() == 0) {
        // Add SRP column
        $conn->exec("ALTER TABLE tbl_product ADD COLUMN srp DECIMAL(10,2) DEFAULT NULL COMMENT 'Suggested Retail Price' AFTER unit_price");
        echo "SRP column added successfully.\n";
        
        // Update existing products
        $conn->exec("UPDATE tbl_product SET srp = unit_price WHERE srp IS NULL");
        echo "Existing products updated with SRP values.\n";
    } else {
        echo "SRP column already exists.\n";
    }
    
    // Ensure we have default brands
    $stmt = $conn->query("SELECT COUNT(*) FROM tbl_brand");
    $brandCount = $stmt->fetchColumn();
    
    if ($brandCount == 0) {
        $conn->exec("INSERT INTO tbl_brand (brand_id, brand) VALUES (1, 'Generic'), (2, 'Unbranded'), (3, 'Store Brand')");
        echo "Default brands added.\n";
    } else {
        echo "Brands already exist ($brandCount brands found).\n";
    }
    
    // Ensure we have default suppliers
    $stmt = $conn->query("SELECT COUNT(*) FROM tbl_supplier");
    $supplierCount = $stmt->fetchColumn();
    
    if ($supplierCount == 0) {
        $conn->exec("INSERT INTO tbl_supplier (supplier_id, supplier_name, supplier_address, supplier_contact, supplier_email) VALUES 
                    (1, 'Default Supplier', 'Default Address', 'Default Contact', 'default@example.com'),
                    (2, 'Generic Supplier', 'Generic Address', 'Generic Contact', 'generic@example.com'),
                    (3, 'Store Supplier', 'Store Address', 'Store Contact', 'store@example.com')");
        echo "Default suppliers added.\n";
    } else {
        echo "Suppliers already exist ($supplierCount suppliers found).\n";
    }
    
    // Fix orphaned references
    $conn->exec("UPDATE tbl_product SET brand_id = 1 WHERE brand_id IS NOT NULL AND brand_id NOT IN (SELECT brand_id FROM tbl_brand)");
    $conn->exec("UPDATE tbl_product SET supplier_id = 1 WHERE supplier_id IS NOT NULL AND supplier_id NOT IN (SELECT supplier_id FROM tbl_supplier)");
    $conn->exec("UPDATE tbl_product SET location_id = 2 WHERE location_id IS NOT NULL AND location_id NOT IN (SELECT location_id FROM tbl_location)");
    echo "Orphaned references fixed.\n";
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Foreign key checks re-enabled.\n";
    
    // Verify the changes
    $stmt = $conn->query("SELECT COUNT(*) as total_products FROM tbl_product");
    $productCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) as total_brands FROM tbl_brand");
    $brandCount = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) as total_suppliers FROM tbl_supplier");
    $supplierCount = $stmt->fetchColumn();
    
    echo "\n=== Database Status ===\n";
    echo "Total Products: $productCount\n";
    echo "Total Brands: $brandCount\n";
    echo "Total Suppliers: $supplierCount\n";
    echo "\nDatabase fix completed successfully!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
<?php
// Create the missing tbl_category table
header("Content-Type: text/html");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creating tbl_category table...</h2>";
    
    // Create the category table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `tbl_category` (
      `category_id` int(11) NOT NULL AUTO_INCREMENT,
      `category_name` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `status` enum('active','inactive') DEFAULT 'active',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`category_id`),
      UNIQUE KEY `category_name` (`category_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->exec($createTableSQL);
    echo "<p style='color: green;'>✓ Table tbl_category created successfully.</p>";
    
    // Check if table has data
    $stmt = $conn->query("SELECT COUNT(*) FROM tbl_category");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default categories
        $defaultCategories = [
            'Medicines',
            'Supplements', 
            'Personal Care',
            'Medical Supplies',
            'Food & Beverages',
            'Others'
        ];
        
        $insertStmt = $conn->prepare("INSERT INTO tbl_category (category_name) VALUES (?)");
        
        foreach ($defaultCategories as $category) {
            try {
                $insertStmt->execute([$category]);
                echo "<p style='color: green;'>✓ Added category: $category</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠ Category '$category' already exists or error: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p style='color: green;'>✓ Default categories inserted successfully.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table already has $count categories. Skipping default insertions.</p>";
    }
    
    // Show current categories
    $stmt = $conn->query("SELECT * FROM tbl_category ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current categories in database:</h3>";
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li><strong>ID: {$cat['category_id']}</strong> - {$cat['category_name']}</li>";
    }
    echo "</ul>";
    
    // Also check what categories are currently used in products
    $stmt = $conn->query("SELECT DISTINCT category FROM tbl_product WHERE category IS NOT NULL AND category != ''");
    $productCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productCategories)) {
        echo "<h3>Categories currently used in products:</h3>";
        echo "<ul>";
        foreach ($productCategories as $cat) {
            echo "<li>{$cat['category']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✓ Category table setup completed successfully!</h3>";
    echo "<p style='color: #155724; margin-bottom: 0;'>The category dropdown in your Warehouse should now work properly.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 
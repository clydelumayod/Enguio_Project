<?php
// Test script to check tbl_category table
header("Content-Type: text/html");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Testing tbl_category table...</h2>";
    
    // Check if table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'tbl_category'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table tbl_category exists</p>";
        
        // Get all categories
        $stmt = $conn->prepare("SELECT * FROM tbl_category ORDER BY category_id");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Categories found: " . count($categories) . "</p>";
        
        if (count($categories) > 0) {
            echo "<h3>Categories in database:</h3>";
            echo "<ul>";
            foreach ($categories as $cat) {
                echo "<li><strong>ID: {$cat['category_id']}</strong> - {$cat['category_name']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ No categories found in table</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Table tbl_category does not exist</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 
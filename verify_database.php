<?php
// Simple verification script to check database
header("Content-Type: text/html; charset=utf-8");

echo "<h2>Database Verification</h2>";

// Check PHP version and PDO
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</p>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if tbl_product table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'tbl_product'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ tbl_product table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ tbl_product table does not exist</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking table: " . $e->getMessage() . "</p>";
    exit;
}

// Get total count of products
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM tbl_product");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total products in database:</strong> " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error counting products: " . $e->getMessage() . "</p>";
}

// Get recent products (last 10)
try {
    $stmt = $conn->query("SELECT product_id, product_name, quantity, unit_price, date_added, location_id FROM tbl_product ORDER BY product_id DESC LIMIT 10");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Products (Last 10):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Quantity</th><th>Price</th><th>Date Added</th><th>Location ID</th>";
    echo "</tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($product['unit_price']) . "</td>";
        echo "<td>" . htmlspecialchars($product['date_added']) . "</td>";
        echo "<td>" . htmlspecialchars($product['location_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error fetching recent products: " . $e->getMessage() . "</p>";
}

// Check if tbl_batch table exists and has data
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'tbl_batch'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ tbl_batch table exists</p>";
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM tbl_batch");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Total batches in database:</strong> " . $result['total'] . "</p>";
        
        // Show recent batches
        $stmt = $conn->query("SELECT batch_id, batch, entry_date, entry_time, entry_by FROM tbl_batch ORDER BY batch_id DESC LIMIT 5");
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Batches (Last 5):</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Batch ID</th><th>Batch</th><th>Entry Date</th><th>Entry Time</th><th>Entry By</th>";
        echo "</tr>";
        
        foreach ($batches as $batch) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($batch['batch_id']) . "</td>";
            echo "<td>" . htmlspecialchars($batch['batch']) . "</td>";
            echo "<td>" . htmlspecialchars($batch['entry_date']) . "</td>";
            echo "<td>" . htmlspecialchars($batch['entry_time']) . "</td>";
            echo "<td>" . htmlspecialchars($batch['entry_by']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ tbl_batch table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking batch table: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>This page helps verify that your add product functionality is working correctly.</em></p>";
echo "<p><em>If you see products listed here after adding them through the frontend, then the database saving is working!</em></p>";
?> 
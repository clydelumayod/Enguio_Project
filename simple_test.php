<?php
// Simple database connection test
$host = 'localhost';
$dbname = 'enguio2';
$username = 'root';
$password = '';

echo "Testing database connection...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_supplier");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Number of suppliers: " . $result['count'] . "\n";
    
} catch(PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?> 
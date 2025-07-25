<?php
// Database connection check script
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    // Test connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        "success" => true,
        "message" => "Database connection successful",
        "database" => $dbname
    ]);
    
    // Check if required tables exist
    $requiredTables = [
        'tbl_product',
        'tbl_supplier', 
        'tbl_brand',
        'tbl_category',
        'tbl_location',
        'tbl_batch',
        'tbl_stock_summary'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        } catch (Exception $e) {
            $missingTables[] = $table;
        }
    }
    
    echo json_encode([
        "success" => true,
        "database" => $dbname,
        "existing_tables" => $existingTables,
        "missing_tables" => $missingTables,
        "total_required" => count($requiredTables),
        "total_existing" => count($existingTables)
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "error" => $e->getMessage(),
        "database" => $dbname
    ]);
}
?> 
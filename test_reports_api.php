<?php
// Test the reports API endpoint
echo "Testing reports API...\n";

$data = json_encode(['action' => 'get_reports_data']);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents('http://localhost/Enguio_Project/Api/backend.php', false, $context);
echo "Response: " . $result . "\n";

// Test the direct database query
echo "\nTesting direct database query...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    
    // Test analytics query
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.product_id) as totalProducts,
            COUNT(CASE WHEN p.quantity <= 10 AND p.quantity > 0 THEN 1 END) as lowStockItems,
            COUNT(CASE WHEN p.quantity = 0 THEN 1 END) as outOfStockItems,
            SUM(p.quantity * p.unit_price) as totalValue
        FROM tbl_product p
        WHERE (p.status IS NULL OR p.status <> 'archived')
    ");
    $stmt->execute();
    $analytics = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Analytics: " . json_encode($analytics, JSON_PRETTY_PRINT) . "\n";
    
    // Test stock movements query
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as movement_count
        FROM tbl_stock_movements
    ");
    $stmt->execute();
    $movements = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Stock movements count: " . $movements['movement_count'] . "\n";
    
    // Test transfer reports query
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as transfer_count
        FROM tbl_transfer_header
    ");
    $stmt->execute();
    $transfers = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Transfer count: " . $transfers['transfer_count'] . "\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 
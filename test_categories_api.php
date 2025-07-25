<?php
// Test the get_categories API endpoint
echo "Testing get_categories API...\n";

$data = json_encode(['action' => 'get_categories']);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents('http://localhost/Enguio_Project/Api/backend.php', false, $context);
echo "Response: " . $result . "\n";

// Test the get_categories query
echo "\nTesting direct database query...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=enguio2", "root", "");
    $stmt = $pdo->prepare("SELECT * FROM tbl_category ORDER BY category_id");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Direct query result: " . json_encode($categories, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 
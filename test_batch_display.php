<?php
require_once 'Api/conn.php';

echo "<h1>Batch Information Test</h1>\n";
echo "<pre>\n";

// Test 1: Check if batch information is included in get_products
echo "=== TEST 1: Checking get_products API for batch information ===\n";

$stmt = $conn->prepare("
    SELECT 
        p.*,
        s.supplier_name,
        b.brand,
        l.location_name,
        batch.batch as batch_reference,
        batch.entry_date,
        batch.entry_by,
        COALESCE(p.date_added, CURDATE()) as date_added
    FROM tbl_product p 
    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
    WHERE (p.status IS NULL OR p.status <> 'archived')
    AND p.quantity > 0
    ORDER BY p.product_name ASC
    LIMIT 5
");

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sample products with batch information:\n";
foreach ($products as $product) {
    echo "Product: {$product['product_name']}\n";
    echo "  - Barcode: {$product['barcode']}\n";
    echo "  - Batch Reference: " . ($product['batch_reference'] ?? 'None') . "\n";
    echo "  - Entry Date: " . ($product['entry_date'] ?? 'None') . "\n";
    echo "  - Entry By: " . ($product['entry_by'] ?? 'None') . "\n";
    echo "  - Date Added: " . ($product['date_added'] ?? 'None') . "\n";
    echo "  - Location: {$product['location_name']}\n";
    echo "---\n";
}

// Test 2: Check products by location
echo "\n=== TEST 2: Checking get_products_by_location for Pharmacy ===\n";

$stmt = $conn->prepare("
    SELECT 
        p.*,
        s.supplier_name,
        b.brand,
        l.location_name,
        batch.batch as batch_reference,
        batch.entry_date,
        batch.entry_by
    FROM tbl_product p 
    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
    WHERE (p.status IS NULL OR p.status <> 'archived')
    AND l.location_name = 'Pharmacy'
    ORDER BY p.product_name ASC
    LIMIT 3
");

$stmt->execute();
$pharmacyProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Pharmacy products with batch information:\n";
foreach ($pharmacyProducts as $product) {
    echo "Product: {$product['product_name']}\n";
    echo "  - Barcode: {$product['barcode']}\n";
    echo "  - Batch Reference: " . ($product['batch_reference'] ?? 'None') . "\n";
    echo "  - Entry Date: " . ($product['entry_date'] ?? 'None') . "\n";
    echo "  - Entry By: " . ($product['entry_by'] ?? 'None') . "\n";
    echo "---\n";
}

// Test 3: Check convenience store products
echo "\n=== TEST 3: Checking get_products_by_location for Convenience ===\n";

$stmt = $conn->prepare("
    SELECT 
        p.*,
        s.supplier_name,
        b.brand,
        l.location_name,
        batch.batch as batch_reference,
        batch.entry_date,
        batch.entry_by
    FROM tbl_product p 
    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
    WHERE (p.status IS NULL OR p.status <> 'archived')
    AND l.location_name = 'Convenience'
    ORDER BY p.product_name ASC
    LIMIT 3
");

$stmt->execute();
$convenienceProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Convenience store products with batch information:\n";
foreach ($convenienceProducts as $product) {
    echo "Product: {$product['product_name']}\n";
    echo "  - Barcode: {$product['barcode']}\n";
    echo "  - Batch Reference: " . ($product['batch_reference'] ?? 'None') . "\n";
    echo "  - Entry Date: " . ($product['entry_date'] ?? 'None') . "\n";
    echo "  - Entry By: " . ($product['entry_by'] ?? 'None') . "\n";
    echo "---\n";
}

// Test 4: Check warehouse products
echo "\n=== TEST 4: Checking warehouse products ===\n";

$stmt = $conn->prepare("
    SELECT 
        p.*,
        s.supplier_name,
        b.brand,
        l.location_name,
        batch.batch as batch_reference,
        batch.entry_date,
        batch.entry_by
    FROM tbl_product p 
    LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
    LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    LEFT JOIN tbl_batch batch ON p.batch_id = batch.batch_id
    WHERE (p.status IS NULL OR p.status <> 'archived')
    AND l.location_name = 'warehouse'
    ORDER BY p.product_name ASC
    LIMIT 3
");

$stmt->execute();
$warehouseProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Warehouse products with batch information:\n";
foreach ($warehouseProducts as $product) {
    echo "Product: {$product['product_name']}\n";
    echo "  - Barcode: {$product['barcode']}\n";
    echo "  - Batch Reference: " . ($product['batch_reference'] ?? 'None') . "\n";
    echo "  - Entry Date: " . ($product['entry_date'] ?? 'None') . "\n";
    echo "  - Entry By: " . ($product['entry_by'] ?? 'None') . "\n";
    echo "---\n";
}

echo "</pre>\n";
?> 
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

try {
    // Test the get_products API with for_transfer parameter
    $data = ['for_transfer' => true, 'location_id' => 2]; // Assuming warehouse location_id is 2
    
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.category,
            p.barcode,
            p.description,
            p.Variation,
            p.brand_id,
            p.supplier_id,
            p.location_id,
            p.unit_price,
            p.stock_status,
            s.supplier_name,
            b.brand,
            l.location_name,
            ss.batch_id,
            ss.batch_reference,
            b.entry_date,
            b.entry_by,
            COALESCE(p.date_added, CURDATE()) as date_added,
            -- Show only the OLDEST batch quantity for FIFO transfer
            ss.available_quantity as quantity
        FROM tbl_product p 
        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
        LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        INNER JOIN tbl_batch b ON ss.batch_id = b.batch_id
        WHERE ss.available_quantity > 0
        AND p.location_id = 2
        AND (p.status IS NULL OR p.status <> 'archived')
        AND b.entry_date = (
            SELECT MIN(b2.entry_date) 
            FROM tbl_batch b2 
            INNER JOIN tbl_stock_summary ss2 ON b2.batch_id = ss2.batch_id 
            WHERE ss2.product_id = p.product_id AND ss2.available_quantity > 0
        )
        ORDER BY p.product_name ASC
    ");
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Transfer Products Test Results</h2>";
    echo "<p>Total products available for transfer: " . count($products) . "</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Product Name</th><th>Category</th><th>Brand</th><th>Available Qty</th><th>Batch ID</th><th>Entry Date</th></tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['category']) . "</td>";
        echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
        echo "<td>" . $product['quantity'] . "</td>";
        echo "<td>" . $product['batch_id'] . "</td>";
        echo "<td>" . $product['entry_date'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Also show all products for comparison
    echo "<h3>All Products (for comparison)</h3>";
    $stmt2 = $conn->prepare("
        SELECT 
            p.product_name,
            p.quantity as total_quantity,
            COUNT(ss.summary_id) as batch_count
        FROM tbl_product p 
        LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        WHERE p.location_id = 2 AND (p.status IS NULL OR p.status <> 'archived')
        GROUP BY p.product_id, p.product_name, p.quantity
        ORDER BY p.product_name
    ");
    
    $stmt2->execute();
    $allProducts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Product Name</th><th>Total Quantity</th><th>Batch Count</th></tr>";
    
    foreach ($allProducts as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . $product['total_quantity'] . "</td>";
        echo "<td>" . $product['batch_count'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
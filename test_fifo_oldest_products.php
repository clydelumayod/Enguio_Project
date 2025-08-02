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
    echo "<h2>FIFO Oldest Products Test</h2>";
    
    // First, let's see what products exist in the database
    echo "<h3>1. All Products in Database</h3>";
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.location_id,
            p.quantity,
            p.status
        FROM tbl_product p 
        WHERE (p.status IS NULL OR p.status <> 'archived')
        ORDER BY p.product_name
        LIMIT 10
    ");
    
    $stmt->execute();
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total products found: " . count($allProducts) . "</p>";
    
    if (count($allProducts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Location ID</th><th>Quantity</th><th>Status</th></tr>";
        
        foreach ($allProducts as $product) {
            echo "<tr>";
            echo "<td>" . $product['product_id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
            echo "<td>" . $product['location_id'] . "</td>";
            echo "<td>" . $product['quantity'] . "</td>";
            echo "<td>" . $product['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Now test the FIFO query for transfer
    echo "<h3>2. FIFO Transfer Products (Oldest First)</h3>";
    
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.category,
            p.barcode,
            ss.batch_id,
            ss.batch_reference,
            b.entry_date,
            ss.available_quantity as quantity
        FROM tbl_product p 
        LEFT JOIN tbl_supplier s ON p.supplier_id = s.supplier_id 
        LEFT JOIN tbl_brand b ON p.brand_id = b.brand_id 
        LEFT JOIN tbl_location l ON p.location_id = l.location_id
        INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
        INNER JOIN tbl_batch b ON ss.batch_id = b.batch_id
        WHERE ss.available_quantity > 0
        AND (p.status IS NULL OR p.status <> 'archived')
        AND b.entry_date = (
            SELECT MIN(b2.entry_date) 
            FROM tbl_batch b2 
            INNER JOIN tbl_stock_summary ss2 ON b2.batch_id = ss2.batch_id 
            WHERE ss2.product_id = p.product_id AND ss2.available_quantity > 0
        )
        ORDER BY p.product_name ASC
        LIMIT 10
    ");
    
    $stmt->execute();
    $fifoProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>FIFO products found: " . count($fifoProducts) . "</p>";
    
    if (count($fifoProducts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product Name</th><th>Category</th><th>Batch ID</th><th>Entry Date</th><th>Available Qty</th></tr>";
        
        foreach ($fifoProducts as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['category']) . "</td>";
            echo "<td>" . $product['batch_id'] . "</td>";
            echo "<td>" . $product['entry_date'] . "</td>";
            echo "<td>" . $product['quantity'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test with location filter
    echo "<h3>3. FIFO Products for Location ID 2 (Warehouse)</h3>";
    
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.category,
            p.barcode,
            ss.batch_id,
            ss.batch_reference,
            b.entry_date,
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
        LIMIT 10
    ");
    
    $stmt->execute();
    $locationProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>FIFO products in location 2: " . count($locationProducts) . "</p>";
    
    if (count($locationProducts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product Name</th><th>Category</th><th>Batch ID</th><th>Entry Date</th><th>Available Qty</th></tr>";
        
        foreach ($locationProducts as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['category']) . "</td>";
            echo "<td>" . $product['batch_id'] . "</td>";
            echo "<td>" . $product['entry_date'] . "</td>";
            echo "<td>" . $product['quantity'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Stack trace: " . $e->getTraceAsString();
}
?> 
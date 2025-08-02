<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>FIFO Oldest Products Test (Simple)</h2>";
    
    // Test the FIFO query for transfer
    echo "<h3>FIFO Transfer Products (Oldest First)</h3>";
    
    $sql = "
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
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p>FIFO products found: " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Product Name</th><th>Category</th><th>Batch ID</th><th>Entry Date</th><th>Available Qty</th></tr>";
            
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "<td>" . $row['batch_id'] . "</td>";
                echo "<td>" . $row['entry_date'] . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "Error: " . $conn->error;
    }
    
    // Also test with location filter
    echo "<h3>FIFO Products for Location ID 2 (Warehouse)</h3>";
    
    $sql2 = "
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
    ";
    
    $result2 = $conn->query($sql2);
    
    if ($result2) {
        echo "<p>FIFO products in location 2: " . $result2->num_rows . "</p>";
        
        if ($result2->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Product Name</th><th>Category</th><th>Batch ID</th><th>Entry Date</th><th>Available Qty</th></tr>";
            
            while($row = $result2->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "<td>" . $row['batch_id'] . "</td>";
                echo "<td>" . $row['entry_date'] . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "Error: " . $conn->error;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
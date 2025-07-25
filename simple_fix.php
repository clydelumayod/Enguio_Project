<?php
// Simple script to fix product locations using mysqli
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enguio2";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Database connection successful!\n";

// Step 1: Check locations
echo "\n=== STEP 1: CHECKING LOCATIONS ===\n";
$sql = "SELECT * FROM tbl_location ORDER BY location_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['location_name']} (ID: {$row['location_id']})\n";
    }
}

// Step 2: Check current product locations
echo "\n=== STEP 2: CHECKING CURRENT PRODUCT LOCATIONS ===\n";
$sql = "
    SELECT 
        p.location_id,
        l.location_name,
        COUNT(*) as product_count
    FROM tbl_product p
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    GROUP BY p.location_id, l.location_name
    ORDER BY p.location_id
";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $locationName = $row['location_name'] ?: 'NULL/Unknown';
        echo "- Location ID {$row['location_id']} ({$locationName}): {$row['product_count']} products\n";
    }
}

// Step 3: Fix products with NULL location_id
echo "\n=== STEP 3: FIXING PRODUCT LOCATIONS ===\n";

// Update products with NULL location_id to warehouse
$sql = "UPDATE tbl_product SET location_id = 1 WHERE location_id IS NULL";
if (mysqli_query($conn, $sql)) {
    $updatedNull = mysqli_affected_rows($conn);
    echo "✅ Updated {$updatedNull} products with NULL location_id to warehouse\n";
} else {
    echo "❌ Error updating NULL location_id: " . mysqli_error($conn) . "\n";
}

// Update products with invalid location_id to warehouse
$sql = "UPDATE tbl_product SET location_id = 1 WHERE location_id NOT IN (SELECT location_id FROM tbl_location)";
if (mysqli_query($conn, $sql)) {
    $updatedInvalid = mysqli_affected_rows($conn);
    echo "✅ Updated {$updatedInvalid} products with invalid location_id to warehouse\n";
} else {
    echo "❌ Error updating invalid location_id: " . mysqli_error($conn) . "\n";
}

// Step 4: Verify fix
echo "\n=== STEP 4: VERIFYING FIX ===\n";
$sql = "
    SELECT 
        p.location_id,
        l.location_name,
        COUNT(*) as product_count
    FROM tbl_product p
    LEFT JOIN tbl_location l ON p.location_id = l.location_id
    GROUP BY p.location_id, l.location_name
    ORDER BY p.location_id
";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $locationName = $row['location_name'] ?: 'NULL/Unknown';
        echo "- Location ID {$row['location_id']} ({$locationName}): {$row['product_count']} products\n";
    }
}

// Step 5: Show sample products in warehouse
echo "\n=== STEP 5: SAMPLE WAREHOUSE PRODUCTS ===\n";
$sql = "SELECT product_id, product_name, quantity, barcode, location_id FROM tbl_product WHERE location_id = 1 LIMIT 5";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "Warehouse products (Location ID: 1):\n";
    while($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['product_name']} (ID: {$row['product_id']}, Qty: {$row['quantity']}, Barcode: {$row['barcode']})\n";
    }
} else {
    echo "No products found in warehouse\n";
}

echo "\n🎉 Location fix completed! Products should now appear in the warehouse.\n";

mysqli_close($conn);
?> 
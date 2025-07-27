<?php
require_once 'fifo_transfer_system.php';

/**
 * Test the FIFO transfer system with various scenarios
 */

echo "<h1>FIFO Transfer System Test</h1>\n";
echo "<pre>\n";

// Test 1: Check available stock before transfer
echo "=== TEST 1: Checking Available Stock ===\n";
$stock_check = getAvailableStock(1000000000015, 2); // C2 Apple in warehouse
echo "Available stock for C2 Apple (barcode: 1000000000015) in warehouse:\n";
echo json_encode($stock_check, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Perform a FIFO transfer
echo "=== TEST 2: Performing FIFO Transfer ===\n";
$transfer_result = performFifoTransfer(
    1000000000015,  // C2 Apple barcode
    2,              // From warehouse (location_id: 2)
    4,              // To convenience store (location_id: 4)
    25,             // Transfer 25 units
    21              // Employee ID 21
);

echo "Transfer Result:\n";
echo json_encode($transfer_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Check stock after transfer
echo "=== TEST 3: Checking Stock After Transfer ===\n";
echo "Warehouse stock after transfer:\n";
$warehouse_stock_after = getAvailableStock(1000000000015, 2);
echo json_encode($warehouse_stock_after, JSON_PRETTY_PRINT) . "\n\n";

echo "Convenience store stock after transfer:\n";
$convenience_stock_after = getAvailableStock(1000000000015, 4);
echo json_encode($convenience_stock_after, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Test insufficient stock scenario
echo "=== TEST 4: Testing Insufficient Stock Scenario ===\n";
$insufficient_stock_result = performFifoTransfer(
    1000000000015,  // C2 Apple barcode
    2,              // From warehouse
    4,              // To convenience store
    1000,           // Try to transfer 1000 units (more than available)
    21              // Employee ID 21
);

echo "Insufficient Stock Test Result:\n";
echo json_encode($insufficient_stock_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Test with different product (Tuna Flakes)
echo "=== TEST 5: Testing with Different Product (Tuna Flakes) ===\n";
echo "Available Tuna Flakes stock in warehouse:\n";
$tuna_stock = getAvailableStock(1000000000004, 2);
echo json_encode($tuna_stock, JSON_PRETTY_PRINT) . "\n\n";

if ($tuna_stock['success'] && $tuna_stock['total_available'] > 0) {
    $tuna_transfer = performFifoTransfer(
        1000000000004,  // Tuna Flakes barcode
        2,              // From warehouse
        3,              // To pharmacy
        10,             // Transfer 10 units
        20              // Employee ID 20
    );
    
    echo "Tuna Flakes Transfer Result:\n";
    echo json_encode($tuna_transfer, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 6: Show transfer history
echo "=== TEST 6: Recent Transfer History ===\n";
showRecentTransfers();

echo "</pre>\n";

/**
 * Helper function to show recent transfers
 */
function showRecentTransfers() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                th.transfer_header_id,
                th.date,
                sl.location_name as source_location,
                dl.location_name as destination_location,
                CONCAT(e.Fname, ' ', e.Lname) as employee_name,
                th.status,
                COUNT(td.transfer_dtl_id) as items_count,
                SUM(td.qty) as total_quantity
            FROM tbl_transfer_header th
            INNER JOIN tbl_location sl ON th.source_location_id = sl.location_id
            INNER JOIN tbl_location dl ON th.destination_location_id = dl.location_id
            INNER JOIN tbl_employee e ON th.employee_id = e.emp_id
            LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
            GROUP BY th.transfer_header_id
            ORDER BY th.transfer_header_id DESC
            LIMIT 10
        ");
        
        $stmt->execute();
        $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent Transfers:\n";
        foreach ($transfers as $transfer) {
            echo "Transfer ID: {$transfer['transfer_header_id']}\n";
            echo "Date: {$transfer['date']}\n";
            echo "From: {$transfer['source_location']} → To: {$transfer['destination_location']}\n";
            echo "Employee: {$transfer['employee_name']}\n";
            echo "Status: {$transfer['status']}\n";
            echo "Items: {$transfer['items_count']}, Total Qty: {$transfer['total_quantity']}\n";
            echo "---\n";
        }
        
    } catch (Exception $e) {
        echo "Error retrieving transfer history: " . $e->getMessage() . "\n";
    }
}

/**
 * Additional helper function to get detailed transfer breakdown
 */
function getTransferDetails($transfer_header_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.product_name,
                p.barcode,
                td.qty,
                b.batch_reference,
                b.entry_date
            FROM tbl_transfer_dtl td
            INNER JOIN tbl_product p ON td.product_id = p.product_id
            INNER JOIN tbl_batch b ON p.batch_id = b.batch_id
            WHERE td.transfer_header_id = :transfer_header_id
            ORDER BY b.entry_date ASC
        ");
        
        $stmt->bindParam(':transfer_header_id', $transfer_header_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return null;
    }
}
?> 
-- ============================================================================
-- FIFO TRANSFER SYSTEM - TEST SCRIPT
-- This script demonstrates the FIFO system with real examples
-- ============================================================================

-- Before running this script, make sure you've executed fifo_transfer_system_enhanced.sql

-- ============================================================================
-- STEP 1: Check Current State of Nova Product (ID 215)
-- ============================================================================

SELECT '=== CURRENT STATE OF NOVA PRODUCT ===' as status;

-- Check current product quantities
SELECT 
    p.product_id,
    p.product_name,
    l.location_name,
    p.quantity as product_table_qty
FROM tbl_product p
INNER JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.product_name = 'Nova'
ORDER BY l.location_name;

-- Check FIFO stock details
SELECT 
    fs.product_id,
    p.product_name,
    fs.batch_id,
    fs.batch_reference,
    b.entry_date,
    fs.quantity as original_qty,
    fs.available_quantity,
    fs.unit_cost,
    ROW_NUMBER() OVER (ORDER BY b.entry_date, fs.created_at) as fifo_order
FROM tbl_fifo_stock fs
INNER JOIN tbl_product p ON fs.product_id = p.product_id
INNER JOIN tbl_batch b ON fs.batch_id = b.batch_id
WHERE p.product_name = 'Nova' AND fs.available_quantity > 0
ORDER BY b.entry_date, fs.created_at;

-- Check overall inventory status
SELECT * FROM v_fifo_inventory_status 
WHERE product_name = 'Nova';

-- ============================================================================
-- STEP 2: Preview FIFO Consumption
-- ============================================================================

SELECT '=== FIFO CONSUMPTION PREVIEW ===' as status;

-- Preview what batches will be consumed for different quantities
SELECT 
    'Transfer 25 units' as scenario,
    GetNextFIFOBatches(215, 2, 25) as batches_to_consume
UNION ALL
SELECT 
    'Transfer 60 units' as scenario,
    GetNextFIFOBatches(215, 2, 60) as batches_to_consume
UNION ALL
SELECT 
    'Transfer 100 units' as scenario,
    GetNextFIFOBatches(215, 2, 100) as batches_to_consume;

-- ============================================================================
-- STEP 3: Test Small Transfer (25 units) - Should consume from oldest batch only
-- ============================================================================

SELECT '=== TESTING SMALL TRANSFER (25 UNITS) ===' as status;

-- Create transfer request
INSERT INTO tbl_transfer_header (date, source_location_id, destination_location_id, employee_id, status)
VALUES (CURDATE(), 2, 4, 20, 'pending');

SET @transfer_id_1 = LAST_INSERT_ID();

INSERT INTO tbl_transfer_dtl (transfer_header_id, product_id, qty)
VALUES (@transfer_id_1, 215, 25);

-- Show transfer details
SELECT 
    th.transfer_header_id,
    th.date,
    sl.location_name as source,
    dl.location_name as destination,
    td.qty,
    th.status
FROM tbl_transfer_header th
INNER JOIN tbl_location sl ON th.source_location_id = sl.location_id
INNER JOIN tbl_location dl ON th.destination_location_id = dl.location_id
INNER JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
WHERE th.transfer_header_id = @transfer_id_1;

-- Approve transfer (this triggers FIFO consumption)
UPDATE tbl_transfer_header 
SET status = 'approved' 
WHERE transfer_header_id = @transfer_id_1;

-- Check results after first transfer
SELECT '--- AFTER 25-UNIT TRANSFER ---' as status;

-- Check updated FIFO stock
SELECT 
    fs.batch_reference,
    b.entry_date,
    fs.quantity as original_qty,
    fs.available_quantity as remaining_qty,
    fs.quantity - fs.available_quantity as consumed_qty
FROM tbl_fifo_stock fs
INNER JOIN tbl_batch b ON fs.batch_id = b.batch_id
WHERE fs.product_id = 215
ORDER BY b.entry_date, fs.created_at;

-- Check stock movements
SELECT 
    sm.movement_type,
    sm.quantity,
    sm.remaining_quantity,
    sm.reference_no,
    sm.notes,
    b.batch_reference
FROM tbl_stock_movements sm
INNER JOIN tbl_batch b ON sm.batch_id = b.batch_id
WHERE sm.product_id = 215
ORDER BY sm.movement_date DESC, sm.movement_id DESC
LIMIT 3;

-- ============================================================================
-- STEP 4: Test Large Transfer (60 units) - Should consume from multiple batches
-- ============================================================================

SELECT '=== TESTING LARGE TRANSFER (60 UNITS) ===' as status;

-- Create second transfer request
INSERT INTO tbl_transfer_header (date, source_location_id, destination_location_id, employee_id, status)
VALUES (CURDATE(), 2, 3, 21, 'pending');

SET @transfer_id_2 = LAST_INSERT_ID();

INSERT INTO tbl_transfer_dtl (transfer_header_id, product_id, qty)
VALUES (@transfer_id_2, 215, 60);

-- Approve transfer
UPDATE tbl_transfer_header 
SET status = 'approved' 
WHERE transfer_header_id = @transfer_id_2;

-- Check results after second transfer
SELECT '--- AFTER 60-UNIT TRANSFER ---' as status;

-- Check updated FIFO stock
SELECT 
    fs.batch_reference,
    b.entry_date,
    fs.quantity as original_qty,
    fs.available_quantity as remaining_qty,
    fs.quantity - fs.available_quantity as consumed_qty,
    CASE 
        WHEN fs.available_quantity = 0 THEN 'FULLY CONSUMED'
        WHEN fs.available_quantity < fs.quantity THEN 'PARTIALLY CONSUMED'
        ELSE 'UNTOUCHED'
    END as status
FROM tbl_fifo_stock fs
INNER JOIN tbl_batch b ON fs.batch_id = b.batch_id
WHERE fs.product_id = 215
ORDER BY b.entry_date, fs.created_at;

-- Check recent stock movements
SELECT 
    sm.movement_type,
    sm.quantity,
    sm.remaining_quantity,
    sm.reference_no,
    b.batch_reference,
    sm.movement_date
FROM tbl_stock_movements sm
INNER JOIN tbl_batch b ON sm.batch_id = b.batch_id
WHERE sm.product_id = 215
ORDER BY sm.movement_date DESC, sm.movement_id DESC
LIMIT 5;

-- ============================================================================
-- STEP 5: Check Final State
-- ============================================================================

SELECT '=== FINAL STATE VERIFICATION ===' as status;

-- Check product quantities in all locations
SELECT 
    p.product_id,
    p.product_name,
    l.location_name,
    p.quantity,
    p.stock_status
FROM tbl_product p
INNER JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.product_name = 'Nova'
ORDER BY l.location_name;

-- Check transfer log
SELECT 
    tl.transfer_id,
    tl.from_location,
    tl.to_location,
    tl.quantity,
    tl.transfer_date
FROM tbl_transfer_log tl
WHERE tl.product_id = 215
ORDER BY tl.created_at DESC;

-- Final inventory status
SELECT * FROM v_fifo_inventory_status 
WHERE product_name = 'Nova';

-- ============================================================================
-- STEP 6: Test Error Handling - Try to transfer more than available
-- ============================================================================

SELECT '=== TESTING ERROR HANDLING ===' as status;

-- Check current available stock
SELECT SUM(available_quantity) as total_available
FROM tbl_fifo_stock 
WHERE product_id = 215;

-- Try to transfer more than available (should fail)
INSERT INTO tbl_transfer_header (date, source_location_id, destination_location_id, employee_id, status)
VALUES (CURDATE(), 2, 3, 20, 'pending');

SET @transfer_id_3 = LAST_INSERT_ID();

INSERT INTO tbl_transfer_dtl (transfer_header_id, product_id, qty)
VALUES (@transfer_id_3, 215, 200); -- This should exceed available stock

-- This should generate an error
-- UPDATE tbl_transfer_header 
-- SET status = 'approved' 
-- WHERE transfer_header_id = @transfer_id_3;

SELECT 'Transfer of 200 units should fail due to insufficient stock' as note;

-- ============================================================================
-- STEP 7: Manual FIFO Processing Example
-- ============================================================================

SELECT '=== MANUAL FIFO PROCESSING EXAMPLE ===' as status;

-- Manually process a small adjustment
CALL ProcessFIFOTransfer(
    215,                -- product_id (Nova)
    5,                  -- quantity
    2,                  -- source_location_id (warehouse)
    9999,               -- dummy transfer_header_id
    'MANUAL-ADJ-001'    -- reference
);

-- Check the result
SELECT 
    sm.movement_type,
    sm.quantity,
    sm.reference_no,
    sm.notes,
    b.batch_reference
FROM tbl_stock_movements sm
INNER JOIN tbl_batch b ON sm.batch_id = b.batch_id
WHERE sm.product_id = 215 AND sm.reference_no = 'MANUAL-ADJ-001';

-- ============================================================================
-- STEP 8: Summary Report
-- ============================================================================

SELECT '=== FIFO SYSTEM TEST SUMMARY ===' as status;

SELECT 
    'Total Movements' as metric,
    COUNT(*) as value
FROM tbl_stock_movements 
WHERE product_id = 215

UNION ALL

SELECT 
    'Total Transfers' as metric,
    COUNT(*) as value
FROM tbl_transfer_log 
WHERE product_id = 215

UNION ALL

SELECT 
    'Remaining Stock (Warehouse)' as metric,
    COALESCE(quantity, 0) as value
FROM tbl_product 
WHERE product_id = 215 AND location_id = 2

UNION ALL

SELECT 
    'Current Available (FIFO)' as metric,
    COALESCE(SUM(available_quantity), 0) as value
FROM tbl_fifo_stock 
WHERE product_id = 215;

-- Show consumption timeline
SELECT 
    sm.movement_date,
    sm.movement_type,
    sm.quantity,
    sm.reference_no,
    b.batch_reference,
    sm.notes
FROM tbl_stock_movements sm
INNER JOIN tbl_batch b ON sm.batch_id = b.batch_id
WHERE sm.product_id = 215
ORDER BY sm.movement_date, sm.movement_id;

SELECT '=== TEST COMPLETED ===' as status;

-- ============================================================================
-- CLEANUP (Optional - uncomment if you want to reset for testing)
-- ============================================================================

/*
-- Reset the test data (uncomment to clean up)

-- Delete test transfers
DELETE FROM tbl_transfer_dtl WHERE transfer_header_id IN (@transfer_id_1, @transfer_id_2, @transfer_id_3);
DELETE FROM tbl_transfer_header WHERE transfer_header_id IN (@transfer_id_1, @transfer_id_2, @transfer_id_3);

-- Reset FIFO stock to original state
UPDATE tbl_fifo_stock 
SET available_quantity = quantity,
    updated_at = CURRENT_TIMESTAMP
WHERE product_id = 215;

-- Reset stock summary
UPDATE tbl_stock_summary 
SET available_quantity = total_quantity,
    last_updated = CURRENT_TIMESTAMP
WHERE product_id = 215;

-- Reset product quantity
UPDATE tbl_product 
SET quantity = 170,
    stock_status = 'in stock'
WHERE product_id = 215 AND location_id = 2;

-- Delete test movements (keep original ones)
DELETE FROM tbl_stock_movements 
WHERE product_id = 215 AND reference_no LIKE 'TRANSFER-%';

-- Delete test transfer logs
DELETE FROM tbl_transfer_log WHERE product_id = 215;

SELECT 'Test data cleaned up' as status;
*/
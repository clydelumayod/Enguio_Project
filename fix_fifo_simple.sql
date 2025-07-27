-- Fix FIFO Dates Display Issue
-- Run this script in phpMyAdmin to populate the stock summary table

-- Step 1: Check current state
SELECT 'Current Stock Summary Records' as status, COUNT(*) as count FROM tbl_stock_summary;

-- Step 2: Check products with batch information
SELECT 'Products with Batch Info' as status, COUNT(*) as count 
FROM tbl_product p 
JOIN tbl_batch b ON p.batch_id = b.batch_id 
WHERE p.status = 'active' AND p.batch_id IS NOT NULL;

-- Step 3: Show sample products with batch info
SELECT 
    p.product_id,
    p.product_name,
    p.quantity,
    b.batch_reference,
    b.entry_date,
    p.unit_price
FROM tbl_product p
JOIN tbl_batch b ON p.batch_id = b.batch_id
WHERE p.status = 'active' AND p.batch_id IS NOT NULL
ORDER BY p.product_id
LIMIT 5;

-- Step 4: Populate stock summary table (only if it's empty)
INSERT INTO tbl_stock_summary (
    product_id, 
    batch_id, 
    available_quantity, 
    reserved_quantity, 
    total_quantity, 
    unit_cost, 
    expiration_date, 
    batch_reference
)
SELECT 
    p.product_id,
    p.batch_id,
    p.quantity as available_quantity,
    0 as reserved_quantity,
    p.quantity as total_quantity,
    p.unit_price as unit_cost,
    p.expiration as expiration_date,
    b.batch_reference
FROM tbl_product p
JOIN tbl_batch b ON p.batch_id = b.batch_id
WHERE p.status = 'active' 
    AND p.batch_id IS NOT NULL
    AND p.quantity > 0
    AND NOT EXISTS (
        SELECT 1 FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id 
        AND ss.batch_id = p.batch_id
    );

-- Step 5: Verify the data was inserted
SELECT 'Records Inserted' as status, COUNT(*) as count FROM tbl_stock_summary;

-- Step 6: Test FIFO query for a sample product
SELECT 
    ss.summary_id,
    ss.batch_id,
    ss.batch_reference,
    ss.available_quantity,
    ss.unit_cost,
    ss.expiration_date,
    b.entry_date as batch_date,
    b.entry_time as batch_time,
    ROW_NUMBER() OVER (ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order,
    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry
FROM tbl_stock_summary ss
JOIN tbl_batch b ON ss.batch_id = b.batch_id
WHERE ss.product_id = (SELECT MIN(product_id) FROM tbl_product WHERE status = 'active' AND batch_id IS NOT NULL)
    AND ss.available_quantity > 0
ORDER BY b.entry_date ASC, ss.summary_id ASC
LIMIT 5; 
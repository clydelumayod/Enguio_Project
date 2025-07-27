-- Populate Stock Summary Table for FIFO Tracking
-- This script populates tbl_stock_summary with existing product data

-- First, clear any existing data (optional - uncomment if needed)
-- DELETE FROM tbl_stock_summary;

-- Insert stock summary records for all products with batch information
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

-- Show the results
SELECT 
    'Stock Summary Population Complete' as status,
    COUNT(*) as records_inserted
FROM tbl_stock_summary;

-- Verify the data
SELECT 
    ss.summary_id,
    p.product_name,
    ss.batch_reference,
    ss.available_quantity,
    ss.unit_cost,
    b.entry_date as batch_date,
    b.entry_time as batch_time
FROM tbl_stock_summary ss
JOIN tbl_product p ON ss.product_id = p.product_id
JOIN tbl_batch b ON ss.batch_id = b.batch_id
ORDER BY b.entry_date ASC
LIMIT 10; 
-- Fix FIFO Quantities for Product 217
-- Update the latest batch (FIFO ID 5) to have 110 units instead of 10

-- Show current state before fix
SELECT 
    'BEFORE FIX' as status,
    fifo_id,
    product_id,
    batch_reference,
    quantity as old_quantity,
    available_quantity as new_quantity,
    unit_cost,
    expiration_date,
    entry_date
FROM tbl_fifo_stock 
WHERE product_id = 217 
ORDER BY entry_date ASC, fifo_id ASC;

-- Calculate current total
SELECT 
    'CURRENT TOTAL' as info,
    SUM(quantity) as total_old_quantity,
    SUM(available_quantity) as total_new_quantity
FROM tbl_fifo_stock 
WHERE product_id = 217;

-- Update the latest batch (FIFO ID 5) to add 100 units
-- This will make the total 120 instead of 20
UPDATE tbl_fifo_stock 
SET 
    quantity = quantity + 100,
    available_quantity = available_quantity + 100
WHERE fifo_id = 5 AND product_id = 217;

-- Show updated state after fix
SELECT 
    'AFTER FIX' as status,
    fifo_id,
    product_id,
    batch_reference,
    quantity as old_quantity,
    available_quantity as new_quantity,
    unit_cost,
    expiration_date,
    entry_date
FROM tbl_fifo_stock 
WHERE product_id = 217 
ORDER BY entry_date ASC, fifo_id ASC;

-- Calculate updated total
SELECT 
    'UPDATED TOTAL' as info,
    SUM(quantity) as total_old_quantity,
    SUM(available_quantity) as total_new_quantity
FROM tbl_fifo_stock 
WHERE product_id = 217;

-- Update product total quantity to match FIFO total
UPDATE tbl_product 
SET quantity = (
    SELECT SUM(available_quantity) 
    FROM tbl_fifo_stock 
    WHERE product_id = 217
)
WHERE product_id = 217;

-- Verify product quantity is updated
SELECT 
    'PRODUCT VERIFICATION' as info,
    product_id,
    product_name,
    quantity as total_quantity
FROM tbl_product 
WHERE product_id = 217;

-- Show final summary
SELECT 
    'FINAL SUMMARY' as info,
    'Product 217 FIFO quantities updated successfully' as message,
    'Total quantity is now 120 units' as result; 
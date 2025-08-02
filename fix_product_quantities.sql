-- Fix Product Quantities Script
-- This script adds a new quantity column and fixes the existing quantity issues

-- Step 1: Add new quantity column
ALTER TABLE tbl_product ADD COLUMN current_quantity INT(11) DEFAULT 0 AFTER quantity;

-- Step 2: Populate current_quantity from stock_summary
UPDATE tbl_product p 
SET p.current_quantity = (
    SELECT COALESCE(SUM(ss.available_quantity), 0)
    FROM tbl_stock_summary ss 
    WHERE ss.product_id = p.product_id
);

-- Step 3: Update the main quantity column with current_quantity values
UPDATE tbl_product SET quantity = current_quantity WHERE current_quantity > 0;

-- Step 4: Update stock_status based on quantity
UPDATE tbl_product 
SET stock_status = CASE 
    WHEN quantity = 0 THEN 'out of stock'
    WHEN quantity <= 10 THEN 'low stock'
    ELSE 'in stock'
END;

-- Step 5: Show verification query (run this to check results)
-- SELECT 
--     product_id, 
--     product_name, 
--     quantity, 
--     current_quantity, 
--     stock_status,
--     location_id
-- FROM tbl_product 
-- ORDER BY product_id 
-- LIMIT 10;

-- Step 6: Show summary statistics (run this to see overall results)
-- SELECT 
--     COUNT(*) as total_products,
--     COUNT(CASE WHEN quantity > 0 THEN 1 END) as products_with_stock,
--     COUNT(CASE WHEN quantity = 0 THEN 1 END) as products_without_stock,
--     COUNT(CASE WHEN stock_status = 'in stock' THEN 1 END) as in_stock,
--     COUNT(CASE WHEN stock_status = 'low stock' THEN 1 END) as low_stock,
--     COUNT(CASE WHEN stock_status = 'out of stock' THEN 1 END) as out_of_stock
-- FROM tbl_product; 
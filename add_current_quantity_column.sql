-- Add current_quantity column to tbl_product table
-- This will store the newly added quantities separately from the main quantity field

-- Step 1: Add current_quantity column
ALTER TABLE tbl_product ADD COLUMN current_quantity INT(11) DEFAULT 0 AFTER quantity;

-- Step 2: Initialize current_quantity with existing quantity values
UPDATE tbl_product SET current_quantity = quantity WHERE quantity > 0;

-- Step 3: Show the updated table structure
DESCRIBE tbl_product;

-- Step 4: Show sample data to verify
SELECT 
    product_id, 
    product_name, 
    quantity, 
    current_quantity, 
    stock_status
FROM tbl_product 
ORDER BY product_id 
LIMIT 10; 
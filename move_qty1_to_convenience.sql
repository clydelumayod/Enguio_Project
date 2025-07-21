-- Move products with quantity = 1 from warehouse to convenience store
-- This script will fix the issue where products with qty=1 are still in warehouse

-- First, let's see what products have quantity = 1 in warehouse
SELECT 
    p.product_id, p.product_name, p.quantity, p.barcode, p.location_id,
    l.location_name
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.quantity = 1 AND p.location_id = 1
ORDER BY p.product_name;

-- Check if these products already exist in convenience store
SELECT 
    p.product_id, p.product_name, p.quantity, p.barcode, p.location_id,
    l.location_name
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.quantity = 1 AND p.location_id = 2
ORDER BY p.product_name;

-- Move products with qty=1 from warehouse to convenience store
-- For each product in warehouse with qty=1:
-- 1. Check if it exists in convenience store
-- 2. If exists, update quantity
-- 3. If not exists, create new entry
-- 4. Remove from warehouse

-- Example for one product (replace product_id with actual ID):
-- UPDATE tbl_product SET location_id = 2 WHERE product_id = [ACTUAL_PRODUCT_ID];

-- Or use this to move all products with qty=1:
UPDATE tbl_product 
SET location_id = 2 
WHERE quantity = 1 AND location_id = 1;

-- Verify the move
SELECT 
    p.product_id, p.product_name, p.quantity, p.barcode, p.location_id,
    l.location_name
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.quantity = 1
ORDER BY p.location_id, p.product_name; 
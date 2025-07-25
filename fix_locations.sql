-- Fix product locations in the database
-- This script will update products to have the correct location_id

-- First, let's see what locations exist
SELECT * FROM tbl_location ORDER BY location_id;

-- Check current product locations
SELECT 
    p.location_id,
    l.location_name,
    COUNT(*) as product_count
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
GROUP BY p.location_id, l.location_name
ORDER BY p.location_id;

-- Update products with NULL location_id to warehouse (location_id = 1)
UPDATE tbl_product 
SET location_id = 1 
WHERE location_id IS NULL;

-- Update products with invalid location_id to warehouse (location_id = 1)
UPDATE tbl_product 
SET location_id = 1 
WHERE location_id NOT IN (SELECT location_id FROM tbl_location);

-- Verify the fix
SELECT 
    p.location_id,
    l.location_name,
    COUNT(*) as product_count
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
GROUP BY p.location_id, l.location_name
ORDER BY p.location_id;

-- Show sample products in warehouse
SELECT product_id, product_name, quantity, barcode, location_id 
FROM tbl_product 
WHERE location_id = 1 
LIMIT 5; 
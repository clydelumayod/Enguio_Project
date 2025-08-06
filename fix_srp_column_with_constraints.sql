-- Fix SRP column issue with foreign key constraints
-- This script addresses the "Column not found: 1054 Unknown column 'srp'" error
-- and the foreign key constraint violation

-- Step 1: Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Step 2: Add SRP column to tbl_product table
ALTER TABLE `tbl_product` 
ADD COLUMN `srp` DECIMAL(10,2) DEFAULT NULL 
COMMENT 'Suggested Retail Price' 
AFTER `unit_price`;

-- Step 3: Update existing products to set SRP equal to unit_price if SRP is NULL
UPDATE `tbl_product` 
SET `srp` = `unit_price` 
WHERE `srp` IS NULL;

-- Step 4: Fix orphaned brand_id references by setting them to NULL
-- This handles products that reference non-existent brands
UPDATE `tbl_product` 
SET `brand_id` = NULL 
WHERE `brand_id` IS NOT NULL 
AND `brand_id` NOT IN (SELECT `brand_id` FROM `tbl_brand`);

-- Step 5: Fix orphaned supplier_id references by setting them to NULL
-- This handles products that reference non-existent suppliers
UPDATE `tbl_product` 
SET `supplier_id` = NULL 
WHERE `supplier_id` IS NOT NULL 
AND `supplier_id` NOT IN (SELECT `supplier_id` FROM `tbl_supplier`);

-- Step 6: Fix orphaned location_id references by setting them to NULL
-- This handles products that reference non-existent locations
UPDATE `tbl_product` 
SET `location_id` = NULL 
WHERE `location_id` IS NOT NULL 
AND `location_id` NOT IN (SELECT `location_id` FROM `tbl_location`);

-- Step 7: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 8: Verify the changes
SELECT 
    'SRP column added successfully' as status,
    COUNT(*) as total_products,
    COUNT(CASE WHEN srp IS NOT NULL THEN 1 END) as products_with_srp,
    COUNT(CASE WHEN srp IS NULL THEN 1 END) as products_without_srp
FROM `tbl_product`; 
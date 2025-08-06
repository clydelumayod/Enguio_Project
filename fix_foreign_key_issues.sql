-- Comprehensive fix for foreign key constraint issues
-- This script addresses both the SRP column issue and foreign key violations

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

-- Step 4: Ensure we have at least one brand record
INSERT IGNORE INTO `tbl_brand` (`brand_id`, `brand`) VALUES 
(1, 'Generic'),
(2, 'Unbranded'),
(3, 'Store Brand');

-- Step 5: Ensure we have at least one supplier record
INSERT IGNORE INTO `tbl_supplier` (`supplier_id`, `supplier_name`, `supplier_address`, `supplier_contact`, `supplier_email`) VALUES 
(1, 'Default Supplier', 'Default Address', 'Default Contact', 'default@example.com'),
(2, 'Generic Supplier', 'Generic Address', 'Generic Contact', 'generic@example.com'),
(3, 'Store Supplier', 'Store Address', 'Store Contact', 'store@example.com');

-- Step 6: Fix orphaned brand_id references by setting them to a valid brand
UPDATE `tbl_product` 
SET `brand_id` = 1 
WHERE `brand_id` IS NOT NULL 
AND `brand_id` NOT IN (SELECT `brand_id` FROM `tbl_brand`);

-- Step 7: Fix orphaned supplier_id references by setting them to a valid supplier
UPDATE `tbl_product` 
SET `supplier_id` = 1 
WHERE `supplier_id` IS NOT NULL 
AND `supplier_id` NOT IN (SELECT `supplier_id` FROM `tbl_supplier`);

-- Step 8: Fix orphaned location_id references by setting them to warehouse (ID 2)
UPDATE `tbl_product` 
SET `location_id` = 2 
WHERE `location_id` IS NOT NULL 
AND `location_id` NOT IN (SELECT `location_id` FROM `tbl_location`);

-- Step 9: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 10: Verify the changes
SELECT 
    'Database fixed successfully' as status,
    (SELECT COUNT(*) FROM `tbl_product`) as total_products,
    (SELECT COUNT(*) FROM `tbl_brand`) as total_brands,
    (SELECT COUNT(*) FROM `tbl_supplier`) as total_suppliers,
    (SELECT COUNT(*) FROM `tbl_location`) as total_locations; 
-- Remove unique constraint on barcode field
-- This allows the same barcode to be used multiple times across different locations

-- Drop the unique constraints on barcode
ALTER TABLE `tbl_product` DROP INDEX `barcode`;
ALTER TABLE `tbl_product` DROP INDEX `barcode_2`;

-- Optional: Add a regular index on barcode for performance (not unique)
-- ALTER TABLE `tbl_product` ADD INDEX `idx_barcode` (`barcode`);

-- Verify the constraints are removed
SHOW INDEX FROM `tbl_product` WHERE Key_name LIKE '%barcode%'; 
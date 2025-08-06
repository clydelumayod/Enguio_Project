-- Add SRP (Suggested Retail Price) column to tbl_product table
-- This fixes the "Column not found: 1054 Unknown column 'srp' in 'field list'" error

ALTER TABLE `tbl_product` 
ADD COLUMN `srp` DECIMAL(10,2) DEFAULT NULL 
COMMENT 'Suggested Retail Price' 
AFTER `unit_price`;

-- Update existing products to set SRP equal to unit_price if SRP is NULL
UPDATE `tbl_product` 
SET `srp` = `unit_price` 
WHERE `srp` IS NULL; 
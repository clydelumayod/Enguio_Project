-- Simple script to add SRP column
-- Disable foreign key checks temporarily to avoid constraint issues

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tbl_product` 
ADD COLUMN `srp` DECIMAL(10,2) DEFAULT NULL 
COMMENT 'Suggested Retail Price' 
AFTER `unit_price`;

UPDATE `tbl_product` 
SET `srp` = `unit_price` 
WHERE `srp` IS NULL;

SET FOREIGN_KEY_CHECKS = 1; 
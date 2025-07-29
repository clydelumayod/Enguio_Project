-- Add SRP (Suggested Retail Price) column to tbl_product table
ALTER TABLE `tbl_product` ADD COLUMN `srp` DECIMAL(10,2) DEFAULT NULL AFTER `unit_price`;

-- Update existing products to have SRP equal to unit_price initially
UPDATE `tbl_product` SET `srp` = `unit_price` WHERE `srp` IS NULL; 
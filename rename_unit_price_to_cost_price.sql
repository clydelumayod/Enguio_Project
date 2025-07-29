-- Rename unit_price column to cost_price in tbl_product table
ALTER TABLE `tbl_product` CHANGE `unit_price` `cost_price` DECIMAL(10,2) NOT NULL;

-- Update any references in other tables if needed
-- Note: This will need to be updated in the application code as well 
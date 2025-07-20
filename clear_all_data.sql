-- Clear all data from enguio database tables
-- This script will delete all records but preserve table structure

-- Disable foreign key checks temporarily to avoid constraint issues
SET FOREIGN_KEY_CHECKS = 0;

-- Clear data from all tables
DELETE FROM tbl_transfer_log;
DELETE FROM tbl_transfer_dtl;
DELETE FROM tbl_transfer_header;
DELETE FROM tbl_purchase_return_dtl;
DELETE FROM tbl_purchase_return_header;
DELETE FROM tbl_purchase_order_dtl;
DELETE FROM tbl_purchase_order_header;
DELETE FROM tbl_pos_sales_details;
DELETE FROM tbl_pos_sales_header;
DELETE FROM tbl_pos_transaction;
DELETE FROM tbl_pos_terminal;
DELETE FROM tbl_adjustment_details;
DELETE FROM tbl_adjustment_header;
DELETE FROM tbl_product;
DELETE FROM tbl_batch;
DELETE FROM tbl_supplier;
DELETE FROM tbl_employee;
DELETE FROM tbl_brand;
DELETE FROM tbl_discount;

-- Reset auto-increment counters
ALTER TABLE tbl_transfer_log AUTO_INCREMENT = 1;
ALTER TABLE tbl_transfer_dtl AUTO_INCREMENT = 1;
ALTER TABLE tbl_transfer_header AUTO_INCREMENT = 1;
ALTER TABLE tbl_purchase_return_dtl AUTO_INCREMENT = 1;
ALTER TABLE tbl_purchase_return_header AUTO_INCREMENT = 1;
ALTER TABLE tbl_purchase_order_dtl AUTO_INCREMENT = 1;
ALTER TABLE tbl_purchase_order_header AUTO_INCREMENT = 1;
ALTER TABLE tbl_pos_sales_details AUTO_INCREMENT = 1;
ALTER TABLE tbl_pos_sales_header AUTO_INCREMENT = 1;
ALTER TABLE tbl_pos_transaction AUTO_INCREMENT = 1;
ALTER TABLE tbl_pos_terminal AUTO_INCREMENT = 1;
ALTER TABLE tbl_adjustment_details AUTO_INCREMENT = 1;
ALTER TABLE tbl_adjustment_header AUTO_INCREMENT = 1;
ALTER TABLE tbl_product AUTO_INCREMENT = 1;
ALTER TABLE tbl_batch AUTO_INCREMENT = 1;
ALTER TABLE tbl_supplier AUTO_INCREMENT = 1;
ALTER TABLE tbl_employee AUTO_INCREMENT = 1;
ALTER TABLE tbl_brand AUTO_INCREMENT = 1;
ALTER TABLE tbl_discount AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Display confirmation
SELECT 'All data has been cleared from the database. Table structures are preserved.' AS Status; 
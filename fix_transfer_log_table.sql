-- Fix tbl_transfer_log table structure for enhanced FIFO transfer system
USE enguio2;

-- Add missing columns to tbl_transfer_log
ALTER TABLE tbl_transfer_log 
ADD COLUMN IF NOT EXISTS from_location VARCHAR(100) AFTER product_name,
ADD COLUMN IF NOT EXISTS to_location VARCHAR(100) AFTER from_location,
ADD COLUMN IF NOT EXISTS quantity INT(11) AFTER to_location,
ADD COLUMN IF NOT EXISTS transfer_date DATE AFTER quantity;

-- Show the updated table structure
DESCRIBE tbl_transfer_log; 
-- SQL script to add missing columns for enhanced FIFO transfer system
-- Run this in your MySQL database if you encounter column not found errors

USE enguio2;

-- Check if product_name column exists in tbl_transfer_log, add if missing
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'enguio2' 
     AND TABLE_NAME = 'tbl_transfer_log' 
     AND COLUMN_NAME = 'product_name') = 0,
    'ALTER TABLE tbl_transfer_log ADD COLUMN product_name VARCHAR(255) AFTER product_id',
    'SELECT "product_name column already exists in tbl_transfer_log" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if created_at column exists in tbl_transfer_log, add if missing
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'enguio2' 
     AND TABLE_NAME = 'tbl_transfer_log' 
     AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE tbl_transfer_log ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "created_at column already exists in tbl_transfer_log" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if delivery_date column exists in tbl_transfer_header, add if missing
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'enguio2' 
     AND TABLE_NAME = 'tbl_transfer_header' 
     AND COLUMN_NAME = 'delivery_date') = 0,
    'ALTER TABLE tbl_transfer_header ADD COLUMN delivery_date DATE AFTER date',
    'SELECT "delivery_date column already exists in tbl_transfer_header" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show current table structures
SELECT 'tbl_transfer_log structure:' as info;
DESCRIBE tbl_transfer_log;

SELECT 'tbl_transfer_header structure:' as info;
DESCRIBE tbl_transfer_header;

SELECT 'tbl_transfer_dtl structure:' as info;
DESCRIBE tbl_transfer_dtl; 
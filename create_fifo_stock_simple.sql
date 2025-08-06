-- ============================================================================
-- CREATE tbl_fifo_stock TABLE (Simplified Version)
-- This table tracks FIFO (First-In, First-Out) stock per batch
-- ============================================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Create the tbl_fifo_stock table
CREATE TABLE IF NOT EXISTS `tbl_fifo_stock` (
    `fifo_id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `batch_id` INT(11) NOT NULL,
    `batch_reference` VARCHAR(100) DEFAULT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `available_quantity` INT(11) NOT NULL DEFAULT 0,
    `unit_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `expiration_date` DATE DEFAULT NULL,
    `entry_date` DATE NOT NULL,
    `entry_by` VARCHAR(100) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`fifo_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_batch_id` (`batch_id`),
    KEY `idx_expiration_date` (`expiration_date`),
    KEY `idx_entry_date` (`entry_date`),
    CONSTRAINT `fk_fifo_stock_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_fifo_stock_batch` FOREIGN KEY (`batch_id`) REFERENCES `tbl_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Simple verification query
SHOW TABLES LIKE 'tbl_fifo_stock'; 
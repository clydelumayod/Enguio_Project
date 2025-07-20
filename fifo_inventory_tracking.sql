-- FIFO Inventory Tracking System
-- This creates the necessary tables for proper FIFO implementation

-- Table to track individual stock movements (FIFO tracking)
CREATE TABLE `tbl_stock_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'admin',
  PRIMARY KEY (`movement_id`),
  KEY `idx_product_batch` (`product_id`, `batch_id`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_expiration` (`expiration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track current stock levels by batch (FIFO summary)
CREATE TABLE `tbl_stock_summary` (
  `summary_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `batch_reference` varchar(50) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `unique_product_batch` (`product_id`, `batch_id`),
  KEY `idx_product_expiration` (`product_id`, `expiration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes to existing tables for better FIFO performance
ALTER TABLE `tbl_product` ADD INDEX `idx_barcode_status` (`barcode`, `status`);
ALTER TABLE `tbl_batch` ADD INDEX `idx_entry_date` (`entry_date`);

-- Create a view for FIFO stock levels (oldest first)
CREATE VIEW `v_fifo_stock` AS
SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    ss.batch_id,
    ss.batch_reference,
    ss.available_quantity,
    ss.unit_cost,
    ss.expiration_date,
    b.entry_date as batch_date,
    ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY b.entry_date ASC, ss.summary_id ASC) as fifo_order
FROM tbl_product p
JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
JOIN tbl_batch b ON ss.batch_id = b.batch_id
WHERE p.status = 'active' AND ss.available_quantity > 0
ORDER BY p.product_id, fifo_order;

-- Create a view for expiring products (FIFO order)
CREATE VIEW `v_expiring_products` AS
SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    ss.available_quantity,
    ss.expiration_date,
    DATEDIFF(ss.expiration_date, CURDATE()) as days_until_expiry,
    ss.batch_reference,
    b.entry_date as batch_date
FROM tbl_product p
JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
JOIN tbl_batch b ON ss.batch_id = b.batch_id
WHERE p.status = 'active' 
    AND ss.available_quantity > 0 
    AND ss.expiration_date IS NOT NULL
    AND ss.expiration_date >= CURDATE()
ORDER BY ss.expiration_date ASC, b.entry_date ASC; 
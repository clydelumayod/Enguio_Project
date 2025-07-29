-- Create tbl_fifo_stock table for FIFO tracking
-- This table is referenced in the backend code but doesn't exist

CREATE TABLE `tbl_fifo_stock` (
  `fifo_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `batch_reference` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expiration_date` date DEFAULT NULL,
  `entry_date` date NOT NULL,
  `entry_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`fifo_id`),
  KEY `idx_product_batch` (`product_id`, `batch_id`),
  KEY `idx_expiration` (`expiration_date`),
  KEY `idx_entry_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE `tbl_fifo_stock`
  ADD CONSTRAINT `fk_fifo_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fifo_batch` FOREIGN KEY (`batch_id`) REFERENCES `tbl_batch` (`batch_id`) ON DELETE CASCADE;

-- Create index for better performance
CREATE INDEX `idx_fifo_product_available` ON `tbl_fifo_stock` (`product_id`, `available_quantity`); 
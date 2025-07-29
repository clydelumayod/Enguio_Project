-- Create tbl_stock_consumption table for tracking stock consumption
CREATE TABLE `tbl_stock_consumption` (
  `consumption_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'admin',
  `consumed_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`consumption_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_consumed_date` (`consumed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint
ALTER TABLE `tbl_stock_consumption`
  ADD CONSTRAINT `fk_consumption_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE; 
-- Create the missing tbl_location table
-- This table is referenced throughout the codebase but doesn't exist

CREATE TABLE `tbl_location` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) NOT NULL,
  `location_type` enum('warehouse','store','pharmacy','office','other') DEFAULT 'other',
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `location_name` (`location_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some default locations
INSERT INTO `tbl_location` (`location_name`, `location_type`, `address`, `status`) VALUES
('Main Warehouse', 'warehouse', '123 Main Street, City', 'active'),
('Convenience Store', 'store', '456 Retail Avenue, City', 'active'),
('Pharmacy Branch', 'pharmacy', '789 Health Road, City', 'active'),
('Office Location', 'office', '321 Business Blvd, City', 'active');

-- Add foreign key constraints to existing tables that reference location_id
-- Note: These will only work if the referenced tables exist

-- Add location_id column to tbl_product if it doesn't exist
ALTER TABLE `tbl_product` 
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `supplier_id`,
ADD KEY `fk_product_location` (`location_id`),
ADD CONSTRAINT `fk_product_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON DELETE SET NULL;

-- Add location_id column to tbl_brand if it doesn't exist
ALTER TABLE `tbl_brand` 
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `brand`,
ADD KEY `fk_brand_location` (`location_id`),
ADD CONSTRAINT `fk_brand_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON DELETE SET NULL;

-- Add location_id column to tbl_notification if it doesn't exist
ALTER TABLE `tbl_notification` 
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `notification_id`,
ADD KEY `fk_notification_location` (`location_id`),
ADD CONSTRAINT `fk_notification_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON DELETE SET NULL; 
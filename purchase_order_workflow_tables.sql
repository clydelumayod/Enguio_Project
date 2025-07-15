-- Additional tables for complete Purchase Order workflow

-- Table for tracking P.O. delivery status
CREATE TABLE `tbl_purchase_order_delivery` (
  `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_header_id` int(11) NOT NULL,
  `expected_delivery_date` date NOT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `delivery_status` enum('pending','in_transit','delivered','partial','cancelled') DEFAULT 'pending',
  `delivery_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`delivery_id`),
  KEY `fk_delivery_purchase_header` (`purchase_header_id`),
  CONSTRAINT `fk_delivery_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for receiving items from P.O.
CREATE TABLE `tbl_purchase_receiving_header` (
  `receiving_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_header_id` int(11) NOT NULL,
  `receiving_date` date NOT NULL,
  `receiving_time` time NOT NULL,
  `received_by` int(11) NOT NULL,
  `delivery_receipt_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','partial') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receiving_id`),
  KEY `fk_receiving_purchase_header` (`purchase_header_id`),
  KEY `fk_receiving_employee` (`received_by`),
  CONSTRAINT `fk_receiving_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`),
  CONSTRAINT `fk_receiving_employee` FOREIGN KEY (`received_by`) REFERENCES `tbl_employee` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for receiving details
CREATE TABLE `tbl_purchase_receiving_dtl` (
  `receiving_dtl_id` int(11) NOT NULL AUTO_INCREMENT,
  `receiving_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `ordered_qty` int(11) NOT NULL,
  `received_qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`receiving_dtl_id`),
  KEY `fk_receiving_dtl_header` (`receiving_id`),
  KEY `fk_receiving_dtl_product` (`product_id`),
  CONSTRAINT `fk_receiving_dtl_header` FOREIGN KEY (`receiving_id`) REFERENCES `tbl_purchase_receiving_header` (`receiving_id`),
  CONSTRAINT `fk_receiving_dtl_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for P.O. approval workflow
CREATE TABLE `tbl_purchase_order_approval` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_header_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`approval_id`),
  KEY `fk_approval_purchase_header` (`purchase_header_id`),
  KEY `fk_approval_employee` (`approved_by`),
  CONSTRAINT `fk_approval_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`),
  CONSTRAINT `fk_approval_employee` FOREIGN KEY (`approved_by`) REFERENCES `tbl_employee` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing purchase order header table to include more fields
ALTER TABLE `tbl_purchase_order_header` 
ADD COLUMN `po_number` varchar(50) DEFAULT NULL AFTER `purchase_header_id`,
ADD COLUMN `expected_delivery_date` date DEFAULT NULL AFTER `total_amount`,
ADD COLUMN `created_by` int(11) DEFAULT NULL AFTER `status`,
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `created_by`,
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`,
ADD UNIQUE KEY `po_number` (`po_number`),
ADD KEY `fk_po_created_by` (`created_by`),
ADD CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_employee` (`emp_id`);

-- Update existing purchase order detail table to include more fields
ALTER TABLE `tbl_purchase_order_dtl` 
ADD COLUMN `unit_price` decimal(10,2) NOT NULL AFTER `price`,
ADD COLUMN `total_amount` decimal(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED AFTER `unit_price`;

-- Add indexes for better performance
ALTER TABLE `tbl_purchase_order_header` ADD INDEX `idx_po_status` (`status`);
ALTER TABLE `tbl_purchase_order_header` ADD INDEX `idx_po_date` (`date`);
ALTER TABLE `tbl_purchase_order_header` ADD INDEX `idx_po_supplier` (`supplier_id`); 
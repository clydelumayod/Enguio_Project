-- Create archive table to store all archived items
CREATE TABLE IF NOT EXISTS `tbl_archive` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `item_type` enum('Product','Category','Supplier') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text,
  `category` varchar(255),
  `archived_by` varchar(100) NOT NULL,
  `archived_date` date NOT NULL,
  `archived_time` time NOT NULL,
  `reason` text,
  `status` enum('Archived','Deleted','Restored') DEFAULT 'Archived',
  `original_data` json,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`archive_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_archived_date` (`archived_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 
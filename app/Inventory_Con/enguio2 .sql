-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 04:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `enguio2`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_adjustment_details`
--

CREATE TABLE `tbl_adjustment_details` (
  `adjustment_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `adjustment_type` enum('add','replace','return') NOT NULL,
  `employee_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_adjustment_header`
--

CREATE TABLE `tbl_adjustment_header` (
  `adjustment_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_archive`
--

CREATE TABLE `tbl_archive` (
  `archive_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('Product','Category','Supplier') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `archived_by` varchar(100) NOT NULL,
  `archived_date` date NOT NULL,
  `archived_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Archived','Deleted','Restored') DEFAULT 'Archived',
  `original_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`original_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_archive`
--

INSERT INTO `tbl_archive` (`archive_id`, `item_id`, `item_type`, `item_name`, `item_description`, `category`, `archived_by`, `archived_date`, `archived_time`, `reason`, `status`, `original_data`, `created_at`) VALUES
(1, 169, 'Product', 'Corned Beef', 'Canned beef product', 'Processed Foods', 'test_user', '2025-07-26', '02:09:33', 'Test archive from integration test', 'Restored', '{\"product_id\":169,\"product_name\":\"Corned Beef\",\"category\":\"Processed Foods\",\"barcode\":1000000000001,\"description\":\"Canned beef product\",\"prescription\":\"0\",\"bulk\":0,\"expiration\":\"2026-07-21\",\"quantity\":50,\"unit_price\":\"60.00\",\"brand_id\":1,\"supplier_id\":13,\"location_id\":2,\"batch_id\":35,\"status\":\"\",\"Variation\":\"\",\"stock_status\":\"In Stock\",\"date_added\":\"2025-07-21\"}', '2025-07-25 18:09:33'),
(2, 205, 'Product', 'Amoxicillin 500mg', 'Antibiotic capsules', 'Medicine (OTC, prescription)', 'admin', '2025-07-26', '02:10:22', 'Archived from warehouse management', 'Archived', '{\"product_id\":205,\"product_name\":\"Amoxicillin 500mg\",\"category\":\"Medicine (OTC, prescription)\",\"barcode\":1000000000021,\"description\":\"Antibiotic capsules\",\"prescription\":\"1\",\"bulk\":0,\"expiration\":\"2026-07-21\",\"quantity\":50,\"unit_price\":\"18.00\",\"brand_id\":12,\"supplier_id\":12,\"location_id\":3,\"batch_id\":55,\"status\":\"active\",\"Variation\":\"\",\"stock_status\":\"in stock\",\"date_added\":\"2025-07-21\"}', '2025-07-25 18:10:22'),
(3, 169, 'Product', 'Corned Beef', 'Canned beef product', 'Processed Foods', 'test_user', '2025-07-26', '02:21:25', 'Test archive workflow', 'Restored', '{\"product_id\":169,\"product_name\":\"Corned Beef\",\"category\":\"Processed Foods\",\"barcode\":1000000000001,\"description\":\"Canned beef product\",\"prescription\":\"0\",\"bulk\":0,\"expiration\":\"2026-07-21\",\"quantity\":50,\"unit_price\":\"60.00\",\"brand_id\":1,\"supplier_id\":13,\"location_id\":2,\"batch_id\":35,\"status\":\"active\",\"Variation\":\"\",\"stock_status\":\"In Stock\",\"date_added\":\"2025-07-21\"}', '2025-07-25 18:21:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_batch`
--

CREATE TABLE `tbl_batch` (
  `batch_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `batch` varchar(255) DEFAULT NULL,
  `batch_reference` varchar(50) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `entry_time` time DEFAULT NULL,
  `entry_by` varchar(100) DEFAULT NULL,
  `order_no` varchar(50) DEFAULT NULL,
  `order_ref` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_batch`
--

INSERT INTO `tbl_batch` (`batch_id`, `date`, `time`, `batch`, `batch_reference`, `supplier_id`, `location_id`, `entry_date`, `entry_time`, `entry_by`, `order_no`, `order_ref`) VALUES
(5, '0000-00-00', '2025-08-04 09:42:42', 'BR-20250804-173108', NULL, 3, 2, '2025-08-04', '17:42:42', 'admin', '24', NULL),
(6, '0000-00-00', '2025-08-04 14:26:55', 'BR-20250804-222655', NULL, 3, 2, '2025-08-04', '22:26:55', 'admin', '', NULL),
(7, '0000-00-00', '2025-08-04 14:27:52', 'BR-20250804-222752', NULL, 3, 2, '2025-08-04', '22:27:52', 'admin', '', NULL),
(8, '0000-00-00', '2025-08-04 15:17:53', 'BR-20250804-231634', NULL, 2, 2, '2025-08-04', '23:17:53', 'admin', '24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_brand`
--

CREATE TABLE `tbl_brand` (
  `brand_id` int(11) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_brand`
--

INSERT INTO `tbl_brand` (`brand_id`, `brand`, `category_id`) VALUES
(1, 'Generic', NULL),
(2, 'Unbranded', NULL),
(3, 'Store Brand', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_category`
--

CREATE TABLE `tbl_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_category`
--

INSERT INTO `tbl_category` (`category_id`, `category_name`) VALUES
(9, 'Processed Foods'),
(10, 'Dairy'),
(11, 'Beverages'),
(12, 'Vitamins & Supplements'),
(13, 'Medicine (OTC, prescription)'),
(14, 'Toiletries'),
(15, 'Skincare & Cosmetics');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_discount`
--

CREATE TABLE `tbl_discount` (
  `discount_id` int(11) NOT NULL,
  `discount_rate` float NOT NULL,
  `discount_type` enum('PWD','SENIOR') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_employee`
--

CREATE TABLE `tbl_employee` (
  `emp_id` int(11) NOT NULL,
  `Fname` varchar(255) NOT NULL,
  `Mname` varchar(255) DEFAULT NULL,
  `Lname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_num` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(10) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_employee`
--

INSERT INTO `tbl_employee` (`emp_id`, `Fname`, `Mname`, `Lname`, `email`, `contact_num`, `role_id`, `shift_id`, `username`, `password`, `age`, `address`, `status`, `gender`, `birthdate`) VALUES
(1, 'ezay', 'bautista', 'Gutierrez', 'ten@gmail.com', '09788878787', 4, NULL, 'ezay', '$2y$10$Sd7GJ3LLwQyJMJyMhje1I.COnChfaX5gix.y6MxPq5k44pQB01Vke', 21, 'Opol mis.or', 'Active', '', '2004-10-08');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_fifo_stock`
--

CREATE TABLE `tbl_fifo_stock` (
  `fifo_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `batch_reference` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expiration_date` date DEFAULT NULL,
  `entry_date` date NOT NULL,
  `entry_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_fifo_stock`
--

INSERT INTO `tbl_fifo_stock` (`fifo_id`, `product_id`, `batch_id`, `batch_reference`, `quantity`, `available_quantity`, `unit_cost`, `expiration_date`, `entry_date`, `entry_by`, `created_at`, `updated_at`) VALUES
(1, 3, 6, 'BR-20250804-222655', 20, 20, 50.00, '2029-12-04', '2025-08-04', 'admin', '2025-08-04 14:26:55', '2025-08-04 14:26:55'),
(2, 3, 7, 'BR-20250804-222752', 100, 100, 40.00, '2030-12-04', '2025-08-04', 'admin', '2025-08-04 14:27:52', '2025-08-04 14:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_location`
--

CREATE TABLE `tbl_location` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_location`
--

INSERT INTO `tbl_location` (`location_id`, `location_name`, `status`) VALUES
(2, 'warehouse', 'active'),
(3, 'Pharmacy', 'active'),
(4, 'Convenience', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_sales_details`
--

CREATE TABLE `tbl_pos_sales_details` (
  `sales_details_id` int(11) NOT NULL,
  `sales_header_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_sales_header`
--

CREATE TABLE `tbl_pos_sales_header` (
  `sales_header_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `total_amount` float NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `terminal_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_terminal`
--

CREATE TABLE `tbl_pos_terminal` (
  `terminal_id` int(11) NOT NULL,
  `terminal_name` varchar(255) NOT NULL,
  `shift_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pos_transaction`
--

CREATE TABLE `tbl_pos_transaction` (
  `transaction_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `emp_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_type` enum('cash','card','Gcash') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `barcode` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prescription` varchar(1) DEFAULT NULL,
  `bulk` tinyint(1) DEFAULT 0,
  `expiration` date DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `srp` decimal(10,2) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `Variation` varchar(255) DEFAULT NULL,
  `stock_status` varchar(20) DEFAULT 'in stock',
  `date_added` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `srp`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`, `date_added`, `created_at`) VALUES
(3, 'Century Tuna Flakes in Oil', 'Processed Foods', 12345678905, 'Can foods', '0', 1, '2030-12-04', 55, 50.00, 55.00, 1, 3, 2, 7, 'active', '', 'in stock', '2025-08-04', '2025-08-04 16:24:21'),
(4, 'Alaska Evaporated Milk', 'Dairy', 20357122682, 'Condensed Milk', '0', 1, '2029-12-04', 100, 50.00, 55.00, 1, 2, 2, 8, 'active', '', 'in stock', '2025-08-04', '2025-08-04 16:24:21'),
(5, 'Century Tuna Flakes in Oil', 'Processed Foods', 12345678905, 'Can foods', NULL, 0, NULL, 15, 50.00, 55.00, 1, 3, 3, NULL, 'active', '', 'in stock', '2025-08-05', '2025-08-04 16:25:02'),
(6, 'Century Tuna Flakes in Oil', 'Processed Foods', 12345678905, 'Can foods', NULL, 0, NULL, 150, 50.00, 55.00, 1, 3, 4, NULL, 'active', '', 'in stock', '2025-08-05', '2025-08-04 16:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_approval`
--

CREATE TABLE `tbl_purchase_order_approval` (
  `approval_id` int(11) NOT NULL,
  `purchase_header_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_delivery`
--

CREATE TABLE `tbl_purchase_order_delivery` (
  `delivery_id` int(11) NOT NULL,
  `purchase_header_id` int(11) NOT NULL,
  `expected_delivery_date` date NOT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `delivery_status` enum('pending','in_transit','delivered','partial','cancelled') DEFAULT 'pending',
  `delivery_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_dtl`
--

CREATE TABLE `tbl_purchase_order_dtl` (
  `purchase_dtl_id` int(11) NOT NULL,
  `purchase_header_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_header`
--

CREATE TABLE `tbl_purchase_order_header` (
  `purchase_header_id` int(11) NOT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `time` time NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('pending','indelivery','delivered','return') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_receiving_dtl`
--

CREATE TABLE `tbl_purchase_receiving_dtl` (
  `receiving_dtl_id` int(11) NOT NULL,
  `receiving_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `ordered_qty` int(11) NOT NULL,
  `received_qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_receiving_header`
--

CREATE TABLE `tbl_purchase_receiving_header` (
  `receiving_id` int(11) NOT NULL,
  `purchase_header_id` int(11) NOT NULL,
  `receiving_date` date NOT NULL,
  `receiving_time` time NOT NULL,
  `received_by` int(11) NOT NULL,
  `delivery_receipt_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','partial') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_return_dtl`
--

CREATE TABLE `tbl_purchase_return_dtl` (
  `return_dtl_id` int(11) NOT NULL,
  `return_header_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_return_header`
--

CREATE TABLE `tbl_purchase_return_header` (
  `return_header_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `total_return_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `supplier_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role`
--

CREATE TABLE `tbl_role` (
  `role_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_role`
--

INSERT INTO `tbl_role` (`role_id`, `role`) VALUES
(1, 'admin'),
(2, 'pharmacist'),
(3, 'cashier'),
(4, 'inventory');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_shift`
--

CREATE TABLE `tbl_shift` (
  `shift_id` int(11) NOT NULL,
  `shifts` varchar(255) NOT NULL,
  `time` time NOT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_shift`
--

INSERT INTO `tbl_shift` (`shift_id`, `shifts`, `time`, `end_time`) VALUES
(1, 'shift1', '06:00:00', '02:00:00'),
(2, 'shift2', '02:00:00', '10:00:00'),
(3, 'shift3', '10:00:00', '06:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_movements`
--

CREATE TABLE `tbl_stock_movements` (
  `movement_id` int(11) NOT NULL,
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
  `created_by` varchar(100) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_stock_movements`
--

INSERT INTO `tbl_stock_movements` (`movement_id`, `product_id`, `batch_id`, `movement_type`, `quantity`, `remaining_quantity`, `unit_cost`, `expiration_date`, `movement_date`, `reference_no`, `notes`, `created_by`) VALUES
(1, 3, 6, 'IN', 20, 120, 50.00, '2029-12-04', '2025-08-04 14:26:55', 'BR-20250804-222655', 'Stock added: +20 units. Old: 100, New: 120', 'admin'),
(2, 3, 7, 'IN', 100, 220, 40.00, '2030-12-04', '2025-08-04 14:27:52', 'BR-20250804-222752', 'Stock added: +100 units. Old: 120, New: 220', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_summary`
--

CREATE TABLE `tbl_stock_summary` (
  `summary_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `batch_reference` varchar(50) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_supplier`
--

CREATE TABLE `tbl_supplier` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_address` varchar(255) NOT NULL,
  `supplier_contact` varchar(20) NOT NULL,
  `supplier_email` varchar(255) DEFAULT NULL,
  `order_level` int(11) NOT NULL,
  `primary_phone` varchar(20) DEFAULT NULL,
  `primary_email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_title` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT NULL,
  `credit_rating` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_supplier`
--

INSERT INTO `tbl_supplier` (`supplier_id`, `supplier_name`, `supplier_address`, `supplier_contact`, `supplier_email`, `order_level`, `primary_phone`, `primary_email`, `contact_person`, `contact_title`, `payment_terms`, `lead_time_days`, `credit_rating`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Midphil Trading Corporation', '#38 Pioneer Street, Mandaluyong City, Metro Manila', '0917-123-4567', 'midphil.main@gmail.com', 50, '(02) 8556-7890', 'orders@midphil.com.ph', 'Maria Dela Cruz', 'Procurement Manager', 'Net 30', 3, 'A', 'Reliable supplier for dairy and processed food goods. Delivers on schedule.', 'active', '2025-08-04 09:28:05', '2025-08-04 09:28:05'),
(2, 'NutriSource Inc.', '77 Ortigas Avenue, Pasig City', '0928-456-7890', 'nutribusiness@yahoo.com', 30, '(02) 8471-1122', 'supply@nutrisource.com', 'John Tan', 'Sales Director', 'COD', 2, 'B+', 'Fast-moving vitamins and supplement distributor. Accepts urgent orders.', 'active', '2025-08-04 09:29:42', '2025-08-04 09:29:42'),
(3, 'HealthFirst Distributors', '19 East Service Road, Muntinlupa City', ' 0995-321-7654', 'contact@healthfirst.com.ph', 100, '(02) 8890-3345', 'sales@healthfirst.com.ph', 'Angela Ramos', 'Key Accounts Executive', 'Net 15', 4, 'A-', 'Specializes in OTC medicines and personal care products.', 'active', '2025-08-04 09:30:43', '2025-08-04 09:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transfer_dtl`
--

CREATE TABLE `tbl_transfer_dtl` (
  `transfer_dtl_id` int(11) NOT NULL,
  `transfer_header_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transfer_dtl`
--

INSERT INTO `tbl_transfer_dtl` (`transfer_dtl_id`, `transfer_header_id`, `product_id`, `qty`) VALUES
(10, 16, 3, 5),
(11, 17, 3, 10),
(12, 18, 3, 150);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transfer_header`
--

CREATE TABLE `tbl_transfer_header` (
  `transfer_header_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `source_location_id` int(11) NOT NULL,
  `destination_location_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transfer_header`
--

INSERT INTO `tbl_transfer_header` (`transfer_header_id`, `date`, `delivery_date`, `source_location_id`, `destination_location_id`, `employee_id`, `status`) VALUES
(16, '2025-08-05', NULL, 2, 3, 1, 'approved'),
(17, '2025-08-05', NULL, 2, 3, 1, 'approved'),
(18, '2025-08-05', NULL, 2, 4, 1, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transfer_log`
--

CREATE TABLE `tbl_transfer_log` (
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `from_location` varchar(100) DEFAULT NULL,
  `to_location` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transfer_log`
--

INSERT INTO `tbl_transfer_log` (`transfer_id`, `product_id`, `product_name`, `from_location`, `to_location`, `quantity`, `transfer_date`, `created_at`) VALUES
(16, 3, NULL, 'warehouse', 'Destination Location', 5, '2025-08-05', '2025-08-04 16:25:02'),
(17, 3, NULL, 'warehouse', 'Destination Location', 10, '2025-08-05', '2025-08-04 16:25:06'),
(18, 3, NULL, 'warehouse', 'Destination Location', 150, '2025-08-05', '2025-08-04 16:25:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_expiring_products`
-- (See below for the actual view)
--
CREATE TABLE `v_expiring_products` (
`product_id` int(11)
,`product_name` varchar(255)
,`barcode` bigint(20)
,`available_quantity` int(11)
,`expiration_date` date
,`days_until_expiry` int(7)
,`batch_reference` varchar(50)
,`batch_date` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_fifo_stock`
-- (See below for the actual view)
--
CREATE TABLE `v_fifo_stock` (
`product_id` int(11)
,`product_name` varchar(255)
,`barcode` bigint(20)
,`category` varchar(255)
,`batch_id` int(11)
,`batch_reference` varchar(50)
,`available_quantity` int(11)
,`unit_cost` decimal(10,2)
,`expiration_date` date
,`batch_date` date
,`fifo_order` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `v_expiring_products`
--
DROP TABLE IF EXISTS `v_expiring_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expiring_products`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `p`.`barcode` AS `barcode`, `ss`.`available_quantity` AS `available_quantity`, `ss`.`expiration_date` AS `expiration_date`, to_days(`ss`.`expiration_date`) - to_days(curdate()) AS `days_until_expiry`, `ss`.`batch_reference` AS `batch_reference`, `b`.`entry_date` AS `batch_date` FROM ((`tbl_product` `p` join `tbl_stock_summary` `ss` on(`p`.`product_id` = `ss`.`product_id`)) join `tbl_batch` `b` on(`ss`.`batch_id` = `b`.`batch_id`)) WHERE `p`.`status` = 'active' AND `ss`.`available_quantity` > 0 AND `ss`.`expiration_date` is not null AND `ss`.`expiration_date` >= curdate() ORDER BY `ss`.`expiration_date` ASC, `b`.`entry_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_fifo_stock`
--
DROP TABLE IF EXISTS `v_fifo_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_fifo_stock`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `p`.`barcode` AS `barcode`, `p`.`category` AS `category`, `ss`.`batch_id` AS `batch_id`, `ss`.`batch_reference` AS `batch_reference`, `ss`.`available_quantity` AS `available_quantity`, `ss`.`unit_cost` AS `unit_cost`, `ss`.`expiration_date` AS `expiration_date`, `b`.`entry_date` AS `batch_date`, row_number() over ( partition by `p`.`product_id` order by `b`.`entry_date`,`ss`.`summary_id`) AS `fifo_order` FROM ((`tbl_product` `p` join `tbl_stock_summary` `ss` on(`p`.`product_id` = `ss`.`product_id`)) join `tbl_batch` `b` on(`ss`.`batch_id` = `b`.`batch_id`)) WHERE `p`.`status` = 'active' AND `ss`.`available_quantity` > 0 ORDER BY `p`.`product_id` ASC, row_number() over ( partition by `p`.`product_id` order by `b`.`entry_date`,`ss`.`summary_id`) ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_adjustment_details`
--
ALTER TABLE `tbl_adjustment_details`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_adjustment_header`
--
ALTER TABLE `tbl_adjustment_header`
  ADD PRIMARY KEY (`adjustment_id`);

--
-- Indexes for table `tbl_archive`
--
ALTER TABLE `tbl_archive`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_item_type` (`item_type`),
  ADD KEY `idx_archived_date` (`archived_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `fk_batch_supplier` (`supplier_id`),
  ADD KEY `fk_batch_location` (`location_id`),
  ADD KEY `idx_entry_date` (`entry_date`);

--
-- Indexes for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  ADD PRIMARY KEY (`brand_id`),
  ADD KEY `fk_brand_category` (`category_id`);

--
-- Indexes for table `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `tbl_discount`
--
ALTER TABLE `tbl_discount`
  ADD PRIMARY KEY (`discount_id`);

--
-- Indexes for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `tbl_fifo_stock`
--
ALTER TABLE `tbl_fifo_stock`
  ADD PRIMARY KEY (`fifo_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_expiration_date` (`expiration_date`),
  ADD KEY `idx_entry_date` (`entry_date`);

--
-- Indexes for table `tbl_location`
--
ALTER TABLE `tbl_location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `tbl_pos_sales_details`
--
ALTER TABLE `tbl_pos_sales_details`
  ADD PRIMARY KEY (`sales_details_id`),
  ADD KEY `sales_header_id` (`sales_header_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_pos_sales_header`
--
ALTER TABLE `tbl_pos_sales_header`
  ADD PRIMARY KEY (`sales_header_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `terminal_id` (`terminal_id`);

--
-- Indexes for table `tbl_pos_terminal`
--
ALTER TABLE `tbl_pos_terminal`
  ADD PRIMARY KEY (`terminal_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `tbl_pos_transaction`
--
ALTER TABLE `tbl_pos_transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `fk_product_batch` (`batch_id`),
  ADD KEY `fk_product_supplier` (`supplier_id`),
  ADD KEY `fk_product_location` (`location_id`),
  ADD KEY `idx_barcode_status` (`barcode`,`status`);

--
-- Indexes for table `tbl_purchase_order_approval`
--
ALTER TABLE `tbl_purchase_order_approval`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `fk_approval_purchase_header` (`purchase_header_id`),
  ADD KEY `fk_approval_employee` (`approved_by`);

--
-- Indexes for table `tbl_purchase_order_delivery`
--
ALTER TABLE `tbl_purchase_order_delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `fk_delivery_purchase_header` (`purchase_header_id`);

--
-- Indexes for table `tbl_purchase_order_dtl`
--
ALTER TABLE `tbl_purchase_order_dtl`
  ADD PRIMARY KEY (`purchase_dtl_id`),
  ADD KEY `purchase_header_id` (`purchase_header_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_purchase_order_header`
--
ALTER TABLE `tbl_purchase_order_header`
  ADD PRIMARY KEY (`purchase_header_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `tbl_purchase_receiving_dtl`
--
ALTER TABLE `tbl_purchase_receiving_dtl`
  ADD PRIMARY KEY (`receiving_dtl_id`),
  ADD KEY `fk_receiving_dtl_header` (`receiving_id`),
  ADD KEY `fk_receiving_dtl_product` (`product_id`);

--
-- Indexes for table `tbl_purchase_receiving_header`
--
ALTER TABLE `tbl_purchase_receiving_header`
  ADD PRIMARY KEY (`receiving_id`),
  ADD KEY `fk_receiving_purchase_header` (`purchase_header_id`),
  ADD KEY `fk_receiving_employee` (`received_by`);

--
-- Indexes for table `tbl_purchase_return_dtl`
--
ALTER TABLE `tbl_purchase_return_dtl`
  ADD PRIMARY KEY (`return_dtl_id`),
  ADD KEY `return_header_id` (`return_header_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_purchase_return_header`
--
ALTER TABLE `tbl_purchase_return_header`
  ADD PRIMARY KEY (`return_header_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `tbl_role`
--
ALTER TABLE `tbl_role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `tbl_shift`
--
ALTER TABLE `tbl_shift`
  ADD PRIMARY KEY (`shift_id`);

--
-- Indexes for table `tbl_stock_movements`
--
ALTER TABLE `tbl_stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_product_batch` (`product_id`,`batch_id`),
  ADD KEY `idx_movement_date` (`movement_date`),
  ADD KEY `idx_expiration` (`expiration_date`);

--
-- Indexes for table `tbl_stock_summary`
--
ALTER TABLE `tbl_stock_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `unique_product_batch` (`product_id`,`batch_id`),
  ADD KEY `idx_product_expiration` (`product_id`,`expiration_date`);

--
-- Indexes for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `tbl_transfer_dtl`
--
ALTER TABLE `tbl_transfer_dtl`
  ADD PRIMARY KEY (`transfer_dtl_id`),
  ADD KEY `transfer_header_id` (`transfer_header_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_transfer_header`
--
ALTER TABLE `tbl_transfer_header`
  ADD PRIMARY KEY (`transfer_header_id`),
  ADD KEY `source_location_id` (`source_location_id`),
  ADD KEY `destination_location_id` (`destination_location_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tbl_transfer_log`
--
ALTER TABLE `tbl_transfer_log`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_adjustment_details`
--
ALTER TABLE `tbl_adjustment_details`
  MODIFY `adjustment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_archive`
--
ALTER TABLE `tbl_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_discount`
--
ALTER TABLE `tbl_discount`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_fifo_stock`
--
ALTER TABLE `tbl_fifo_stock`
  MODIFY `fifo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_location`
--
ALTER TABLE `tbl_location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_pos_sales_details`
--
ALTER TABLE `tbl_pos_sales_details`
  MODIFY `sales_details_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_pos_sales_header`
--
ALTER TABLE `tbl_pos_sales_header`
  MODIFY `sales_header_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_pos_terminal`
--
ALTER TABLE `tbl_pos_terminal`
  MODIFY `terminal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_pos_transaction`
--
ALTER TABLE `tbl_pos_transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_approval`
--
ALTER TABLE `tbl_purchase_order_approval`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_delivery`
--
ALTER TABLE `tbl_purchase_order_delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_dtl`
--
ALTER TABLE `tbl_purchase_order_dtl`
  MODIFY `purchase_dtl_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_header`
--
ALTER TABLE `tbl_purchase_order_header`
  MODIFY `purchase_header_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_receiving_dtl`
--
ALTER TABLE `tbl_purchase_receiving_dtl`
  MODIFY `receiving_dtl_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_receiving_header`
--
ALTER TABLE `tbl_purchase_receiving_header`
  MODIFY `receiving_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_return_dtl`
--
ALTER TABLE `tbl_purchase_return_dtl`
  MODIFY `return_dtl_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_return_header`
--
ALTER TABLE `tbl_purchase_return_header`
  MODIFY `return_header_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_role`
--
ALTER TABLE `tbl_role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_shift`
--
ALTER TABLE `tbl_shift`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_stock_movements`
--
ALTER TABLE `tbl_stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_stock_summary`
--
ALTER TABLE `tbl_stock_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_transfer_dtl`
--
ALTER TABLE `tbl_transfer_dtl`
  MODIFY `transfer_dtl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_transfer_header`
--
ALTER TABLE `tbl_transfer_header`
  MODIFY `transfer_header_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_transfer_log`
--
ALTER TABLE `tbl_transfer_log`
  MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_adjustment_details`
--
ALTER TABLE `tbl_adjustment_details`
  ADD CONSTRAINT `tbl_adjustment_details_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`emp_id`),
  ADD CONSTRAINT `tbl_adjustment_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

--
-- Constraints for table `tbl_adjustment_header`
--
ALTER TABLE `tbl_adjustment_header`
  ADD CONSTRAINT `tbl_adjustment_header_ibfk_1` FOREIGN KEY (`adjustment_id`) REFERENCES `tbl_adjustment_details` (`adjustment_id`);

--
-- Constraints for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  ADD CONSTRAINT `fk_batch_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`),
  ADD CONSTRAINT `fk_batch_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier` (`supplier_id`);

--
-- Constraints for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  ADD CONSTRAINT `fk_brand_category` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`category_id`);

--
-- Constraints for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD CONSTRAINT `tbl_employee_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `tbl_role` (`role_id`),
  ADD CONSTRAINT `tbl_employee_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `tbl_shift` (`shift_id`);

--
-- Constraints for table `tbl_fifo_stock`
--
ALTER TABLE `tbl_fifo_stock`
  ADD CONSTRAINT `fk_fifo_stock_batch` FOREIGN KEY (`batch_id`) REFERENCES `tbl_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fifo_stock_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_pos_sales_details`
--
ALTER TABLE `tbl_pos_sales_details`
  ADD CONSTRAINT `tbl_pos_sales_details_ibfk_1` FOREIGN KEY (`sales_header_id`) REFERENCES `tbl_pos_sales_header` (`sales_header_id`),
  ADD CONSTRAINT `tbl_pos_sales_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

--
-- Constraints for table `tbl_pos_sales_header`
--
ALTER TABLE `tbl_pos_sales_header`
  ADD CONSTRAINT `tbl_pos_sales_header_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `tbl_pos_transaction` (`transaction_id`),
  ADD CONSTRAINT `tbl_pos_sales_header_ibfk_2` FOREIGN KEY (`terminal_id`) REFERENCES `tbl_pos_terminal` (`terminal_id`);

--
-- Constraints for table `tbl_pos_terminal`
--
ALTER TABLE `tbl_pos_terminal`
  ADD CONSTRAINT `tbl_pos_terminal_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `tbl_shift` (`shift_id`);

--
-- Constraints for table `tbl_pos_transaction`
--
ALTER TABLE `tbl_pos_transaction`
  ADD CONSTRAINT `tbl_pos_transaction_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `tbl_employee` (`emp_id`);

--
-- Constraints for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD CONSTRAINT `fk_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_batch` FOREIGN KEY (`batch_id`) REFERENCES `tbl_batch` (`batch_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_brand` FOREIGN KEY (`brand_id`) REFERENCES `tbl_brand` (`brand_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier` (`supplier_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_product_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `tbl_brand` (`brand_id`);

--
-- Constraints for table `tbl_purchase_order_approval`
--
ALTER TABLE `tbl_purchase_order_approval`
  ADD CONSTRAINT `fk_approval_employee` FOREIGN KEY (`approved_by`) REFERENCES `tbl_employee` (`emp_id`),
  ADD CONSTRAINT `fk_approval_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`);

--
-- Constraints for table `tbl_purchase_order_delivery`
--
ALTER TABLE `tbl_purchase_order_delivery`
  ADD CONSTRAINT `fk_delivery_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`);

--
-- Constraints for table `tbl_purchase_order_dtl`
--
ALTER TABLE `tbl_purchase_order_dtl`
  ADD CONSTRAINT `tbl_purchase_order_dtl_ibfk_1` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`),
  ADD CONSTRAINT `tbl_purchase_order_dtl_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

--
-- Constraints for table `tbl_purchase_order_header`
--
ALTER TABLE `tbl_purchase_order_header`
  ADD CONSTRAINT `tbl_purchase_order_header_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier` (`supplier_id`);

--
-- Constraints for table `tbl_purchase_receiving_dtl`
--
ALTER TABLE `tbl_purchase_receiving_dtl`
  ADD CONSTRAINT `fk_receiving_dtl_header` FOREIGN KEY (`receiving_id`) REFERENCES `tbl_purchase_receiving_header` (`receiving_id`),
  ADD CONSTRAINT `fk_receiving_dtl_product` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

--
-- Constraints for table `tbl_purchase_receiving_header`
--
ALTER TABLE `tbl_purchase_receiving_header`
  ADD CONSTRAINT `fk_receiving_employee` FOREIGN KEY (`received_by`) REFERENCES `tbl_employee` (`emp_id`),
  ADD CONSTRAINT `fk_receiving_purchase_header` FOREIGN KEY (`purchase_header_id`) REFERENCES `tbl_purchase_order_header` (`purchase_header_id`);

--
-- Constraints for table `tbl_purchase_return_dtl`
--
ALTER TABLE `tbl_purchase_return_dtl`
  ADD CONSTRAINT `tbl_purchase_return_dtl_ibfk_1` FOREIGN KEY (`return_header_id`) REFERENCES `tbl_purchase_return_header` (`return_header_id`),
  ADD CONSTRAINT `tbl_purchase_return_dtl_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

--
-- Constraints for table `tbl_purchase_return_header`
--
ALTER TABLE `tbl_purchase_return_header`
  ADD CONSTRAINT `tbl_purchase_return_header_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier` (`supplier_id`);

--
-- Constraints for table `tbl_transfer_dtl`
--
ALTER TABLE `tbl_transfer_dtl`
  ADD CONSTRAINT `tbl_transfer_dtl_ibfk_1` FOREIGN KEY (`transfer_header_id`) REFERENCES `tbl_transfer_header` (`transfer_header_id`),
  ADD CONSTRAINT `tbl_transfer_dtl_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_transfer_header`
--
ALTER TABLE `tbl_transfer_header`
  ADD CONSTRAINT `tbl_transfer_header_ibfk_1` FOREIGN KEY (`source_location_id`) REFERENCES `tbl_location` (`location_id`),
  ADD CONSTRAINT `tbl_transfer_header_ibfk_2` FOREIGN KEY (`destination_location_id`) REFERENCES `tbl_location` (`location_id`),
  ADD CONSTRAINT `tbl_transfer_header_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`emp_id`);

--
-- Constraints for table `tbl_transfer_log`
--
ALTER TABLE `tbl_transfer_log`
  ADD CONSTRAINT `tbl_transfer_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

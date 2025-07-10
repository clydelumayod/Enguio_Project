-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2025 at 05:29 PM
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
-- Database: `enguio`
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
(1, '0000-00-00', '2025-06-26 14:05:09', 'BR-20250626-220440', NULL, 12, 2, '2025-06-26', '16:05:09', 'admin', '4', NULL),
(2, '0000-00-00', '2025-06-26 14:20:32', 'BR-20250626-222016', NULL, 13, 2, '2025-06-26', '16:20:32', 'admin', '424', NULL),
(3, '0000-00-00', '2025-06-26 14:35:56', 'BR-20250626-223540', NULL, 12, 2, '2025-06-26', '16:35:56', 'admin', 'dawd', NULL),
(4, '0000-00-00', '2025-06-26 15:12:11', 'BR-20250626-231144', NULL, 12, 2, '2025-06-26', '17:12:11', 'admin', '424', NULL),
(5, '0000-00-00', '2025-06-26 15:13:54', 'BR-20250626-231336', NULL, 12, 2, '2025-06-26', '17:13:54', 'admin', '42', NULL),
(6, '0000-00-00', '2025-06-26 15:37:59', 'BR-20250626-233733', NULL, 13, 2, '2025-06-26', '17:37:59', 'admin', '242', NULL),
(7, '0000-00-00', '2025-06-26 15:42:53', 'BR-20250626-234228', NULL, 12, 2, '2025-06-26', '17:42:53', 'admin', '4242', NULL),
(8, '0000-00-00', '2025-06-26 15:47:43', 'BR-20250626-234724', NULL, 13, 2, '2025-06-26', '17:47:43', 'admin', '424', ''),
(9, '0000-00-00', '2025-06-26 15:50:07', 'BR-20250626-234949', NULL, 13, 2, '2025-06-26', '17:50:07', 'admin', '242', ''),
(10, '0000-00-00', '2025-06-26 15:51:20', 'BR-20250626-235106', NULL, 13, 2, '2025-06-26', '17:51:20', 'admin', '242', NULL),
(11, '0000-00-00', '2025-06-26 15:52:35', 'BR-20250626-235217', NULL, 12, 2, '2025-06-26', '17:52:35', 'admin', '4', NULL),
(12, '0000-00-00', '2025-06-26 15:58:15', 'BR-20250626-235751', NULL, 13, 2, '2025-06-26', '17:58:15', 'admin', '424', NULL),
(13, '0000-00-00', '2025-06-27 08:11:16', 'BR-20250627-160432', NULL, 13, 2, '2025-06-27', '10:11:16', 'admin', '4242', NULL),
(14, '0000-00-00', '2025-06-27 08:12:48', 'BR-20250627-161227', NULL, 13, 2, '2025-06-27', '10:12:48', 'admin', '24242', NULL),
(15, '0000-00-00', '2025-06-27 08:15:05', 'BR-20250627-161446', NULL, 13, 2, '2025-06-27', '10:15:05', 'admin', '4', NULL),
(16, '0000-00-00', '2025-06-27 08:16:56', 'BR-20250627-161629', NULL, 13, 2, '2025-06-27', '10:16:56', 'admin', '242', NULL),
(17, '0000-00-00', '2025-06-30 15:32:01', 'BR-20250630-232544', NULL, 13, 2, '2025-06-30', '17:32:01', 'admin', '24424', NULL),
(18, '0000-00-00', '2025-07-03 10:33:36', 'BR-20250703-183052', NULL, 13, 2, '2025-07-03', '12:33:36', 'admin', '242', NULL),
(19, '0000-00-00', '2025-07-03 10:48:41', 'BR-20250703-184716', NULL, 13, 2, '2025-07-03', '12:48:41', 'admin', '424', NULL),
(20, '0000-00-00', '2025-07-03 11:09:27', 'BR-20250703-190843', NULL, 13, 2, '2025-07-03', '13:09:27', 'admin', '4251532', NULL),
(21, '0000-00-00', '2025-07-03 12:24:14', 'BR-20250703-202247', NULL, 13, 2, '2025-07-03', '14:24:14', 'admin', '4242', NULL),
(22, '0000-00-00', '2025-07-10 13:50:04', 'BR-20250710-214744', NULL, 13, 2, '2025-07-10', '15:50:04', 'admin', '76', NULL),
(23, '0000-00-00', '2025-07-10 13:52:55', 'BR-20250710-215212', NULL, 13, 2, '2025-07-10', '15:52:55', 'admin', '242', NULL),
(24, '0000-00-00', '2025-07-10 13:56:10', 'BR-20250710-215532', NULL, 13, 2, '2025-07-10', '15:56:10', 'admin', '24', NULL),
(25, '0000-00-00', '2025-07-10 14:17:42', 'BR-20250710-221636', NULL, 13, 2, '2025-07-10', '16:17:42', 'admin', '24', NULL),
(26, '0000-00-00', '2025-07-10 14:28:29', 'BR-20250710-222803', NULL, 13, 2, '2025-07-10', '16:28:29', 'admin', '242', NULL),
(27, '0000-00-00', '2025-07-10 14:35:06', 'BR-20250710-223433', NULL, 13, 2, '2025-07-10', '16:35:06', 'admin', '24', NULL),
(28, '0000-00-00', '2025-07-10 14:41:41', 'BR-20250710-224105', NULL, 13, 2, '2025-07-10', '16:41:41', 'admin', '24', NULL),
(29, '0000-00-00', '2025-07-10 14:44:10', 'BR-20250710-224335', NULL, 13, 2, '2025-07-10', '16:44:10', 'admin', '24', NULL),
(30, '0000-00-00', '2025-07-10 15:15:21', 'BR-20250710-231438', NULL, 13, 2, '2025-07-10', '17:15:21', 'admin', '24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_brand`
--

CREATE TABLE `tbl_brand` (
  `brand_id` int(11) NOT NULL,
  `brand` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_brand`
--

INSERT INTO `tbl_brand` (`brand_id`, `brand`) VALUES
(30, 'daw'),
(31, 'awdwa'),
(32, 'dawd'),
(33, 'wd'),
(34, 'awd'),
(35, 'Right Mid'),
(36, 'righ mid'),
(37, 'dwad2'),
(38, 'wdawd'),
(39, 'awdaw'),
(40, 'dwada');

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
  `shift_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(10) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_employee`
--

INSERT INTO `tbl_employee` (`emp_id`, `Fname`, `Mname`, `Lname`, `email`, `contact_num`, `role_id`, `shift_id`, `username`, `password`, `age`, `address`, `status`) VALUES
(19, 'tenten', 'B', 'Gutierrez', 'ten@gmail.com', '09788878787', 4, 3, 'ezay', '1234', 22, 'Opol mis.or', 'Active'),
(20, 'clyde', 'elmer', 'parol', 'clydepautog@gmail.com', '0982711818', 3, 3, 'bayot', '1234', 22, 'Opol mis.or', 'Active'),
(21, 'elmer', 'clyde', 'parol', 'shierouuj@gmail.com', '09788878787', 2, 3, 'bayot2', '1234', 22, 'Opol mis.or', 'Active');

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
  `brand_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `Variation` varchar(255) DEFAULT NULL,
  `stock_status` varchar(20) DEFAULT 'in stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(69, 'dawd', 'dawd', 1234567890123, 'dawd - dawd', '0', 1, '2025-07-02', 2, 2.00, 31, 12, 2, 7, 'active', NULL, 'out of stock'),
(76, 'daw', 'daw', 123456721890123, 'daw - dawda', '0', 1, '2025-07-08', 23, 23.00, 33, 12, 2, 11, 'active', NULL, 'out of stock'),
(77, 'dawdaw', 'dawd', 111234567890123, 'dawdaw - adaw', '0', 1, '2025-07-11', 232, 232.00, 30, 13, 2, 12, 'active', NULL, 'in stock'),
(79, 'dawd', 'awd', 123456220123, 'dawd - awdaw', '1', 0, '2025-07-03', 22, 22.00, 32, 13, 2, 13, 'active', NULL, 'out of stock'),
(80, 'dawd', 'awdaw', 2221234567890123, 'dawd - dawd', '1', 0, NULL, 222, 22.00, 34, 13, 2, 14, 'active', NULL, 'in stock'),
(82, 'dawd', 'adawd', 22221234567890123, 'dawd - wadaw', '0', 0, '2025-07-08', 22, 22.00, 30, 13, 2, 15, 'active', NULL, 'in stock'),
(83, 'dawdaw', 'dawda', 113131234567890123, 'dawdaw - wdaw', '1', 0, NULL, 20, 22.00, 30, 13, 2, 16, 'active', NULL, 'in stock');

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
  `date` date NOT NULL,
  `time` time NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL
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
(12, 'MidPhil', 'daw', '09788878787', 'ten@gmail.com', 3, '09788878787', 'ten@gmail.com', 'tenten B Gutierrez', '222', 'COD', 3, 'Excellent', 'wadaw', 'inactive', '2025-06-23 14:57:14', '2025-07-03 10:24:58'),
(13, 'MidPhil', 'dawda', '09788878787', 'ten@gmail.com', 3, '09788878787', 'ten@gmail.com', 'tenten B Gutierrez', '222', 'COD', 3, 'Excellent', 'dawd', 'active', '2025-06-23 14:57:14', '2025-06-23 14:57:14');

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
(1, 1, 83, 2),
(2, 2, 83, 200),
(3, 3, 82, 200);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transfer_header`
--

CREATE TABLE `tbl_transfer_header` (
  `transfer_header_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `source_location_id` int(11) NOT NULL,
  `destination_location_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transfer_header`
--

INSERT INTO `tbl_transfer_header` (`transfer_header_id`, `date`, `source_location_id`, `destination_location_id`, `employee_id`, `status`) VALUES
(1, '2025-07-03', 2, 3, 19, ''),
(2, '2025-07-03', 2, 3, 19, ''),
(3, '2025-07-03', 2, 4, 19, '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transfer_log`
--

CREATE TABLE `tbl_transfer_log` (
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `from_location` varchar(100) DEFAULT NULL,
  `to_location` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transfer_log`
--

INSERT INTO `tbl_transfer_log` (`transfer_id`, `product_id`, `from_location`, `to_location`, `quantity`, `transfer_date`, `created_at`) VALUES
(1, 83, '2', '3', 2, '2025-07-03', '2025-07-03 08:01:14'),
(2, 83, '2', '3', 200, '2025-07-03', '2025-07-03 08:11:29'),
(3, 82, '2', '4', 200, '2025-07-03', '2025-07-03 08:12:16');

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
-- Indexes for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `fk_batch_supplier` (`supplier_id`),
  ADD KEY `fk_batch_location` (`location_id`);

--
-- Indexes for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  ADD PRIMARY KEY (`brand_id`);

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
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD UNIQUE KEY `barcode_2` (`barcode`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `fk_product_batch` (`batch_id`),
  ADD KEY `fk_product_supplier` (`supplier_id`),
  ADD KEY `fk_product_location` (`location_id`);

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
-- AUTO_INCREMENT for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `tbl_discount`
--
ALTER TABLE `tbl_discount`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

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
-- AUTO_INCREMENT for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_transfer_dtl`
--
ALTER TABLE `tbl_transfer_dtl`
  MODIFY `transfer_dtl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_transfer_header`
--
ALTER TABLE `tbl_transfer_header`
  MODIFY `transfer_header_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_transfer_log`
--
ALTER TABLE `tbl_transfer_log`
  MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD CONSTRAINT `tbl_employee_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `tbl_role` (`role_id`),
  ADD CONSTRAINT `tbl_employee_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `tbl_shift` (`shift_id`);

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
  ADD CONSTRAINT `tbl_transfer_dtl_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`);

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

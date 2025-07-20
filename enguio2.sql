-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 05:16 PM
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
(35, '0000-00-00', '2025-07-16 15:25:52', 'BR-20250716-232504', NULL, 13, 2, '2025-07-16', '23:25:52', 'admin', '019823', NULL);

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
(59, 'Lucky Me', 9),
(60, 'Argentina', 9),
(61, 'CDO', 9),
(62, '555', 9),
(63, 'Century Tuna', 9),
(64, 'Ligo', 9),
(65, 'Young’s Town', 9),
(66, 'Purefoods', 9),
(67, 'Holiday', 9),
(68, 'Swift', 9),
(69, 'El Rancho', 9),
(70, 'Mega Sardines', 9),
(71, 'Family’s Brand Sardines', 9),
(72, 'Hunt’s', 9),
(73, 'Wow Ulam', 9),
(74, 'Delimondo', 9),
(75, 'Alaska', 10),
(76, 'Bear Brand', 10),
(77, 'Selecta', 10),
(78, 'Magnolia', 10),
(79, 'Nesvita', 10),
(80, 'Anchor', 10),
(81, 'Cowbell', 10),
(82, 'Birch Tree', 10),
(83, 'Star Margarine', 10),
(84, 'La Filipina', 10),
(85, 'NutriMilk', 10),
(86, 'Mr. Milk', 10),
(87, 'Coca-Cola', 11),
(88, 'Pepsi', 11),
(89, 'Zest-O', 11),
(90, 'Summit', 11),
(91, 'Wilkins', 11),
(92, 'RC Cola', 11),
(93, 'Nestea', 11),
(94, 'Real Leaf', 11),
(95, 'Sparkle', 11),
(96, 'Sunkist', 11),
(97, 'Eight O’Clock', 11),
(98, 'Locally', 11),
(99, 'Cheers', 11),
(100, 'Absolute', 11),
(101, 'Nature’s Spring', 11),
(102, 'Coca-Cola', 11),
(103, 'Pepsi', 11),
(104, 'Zest-O', 11),
(105, 'Summit', 11),
(106, 'Wilkins', 11),
(107, 'RC Cola', 11),
(108, 'Nestea', 11),
(109, 'Real Leaf', 11),
(110, 'Sparkle', 11),
(111, 'Sunkist', 11),
(112, 'Eight O’Clock', 11),
(113, 'Locally', 11),
(114, 'Cheers', 11),
(115, 'Absolute', 11),
(116, 'Nature’s Spring', 11),
(117, 'Ceelin', 12),
(118, 'Enervon', 12),
(119, 'Revicon', 12),
(120, 'Stresstabs', 12),
(121, 'Fern-C', 12),
(122, 'Cherifer', 12),
(123, 'ImmunPro', 12),
(124, 'Sangobion', 12),
(125, 'Neurobion', 12),
(126, 'Ritemed', 12),
(127, 'Potencee', 12),
(128, 'Tiki-Tiki', 12),
(129, 'Centrum Advance', 12),
(130, 'Propan TLC', 12),
(131, 'Sodium Ascorbate', 12),
(132, 'Biogesic', 13),
(133, 'Neozep', 13),
(134, 'Bioflu', 13),
(135, 'Decolgen', 13),
(136, 'Medicol', 13),
(137, 'Diatabs', 13),
(138, 'Ascof Lagundi', 13),
(139, 'Kremil-S', 13),
(140, 'Solmux', 13),
(141, 'Tuseran', 13),
(142, 'Tempra', 13),
(143, 'Dolfenal', 13),
(144, 'Alaxan FR', 13),
(145, 'Biofermin', 13),
(146, 'Bonamine', 13),
(147, 'Silka', 14),
(148, 'Safeguard', 14),
(149, 'Palmolive', 14),
(150, 'Head & Shoulders', 14),
(151, 'Tide', 14),
(152, 'Pride', 14),
(153, 'Downy', 14),
(154, 'Mr. Clean', 14),
(155, 'Lifebuoy', 14),
(156, 'Johnson’s Baby', 14),
(157, 'Champion', 14),
(158, 'Calla', 14),
(159, 'Babyflo', 14),
(160, 'Hygienix', 14),
(161, 'Dr. Kaufmann', 14),
(162, 'Pond’s', 15),
(163, 'Eskinol', 15),
(164, 'Olay', 15),
(165, 'SkinWhite', 15),
(166, 'Myra-E', 15),
(167, 'Belo', 15),
(168, 'Ever Bilena', 15),
(169, 'Careline', 15),
(170, 'GT Cosmetics', 15),
(171, 'RDL', 15),
(172, 'Brilliant Skin Essentials', 15),
(173, 'RYX Skin', 15),
(174, 'Skin Magical', 15),
(175, 'Avon', 15),
(176, 'Vice Cosmetics', 15),
(177, 'EB Matte Lipstick', 15),
(178, 'Ever Bilena Advance', 15);

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
(94, 'Purified bottled drinking water', 'Beverages', 4891208040174, '', '0', 0, NULL, 10, 100.00, 59, 14, 2, NULL, 'active', '', 'low stock'),
(95, 'condensed milk', 'Dairy', 20357122682, 'dawdaw', '1', 0, '2025-07-17', 10, 50.00, 75, 13, 2, 35, 'active', '', 'low stock');

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
(12, 'MidPhil', 'daw', '09788878787', 'ten@gmail.com', 3, '09788878787', 'ten@gmail.com', 'tenten B Gutierrez', '222', 'COD', 3, 'Excellent', 'wadaw', 'inactive', '2025-06-23 14:57:14', '2025-07-03 10:24:58'),
(13, 'MidPhil', 'dawda', '09788878787', 'ten@gmail.com', 3, '09788878787', 'ten@gmail.com', 'tenten B Gutierrez', '222', 'COD', 3, 'Excellent', 'dawd', 'active', '2025-06-23 14:57:14', '2025-06-23 14:57:14'),
(14, 'dawd', 'dwa', '09788878787', 'tten@gmail.com', 2, '2424', 'dwad42@gmail.com', '424', '42', '424', 24, '24', 'awd', 'active', '2025-07-10 15:43:31', '2025-07-10 15:43:31');

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
(1, 10, 94, 90),
(2, 11, 95, 40);

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
(10, '2025-07-16', 2, 3, 21, 'approved'),
(11, '2025-07-17', 2, 3, 19, 'approved');

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
  ADD KEY `fk_product_location` (`location_id`),
  ADD KEY `idx_barcode_status` (`barcode`,`status`);

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
-- AUTO_INCREMENT for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tbl_brand`
--
ALTER TABLE `tbl_brand`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

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
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

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
-- AUTO_INCREMENT for table `tbl_stock_movements`
--
ALTER TABLE `tbl_stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_stock_summary`
--
ALTER TABLE `tbl_stock_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_transfer_dtl`
--
ALTER TABLE `tbl_transfer_dtl`
  MODIFY `transfer_dtl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_transfer_header`
--
ALTER TABLE `tbl_transfer_header`
  MODIFY `transfer_header_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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

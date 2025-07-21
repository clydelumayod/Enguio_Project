-- Add 5 products per category to tbl_product table
-- This script adds 35 products total (7 categories x 5 products each)

-- Processed Foods Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(96, 'Lucky Me Pancit Canton', 'Processed Foods', 4800016021234, 'Instant pancit canton noodles with seasoning', '0', 0, '2025-12-31', 50, 12.50, 59, 13, 2, NULL, 'active', 'Original', 'in stock'),
(97, 'Argentina Corned Beef', 'Processed Foods', 4800016021235, 'Premium corned beef in natural juices', '0', 0, '2025-10-15', 30, 45.00, 60, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(98, 'CDO Hotdog', 'Processed Foods', 4800016021236, 'Classic hotdog with natural casing', '0', 0, '2025-09-20', 40, 35.00, 61, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(99, '555 Sardines in Tomato Sauce', 'Processed Foods', 4800016021237, 'Sardines in rich tomato sauce', '0', 0, '2025-11-30', 60, 18.00, 62, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(100, 'Century Tuna Flakes in Oil', 'Processed Foods', 4800016021238, 'Premium tuna flakes in vegetable oil', '0', 0, '2025-08-15', 25, 42.00, 63, 13, 2, NULL, 'active', 'Regular', 'low stock');

-- Dairy Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(101, 'Alaska Condensed Milk', 'Dairy', 4800016021239, 'Sweetened condensed milk for cooking and baking', '0', 0, '2025-07-17', 35, 50.00, 75, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(102, 'Bear Brand Powdered Milk', 'Dairy', 4800016021240, 'Fortified powdered milk drink', '0', 0, '2025-12-31', 20, 85.00, 76, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(103, 'Selecta Vanilla Ice Cream', 'Dairy', 4800016021241, 'Premium vanilla ice cream', '0', 0, '2025-06-30', 15, 120.00, 77, 13, 2, NULL, 'active', 'Regular', 'low stock'),
(104, 'Magnolia Fresh Milk', 'Dairy', 4800016021242, 'Fresh whole milk', '0', 0, '2025-05-20', 25, 65.00, 78, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(105, 'Nesvita Chocolate Milk', 'Dairy', 4800016021243, 'Chocolate flavored milk drink', '0', 0, '2025-08-15', 30, 45.00, 79, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Beverages Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(106, 'Coca-Cola Classic 1.5L', 'Beverages', 4800016021244, 'Classic Coca-Cola soft drink', '0', 0, '2025-12-31', 40, 75.00, 87, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(107, 'Pepsi Cola 2L', 'Beverages', 4800016021245, 'Pepsi cola soft drink', '0', 0, '2025-12-31', 35, 85.00, 88, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(108, 'Zest-O Orange Juice 1L', 'Beverages', 4800016021246, 'Natural orange juice drink', '0', 0, '2025-08-30', 25, 55.00, 89, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(109, 'Summit Drinking Water 500ml', 'Beverages', 4800016021247, 'Purified drinking water', '0', 0, '2025-12-31', 100, 15.00, 90, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(110, 'Wilkins Distilled Water 1L', 'Beverages', 4800016021248, 'Distilled water for drinking', '0', 0, '2025-12-31', 80, 25.00, 91, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Vitamins & Supplements Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(111, 'Ceelin Plus Vitamin C', 'Vitamins & Supplements', 4800016021249, 'Vitamin C supplement for immunity', '0', 0, '2025-10-31', 45, 95.00, 117, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(112, 'Enervon Multivitamins', 'Vitamins & Supplements', 4800016021250, 'Complete multivitamin supplement', '0', 0, '2025-09-30', 30, 120.00, 118, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(113, 'Revicon Forte B-Complex', 'Vitamins & Supplements', 4800016021251, 'B-complex vitamin supplement', '0', 0, '2025-11-15', 25, 85.00, 119, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(114, 'Stresstabs Multivitamins', 'Vitamins & Supplements', 4800016021252, 'Stress relief multivitamin', '0', 0, '2025-08-20', 35, 110.00, 120, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(115, 'Fern-C Sodium Ascorbate', 'Vitamins & Supplements', 4800016021253, 'Non-acidic vitamin C supplement', '0', 0, '2025-12-31', 40, 75.00, 121, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Medicine (OTC, prescription) Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(116, 'Biogesic 500mg Tablet', 'Medicine (OTC, prescription)', 4800016021254, 'Paracetamol for fever and pain relief', '0', 0, '2025-12-31', 100, 8.00, 132, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(117, 'Neozep Forte Cold Remedy', 'Medicine (OTC, prescription)', 4800016021255, 'Cold and flu relief medication', '0', 0, '2025-11-30', 75, 12.00, 133, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(118, 'Bioflu Cold Remedy', 'Medicine (OTC, prescription)', 4800016021256, 'Cold and flu relief with decongestant', '0', 0, '2025-10-15', 60, 15.00, 134, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(119, 'Decolgen Forte Tablet', 'Medicine (OTC, prescription)', 4800016021257, 'Decongestant for nasal relief', '0', 0, '2025-09-30', 50, 10.00, 135, 13, 2, NULL, 'active', 'Regular', 'low stock'),
(120, 'Medicol Ibuprofen 200mg', 'Medicine (OTC, prescription)', 4800016021258, 'Ibuprofen for pain and inflammation', '0', 0, '2025-12-31', 80, 6.00, 136, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Toiletries Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(121, 'Silka Papaya Soap 135g', 'Toiletries', 4800016021259, 'Papaya whitening soap', '0', 0, '2025-12-31', 60, 35.00, 147, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(122, 'Safeguard White Soap 135g', 'Toiletries', 4800016021260, 'Antibacterial white soap', '0', 0, '2025-12-31', 70, 28.00, 148, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(123, 'Palmolive Naturals Shampoo 200ml', 'Toiletries', 4800016021261, 'Natural ingredients shampoo', '0', 0, '2025-10-31', 40, 85.00, 149, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(124, 'Head & Shoulders Anti-Dandruff 200ml', 'Toiletries', 4800016021262, 'Anti-dandruff shampoo', '0', 0, '2025-11-30', 35, 95.00, 150, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(125, 'Tide Powder Detergent 1kg', 'Toiletries', 4800016021263, 'Laundry detergent powder', '0', 0, '2025-12-31', 25, 120.00, 151, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Skincare & Cosmetics Category (5 products)
INSERT INTO `tbl_product` (`product_id`, `product_name`, `category`, `barcode`, `description`, `prescription`, `bulk`, `expiration`, `quantity`, `unit_price`, `brand_id`, `supplier_id`, `location_id`, `batch_id`, `status`, `Variation`, `stock_status`) VALUES
(126, 'Pond\'s White Beauty Cream 50g', 'Skincare & Cosmetics', 4800016021264, 'Whitening facial cream', '0', 0, '2025-12-31', 45, 65.00, 162, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(127, 'Eskinol Facial Cleanser 200ml', 'Skincare & Cosmetics', 4800016021265, 'Deep cleansing facial toner', '0', 0, '2025-10-31', 50, 75.00, 163, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(128, 'Olay Total Effects 50g', 'Skincare & Cosmetics', 4800016021266, '7-in-1 anti-aging moisturizer', '0', 0, '2025-11-30', 30, 180.00, 164, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(129, 'SkinWhite Lotion 200ml', 'Skincare & Cosmetics', 4800016021267, 'Whitening body lotion', '0', 0, '2025-09-30', 40, 95.00, 165, 13, 2, NULL, 'active', 'Regular', 'in stock'),
(130, 'Myra-E Moisturizer 50g', 'Skincare & Cosmetics', 4800016021268, 'Vitamin E moisturizing cream', '0', 0, '2025-12-31', 35, 85.00, 166, 13, 2, NULL, 'active', 'Regular', 'in stock');

-- Update the auto-increment value for product_id
ALTER TABLE `tbl_product` AUTO_INCREMENT = 131; 
-- Fix FIFO Quantity Sync System
-- This corrects the UpdateProductTotalQuantity function and triggers

-- First, drop any existing triggers that might be causing issues
DROP TRIGGER IF EXISTS after_fifo_stock_update;
DROP TRIGGER IF EXISTS after_fifo_stock_insert;
DROP TRIGGER IF EXISTS after_fifo_stock_delete;

-- Drop the function if it exists
DROP FUNCTION IF EXISTS UpdateProductTotalQuantity;

-- Create a function to update product total quantity
DELIMITER $$

CREATE FUNCTION UpdateProductTotalQuantity(product_id_param INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from all stock summary for this product
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_stock_summary 
    WHERE product_id = product_id_param;
    
    -- Update the product's total quantity
    UPDATE tbl_product 
    SET quantity = total_qty,
        stock_status = CASE 
            WHEN total_qty <= 0 THEN 'out of stock'
            WHEN total_qty <= 10 THEN 'low stock'
            ELSE 'in stock'
        END
    WHERE product_id = product_id_param;
    
    RETURN total_qty;
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when stock summary changes
DELIMITER $$

CREATE TRIGGER after_stock_summary_update
AFTER UPDATE ON tbl_stock_summary
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when stock summary is updated
    SELECT UpdateProductTotalQuantity(NEW.product_id);
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when stock summary is inserted
DELIMITER $$

CREATE TRIGGER after_stock_summary_insert
AFTER INSERT ON tbl_stock_summary
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when new stock summary is added
    SELECT UpdateProductTotalQuantity(NEW.product_id);
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when stock summary is deleted
DELIMITER $$

CREATE TRIGGER after_stock_summary_delete
AFTER DELETE ON tbl_stock_summary
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when stock summary is removed
    SELECT UpdateProductTotalQuantity(OLD.product_id);
END$$

DELIMITER ;

-- Update all existing products to sync their quantities with stock summary
UPDATE tbl_product p 
SET p.quantity = (
    SELECT COALESCE(SUM(ss.available_quantity), 0)
    FROM tbl_stock_summary ss 
    WHERE ss.product_id = p.product_id
),
p.stock_status = CASE 
    WHEN (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    ) <= 0 THEN 'out of stock'
    WHEN (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    ) <= 10 THEN 'low stock'
    ELSE 'in stock'
END;

-- Show the sync status
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(ss.available_quantity), 0) as stock_summary_total_qty,
    p.stock_status,
    CASE 
        WHEN p.quantity = COALESCE(SUM(ss.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
FROM tbl_product p
LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status
ORDER BY p.product_id; 
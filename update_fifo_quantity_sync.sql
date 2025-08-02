-- Update FIFO Quantity Sync System
-- This ensures tbl_product.quantity is always the sum of all available_quantity from tbl_fifo_stock

-- Create a function to update product total quantity
DELIMITER $$

CREATE FUNCTION UpdateProductTotalQuantity(product_id_param INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from all FIFO batches for this product
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = product_id_param;
    
    -- Update the product's total quantity
    UPDATE tbl_product 
    SET quantity = total_qty 
    WHERE product_id = product_id_param;
    
    RETURN total_qty;
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when FIFO stock changes
DELIMITER $$

CREATE TRIGGER after_fifo_stock_update
AFTER UPDATE ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when FIFO stock is updated
    CALL UpdateProductTotalQuantity(NEW.product_id);
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when FIFO stock is inserted
DELIMITER $$

CREATE TRIGGER after_fifo_stock_insert
AFTER INSERT ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when new FIFO stock is added
    CALL UpdateProductTotalQuantity(NEW.product_id);
END$$

DELIMITER ;

-- Create trigger to automatically update product quantity when FIFO stock is deleted
DELIMITER $$

CREATE TRIGGER after_fifo_stock_delete
AFTER DELETE ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    -- Update the product's total quantity when FIFO stock is removed
    CALL UpdateProductTotalQuantity(OLD.product_id);
END$$

DELIMITER ;

-- Update all existing products to sync their quantities with FIFO stock
UPDATE tbl_product p 
SET p.quantity = (
    SELECT COALESCE(SUM(fs.available_quantity), 0)
    FROM tbl_fifo_stock fs 
    WHERE fs.product_id = p.product_id
);

-- Show the sync status
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(fs.available_quantity), 0) as fifo_total_qty,
    CASE 
        WHEN p.quantity = COALESCE(SUM(fs.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
FROM tbl_product p
LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity
ORDER BY p.product_id; 
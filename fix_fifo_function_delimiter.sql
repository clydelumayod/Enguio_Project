-- Fix UpdateProductTotalFromFIFO Function with Proper Delimiter
-- This handles the delimiter issue in MariaDB/MySQL

-- Drop the function if it exists
DROP FUNCTION IF EXISTS UpdateProductTotalFromFIFO;

-- Set delimiter for function creation
DELIMITER $$

-- Create the function with proper syntax
CREATE FUNCTION UpdateProductTotalFromFIFO(product_id_param INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from FIFO
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = product_id_param;
    
    -- Update product quantity
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

-- Reset delimiter back to default
DELIMITER ;

-- Test the function
SELECT UpdateProductTotalFromFIFO(169) as test_result; 
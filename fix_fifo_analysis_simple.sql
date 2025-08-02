-- Fix FIFO Analysis System - Simplified Version
-- This fixes the UpdateProductTotalFromFIFO function and trigger issues

-- First, drop any existing triggers that might be causing issues
DROP TRIGGER IF EXISTS after_fifo_stock_insert_sync;
DROP TRIGGER IF EXISTS after_fifo_stock_update_sync;
DROP TRIGGER IF EXISTS after_fifo_stock_delete_sync;

-- Drop the function if it exists
DROP FUNCTION IF EXISTS UpdateProductTotalFromFIFO;

-- Create function to update product total quantity
CREATE OR REPLACE FUNCTION UpdateProductTotalFromFIFO(product_id_param INT) 
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
END;

-- Update all existing products to sync with FIFO totals
UPDATE tbl_product p 
SET p.quantity = (
    SELECT COALESCE(SUM(fs.available_quantity), 0)
    FROM tbl_fifo_stock fs 
    WHERE fs.product_id = p.product_id
),
p.stock_status = CASE 
    WHEN (
        SELECT COALESCE(SUM(fs.available_quantity), 0)
        FROM tbl_fifo_stock fs 
        WHERE fs.product_id = p.product_id
    ) <= 0 THEN 'out of stock'
    WHEN (
        SELECT COALESCE(SUM(fs.available_quantity), 0)
        FROM tbl_fifo_stock fs 
        WHERE fs.product_id = p.product_id
    ) <= 10 THEN 'low stock'
    ELSE 'in stock'
END;

-- Test the function
SELECT UpdateProductTotalFromFIFO(169) as test_result;

-- Show current status
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as product_total_qty,
    COALESCE(SUM(fs.available_quantity), 0) as fifo_total_qty,
    p.stock_status,
    CASE 
        WHEN p.quantity = COALESCE(SUM(fs.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
FROM tbl_product p
LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status
ORDER BY p.product_id
LIMIT 10; 
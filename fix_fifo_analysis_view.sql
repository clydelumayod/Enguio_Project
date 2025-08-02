-- Fix FIFO Analysis System - Corrected Version
-- This fixes the UpdateProductTotalFromFIFO function and trigger issues

-- First, drop any existing triggers that might be causing issues
DROP TRIGGER IF EXISTS after_fifo_stock_insert_sync;
DROP TRIGGER IF EXISTS after_fifo_stock_update_sync;
DROP TRIGGER IF EXISTS after_fifo_stock_delete_sync;

-- Drop the function if it exists
DROP FUNCTION IF EXISTS UpdateProductTotalFromFIFO;

-- 1. Create a view to show all batch entries with old vs new classification
CREATE OR REPLACE VIEW v_fifo_batch_analysis AS
SELECT 
    fs.fifo_id,
    fs.product_id,
    p.product_name,
    fs.batch_id,
    fs.batch_reference,
    fs.quantity as original_quantity,
    fs.available_quantity as current_quantity,
    fs.unit_cost,
    fs.expiration_date,
    fs.entry_date,
    fs.created_at,
    fs.entry_by,
    -- Classify as old or new based on entry date
    CASE 
        WHEN fs.entry_date <= (
            SELECT MIN(entry_date) 
            FROM tbl_fifo_stock fs2 
            WHERE fs2.product_id = fs.product_id
        ) THEN 'OLD'
        ELSE 'NEW'
    END as batch_type,
    -- Rank batches by entry date (1 = oldest)
    ROW_NUMBER() OVER (
        PARTITION BY fs.product_id 
        ORDER BY fs.entry_date ASC, fs.fifo_id ASC
    ) as batch_order,
    -- Calculate running total
    SUM(fs.quantity) OVER (
        PARTITION BY fs.product_id 
        ORDER BY fs.entry_date ASC, fs.fifo_id ASC
    ) as running_total_original,
    SUM(fs.available_quantity) OVER (
        PARTITION BY fs.product_id 
        ORDER BY fs.entry_date ASC, fs.fifo_id ASC
    ) as running_total_current
FROM tbl_fifo_stock fs
LEFT JOIN tbl_product p ON fs.product_id = p.product_id
WHERE p.status = 'active'
ORDER BY fs.product_id, fs.entry_date ASC, fs.fifo_id ASC;

-- 2. Create a view for product summary with total quantities
CREATE OR REPLACE VIEW v_product_fifo_summary AS
SELECT 
    p.product_id,
    p.product_name,
    p.category,
    p.quantity as product_total_quantity,
    -- FIFO totals
    COALESCE(SUM(fs.quantity), 0) as fifo_total_original,
    COALESCE(SUM(fs.available_quantity), 0) as fifo_total_current,
    -- Old batch totals (first 50% of batches by entry date)
    COALESCE(SUM(
        CASE 
            WHEN fs.entry_date <= (
                SELECT MIN(entry_date) 
                FROM tbl_fifo_stock fs2 
                WHERE fs2.product_id = fs.product_id
            ) THEN fs.available_quantity
            ELSE 0
        END
    ), 0) as old_batch_quantity,
    -- New batch totals (remaining batches)
    COALESCE(SUM(
        CASE 
            WHEN fs.entry_date > (
                SELECT MIN(entry_date) 
                FROM tbl_fifo_stock fs2 
                WHERE fs2.product_id = fs.product_id
            ) THEN fs.available_quantity
            ELSE 0
        END
    ), 0) as new_batch_quantity,
    -- Batch counts
    COUNT(fs.fifo_id) as total_batches,
    COUNT(CASE WHEN fs.available_quantity > 0 THEN 1 END) as active_batches,
    -- Sync status
    CASE 
        WHEN p.quantity = COALESCE(SUM(fs.available_quantity), 0) THEN 'SYNCED'
        ELSE 'NOT SYNCED'
    END as sync_status
FROM tbl_product p
LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.category, p.quantity
ORDER BY p.product_id;

-- 3. Create a view for detailed old vs new breakdown
CREATE OR REPLACE VIEW v_fifo_old_vs_new AS
SELECT 
    product_id,
    product_name,
    batch_type,
    COUNT(*) as batch_count,
    SUM(original_quantity) as total_original_quantity,
    SUM(current_quantity) as total_current_quantity,
    MIN(entry_date) as earliest_entry,
    MAX(entry_date) as latest_entry,
    AVG(unit_cost) as avg_unit_cost
FROM v_fifo_batch_analysis
GROUP BY product_id, product_name, batch_type
ORDER BY product_id, batch_type;

-- 4. Create function to update product total quantity
DELIMITER $$

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
END$$

DELIMITER ;

-- 5. Create triggers to auto-sync product quantities (without result sets)
DELIMITER $$

CREATE TRIGGER after_fifo_stock_insert_sync
AFTER INSERT ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from FIFO
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = NEW.product_id;
    
    -- Update product quantity directly
    UPDATE tbl_product 
    SET quantity = total_qty,
        stock_status = CASE 
            WHEN total_qty <= 0 THEN 'out of stock'
            WHEN total_qty <= 10 THEN 'low stock'
            ELSE 'in stock'
        END
    WHERE product_id = NEW.product_id;
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_fifo_stock_update_sync
AFTER UPDATE ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from FIFO
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = NEW.product_id;
    
    -- Update product quantity directly
    UPDATE tbl_product 
    SET quantity = total_qty,
        stock_status = CASE 
            WHEN total_qty <= 0 THEN 'out of stock'
            WHEN total_qty <= 10 THEN 'low stock'
            ELSE 'in stock'
        END
    WHERE product_id = NEW.product_id;
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_fifo_stock_delete_sync
AFTER DELETE ON tbl_fifo_stock
FOR EACH ROW
BEGIN
    DECLARE total_qty INT DEFAULT 0;
    
    -- Calculate total available quantity from FIFO
    SELECT COALESCE(SUM(available_quantity), 0) INTO total_qty
    FROM tbl_fifo_stock 
    WHERE product_id = OLD.product_id;
    
    -- Update product quantity directly
    UPDATE tbl_product 
    SET quantity = total_qty,
        stock_status = CASE 
            WHEN total_qty <= 0 THEN 'out of stock'
            WHEN total_qty <= 10 THEN 'low stock'
            ELSE 'in stock'
        END
    WHERE product_id = OLD.product_id;
END$$

DELIMITER ;

-- 6. Create a view for inventory transfer recommendations (FIFO order)
CREATE OR REPLACE VIEW v_fifo_transfer_recommendations AS
SELECT 
    fs.fifo_id,
    fs.product_id,
    p.product_name,
    fs.batch_id,
    fs.batch_reference,
    fs.quantity as original_quantity,
    fs.available_quantity as current_quantity,
    fs.unit_cost,
    fs.expiration_date,
    fs.entry_date,
    -- Priority for transfer (1 = highest priority, oldest first)
    ROW_NUMBER() OVER (
        PARTITION BY fs.product_id 
        ORDER BY fs.entry_date ASC, fs.fifo_id ASC
    ) as transfer_priority,
    -- Days until expiry
    DATEDIFF(fs.expiration_date, CURDATE()) as days_to_expiry,
    -- Urgency level
    CASE 
        WHEN DATEDIFF(fs.expiration_date, CURDATE()) <= 30 THEN 'URGENT'
        WHEN DATEDIFF(fs.expiration_date, CURDATE()) <= 90 THEN 'MODERATE'
        ELSE 'GOOD'
    END as urgency_level,
    -- Batch age in days
    DATEDIFF(CURDATE(), fs.entry_date) as batch_age_days
FROM tbl_fifo_stock fs
LEFT JOIN tbl_product p ON fs.product_id = p.product_id
WHERE p.status = 'active' AND fs.available_quantity > 0
ORDER BY fs.product_id, fs.entry_date ASC, fs.fifo_id ASC;

-- 7. Update all existing products to sync with FIFO totals
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

-- 8. Show sample data for verification
SELECT 'FIFO BATCH ANALYSIS SAMPLE' as info;
SELECT * FROM v_fifo_batch_analysis LIMIT 5;

SELECT 'PRODUCT FIFO SUMMARY SAMPLE' as info;
SELECT * FROM v_product_fifo_summary LIMIT 5;

SELECT 'OLD VS NEW BREAKDOWN SAMPLE' as info;
SELECT * FROM v_fifo_old_vs_new LIMIT 5;

SELECT 'TRANSFER RECOMMENDATIONS SAMPLE' as info;
SELECT * FROM v_fifo_transfer_recommendations LIMIT 5; 
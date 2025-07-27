-- Product Stock Tracking Queries
-- MySQL queries for tracking product additions and restocks with batch information
-- For inventory auditing purposes

-- ============================================================================
-- 1. BASIC STOCK HISTORY QUERY
-- Shows when products were added or restocked with batch information
-- ============================================================================

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    b.batch_id,
    b.batch_reference,
    b.entry_date as date_received,
    b.entry_time as time_received,
    b.entry_by,
    b.order_no,
    b.order_ref,
    s.supplier_name,
    l.location_name,
    p.quantity as current_quantity,
    p.unit_price,
    p.expiration,
    p.date_added,
    p.stock_status,
    CASE 
        WHEN p.date_added = b.entry_date THEN 'New Entry'
        WHEN p.date_added != b.entry_date THEN 'Restocked'
        ELSE 'Unknown'
    END as action_type,
    CONCAT(
        'Product: ', p.product_name, ' | ',
        'Batch: ', COALESCE(b.batch_reference, 'N/A'), ' | ',
        'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
        'Qty: ', p.quantity, ' | ',
        'Location: ', l.location_name
    ) as audit_summary
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
ORDER BY b.entry_date DESC, b.entry_time DESC, p.product_name;

-- ============================================================================
-- 2. FILTERED STOCK HISTORY QUERY
-- Example: Filter by specific product, location, and date range
-- ============================================================================

-- Replace the values below with your desired filters
SET @product_id = NULL;        -- Set to product ID or NULL for all products
SET @location_id = NULL;       -- Set to location ID or NULL for all locations
SET @date_from = '2025-01-01'; -- Set start date or NULL for no start limit
SET @date_to = '2025-12-31';   -- Set end date or NULL for no end limit
SET @batch_reference = NULL;   -- Set batch reference or NULL for all batches

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    b.batch_id,
    b.batch_reference,
    b.entry_date as date_received,
    b.entry_time as time_received,
    b.entry_by,
    b.order_no,
    b.order_ref,
    s.supplier_name,
    l.location_name,
    p.quantity as current_quantity,
    p.unit_price,
    p.expiration,
    p.date_added,
    p.stock_status,
    CASE 
        WHEN p.date_added = b.entry_date THEN 'New Entry'
        WHEN p.date_added != b.entry_date THEN 'Restocked'
        ELSE 'Unknown'
    END as action_type
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
    AND (@product_id IS NULL OR p.product_id = @product_id)
    AND (@location_id IS NULL OR p.location_id = @location_id)
    AND (@date_from IS NULL OR b.entry_date >= @date_from)
    AND (@date_to IS NULL OR b.entry_date <= @date_to)
    AND (@batch_reference IS NULL OR b.batch_reference LIKE CONCAT('%', @batch_reference, '%'))
ORDER BY b.entry_date DESC, b.entry_time DESC, p.product_name;

-- ============================================================================
-- 3. STOCK MOVEMENT HISTORY QUERY
-- Uses tbl_stock_movements for comprehensive movement tracking
-- ============================================================================

SELECT 
    sm.movement_id,
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    b.batch_id,
    b.batch_reference,
    b.entry_date as batch_date_received,
    sm.movement_type,
    sm.quantity,
    sm.remaining_quantity,
    sm.unit_cost,
    sm.expiration_date,
    sm.movement_date,
    sm.reference_no,
    sm.notes,
    sm.created_by,
    s.supplier_name,
    l.location_name,
    CASE 
        WHEN sm.movement_type = 'IN' THEN 'Stock Added'
        WHEN sm.movement_type = 'OUT' THEN 'Stock Consumed'
        WHEN sm.movement_type = 'ADJUSTMENT' THEN 'Stock Adjusted'
        ELSE 'Unknown'
    END as action_description,
    CONCAT(
        p.product_name, ' - ',
        CASE 
            WHEN sm.movement_type = 'IN' THEN 'Added'
            WHEN sm.movement_type = 'OUT' THEN 'Consumed'
            WHEN sm.movement_type = 'ADJUSTMENT' THEN 'Adjusted'
        END,
        ' ', sm.quantity, ' units',
        ' (Batch: ', COALESCE(b.batch_reference, 'N/A'), ')'
    ) as movement_summary
FROM tbl_stock_movements sm
JOIN tbl_product p ON sm.product_id = p.product_id
LEFT JOIN tbl_batch b ON sm.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
ORDER BY sm.movement_date DESC, p.product_name;

-- ============================================================================
-- 4. FILTERED MOVEMENT HISTORY QUERY
-- Example: Filter by movement type and date range
-- ============================================================================

-- Replace the values below with your desired filters
SET @movement_product_id = NULL;    -- Set to product ID or NULL for all products
SET @movement_location_id = NULL;   -- Set to location ID or NULL for all locations
SET @movement_date_from = '2025-01-01'; -- Set start date or NULL for no start limit
SET @movement_date_to = '2025-12-31';   -- Set end date or NULL for no end limit
SET @movement_type = 'IN';          -- Set to 'IN', 'OUT', 'ADJUSTMENT' or NULL for all

SELECT 
    sm.movement_id,
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    b.batch_id,
    b.batch_reference,
    b.entry_date as batch_date_received,
    sm.movement_type,
    sm.quantity,
    sm.remaining_quantity,
    sm.unit_cost,
    sm.expiration_date,
    sm.movement_date,
    sm.reference_no,
    sm.notes,
    sm.created_by,
    s.supplier_name,
    l.location_name,
    CASE 
        WHEN sm.movement_type = 'IN' THEN 'Stock Added'
        WHEN sm.movement_type = 'OUT' THEN 'Stock Consumed'
        WHEN sm.movement_type = 'ADJUSTMENT' THEN 'Stock Adjusted'
        ELSE 'Unknown'
    END as action_description
FROM tbl_stock_movements sm
JOIN tbl_product p ON sm.product_id = p.product_id
LEFT JOIN tbl_batch b ON sm.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
    AND (@movement_product_id IS NULL OR p.product_id = @movement_product_id)
    AND (@movement_location_id IS NULL OR p.location_id = @movement_location_id)
    AND (@movement_date_from IS NULL OR DATE(sm.movement_date) >= @movement_date_from)
    AND (@movement_date_to IS NULL OR DATE(sm.movement_date) <= @movement_date_to)
    AND (@movement_type IS NULL OR sm.movement_type = @movement_type)
ORDER BY sm.movement_date DESC, p.product_name;

-- ============================================================================
-- 5. PRODUCT STOCK SUMMARY BY LOCATION
-- Shows current stock levels with batch information
-- ============================================================================

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.quantity as current_stock,
    p.unit_price,
    p.expiration,
    p.date_added,
    p.stock_status,
    b.batch_id,
    b.batch_reference,
    b.entry_date as last_batch_date,
    b.entry_by as last_added_by,
    s.supplier_name,
    l.location_name,
    CONCAT(
        p.product_name, ' | ',
        'Stock: ', p.quantity, ' | ',
        'Last Batch: ', COALESCE(DATE_FORMAT(b.entry_date, '%M %d, %Y'), 'N/A'), ' | ',
        'Location: ', l.location_name
    ) as stock_summary
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
ORDER BY l.location_name, p.product_name;

-- ============================================================================
-- 6. FILTERED STOCK SUMMARY QUERY
-- Example: Filter by specific location
-- ============================================================================

-- Replace with your desired location ID
SET @summary_location_id = 2; -- Set to location ID or NULL for all locations

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.quantity as current_stock,
    p.unit_price,
    p.expiration,
    p.date_added,
    p.stock_status,
    b.batch_id,
    b.batch_reference,
    b.entry_date as last_batch_date,
    b.entry_by as last_added_by,
    s.supplier_name,
    l.location_name
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
    AND (@summary_location_id IS NULL OR p.location_id = @summary_location_id)
ORDER BY l.location_name, p.product_name;

-- ============================================================================
-- 7. PRODUCTS BY BATCH REFERENCE
-- Useful for tracking specific batches across products
-- ============================================================================

-- Replace with your desired batch reference
SET @batch_reference_search = 'BR-20250716-232504';

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.quantity,
    p.unit_price,
    p.expiration,
    p.date_added,
    b.batch_id,
    b.batch_reference,
    b.entry_date,
    b.entry_time,
    b.entry_by,
    b.order_no,
    s.supplier_name,
    l.location_name,
    CONCAT(
        'Batch: ', b.batch_reference, ' | ',
        'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
        'Products: ', COUNT(*) OVER(), ' items'
    ) as batch_summary
FROM tbl_product p
JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE b.batch_reference = @batch_reference_search 
    AND p.status = 'active'
ORDER BY p.product_name;

-- ============================================================================
-- 8. COMPREHENSIVE STOCK AUDIT REPORT
-- Complete audit report for inventory auditing
-- ============================================================================

-- Replace the values below with your desired filters
SET @audit_date_from = '2025-01-01'; -- Set start date or NULL for no start limit
SET @audit_date_to = '2025-12-31';   -- Set end date or NULL for no end limit
SET @audit_location_id = NULL;       -- Set to location ID or NULL for all locations

SELECT 
    -- Product Information
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.quantity as current_stock,
    p.unit_price,
    p.expiration,
    p.date_added,
    p.stock_status,
    
    -- Batch Information
    b.batch_id,
    b.batch_reference,
    b.entry_date as batch_received_date,
    b.entry_time as batch_received_time,
    b.entry_by as batch_added_by,
    b.order_no,
    b.order_ref,
    
    -- Supplier Information
    s.supplier_name,
    s.supplier_contact,
    
    -- Location Information
    l.location_name,
    
    -- Movement Information (latest)
    (
        SELECT sm.movement_date 
        FROM tbl_stock_movements sm 
        WHERE sm.product_id = p.product_id 
        ORDER BY sm.movement_date DESC 
        LIMIT 1
    ) as last_movement_date,
    
    -- Audit Information
    CASE 
        WHEN p.date_added = b.entry_date THEN 'New Entry'
        WHEN p.date_added != b.entry_date THEN 'Restocked'
        ELSE 'Unknown'
    END as entry_type,
    
    CONCAT(
        'Product: ', p.product_name, ' | ',
        'Batch: ', COALESCE(b.batch_reference, 'N/A'), ' | ',
        'Received: ', DATE_FORMAT(b.entry_date, '%M %d, %Y'), ' | ',
        'Current Stock: ', p.quantity, ' | ',
        'Location: ', l.location_name, ' | ',
        'Supplier: ', COALESCE(s.supplier_name, 'N/A')
    ) as audit_summary
    
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
    AND (@audit_date_from IS NULL OR b.entry_date >= @audit_date_from)
    AND (@audit_date_to IS NULL OR b.entry_date <= @audit_date_to)
    AND (@audit_location_id IS NULL OR p.location_id = @audit_location_id)
ORDER BY l.location_name, b.entry_date DESC, p.product_name;

-- ============================================================================
-- 9. SUMMARY STATISTICS
-- Quick overview of stock tracking data
-- ============================================================================

-- Total products with batch tracking
SELECT 
    COUNT(*) as total_products,
    COUNT(b.batch_id) as products_with_batches,
    COUNT(*) - COUNT(b.batch_id) as products_without_batches
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
WHERE p.status = 'active';

-- Products by location
SELECT 
    l.location_name,
    COUNT(*) as product_count,
    SUM(p.quantity) as total_stock,
    AVG(p.unit_price) as avg_unit_price
FROM tbl_product p
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
GROUP BY l.location_id, l.location_name
ORDER BY l.location_name;

-- Recent stock additions (last 30 days)
SELECT 
    DATE(b.entry_date) as date_received,
    COUNT(*) as products_added,
    SUM(p.quantity) as total_quantity_added
FROM tbl_product p
JOIN tbl_batch b ON p.batch_id = b.batch_id
WHERE p.status = 'active'
    AND b.entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(b.entry_date)
ORDER BY date_received DESC;

-- ============================================================================
-- 10. EXPIRING PRODUCTS WITH BATCH INFORMATION
-- Products that will expire soon with their batch details
-- ============================================================================

SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.quantity as current_stock,
    p.expiration,
    DATEDIFF(p.expiration, CURDATE()) as days_until_expiry,
    b.batch_reference,
    b.entry_date as batch_received_date,
    s.supplier_name,
    l.location_name,
    CASE 
        WHEN DATEDIFF(p.expiration, CURDATE()) <= 30 THEN 'Expiring Soon (≤30 days)'
        WHEN DATEDIFF(p.expiration, CURDATE()) <= 60 THEN 'Expiring Soon (≤60 days)'
        WHEN DATEDIFF(p.expiration, CURDATE()) <= 90 THEN 'Expiring Soon (≤90 days)'
        ELSE 'Safe'
    END as expiry_status
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_supplier s ON b.supplier_id = s.supplier_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
    AND p.expiration IS NOT NULL
    AND p.expiration >= CURDATE()
ORDER BY p.expiration ASC, p.product_name;

-- ============================================================================
-- 11. SUPPLIER PERFORMANCE TRACKING
-- Track which suppliers provide products and when
-- ============================================================================

SELECT 
    s.supplier_id,
    s.supplier_name,
    s.supplier_contact,
    COUNT(DISTINCT p.product_id) as total_products,
    SUM(p.quantity) as total_stock_provided,
    MIN(b.entry_date) as first_delivery_date,
    MAX(b.entry_date) as last_delivery_date,
    COUNT(DISTINCT b.batch_id) as total_batches,
    AVG(p.unit_price) as avg_product_price
FROM tbl_supplier s
LEFT JOIN tbl_batch b ON s.supplier_id = b.supplier_id
LEFT JOIN tbl_product p ON b.batch_id = p.batch_id
WHERE s.status = 'active'
    AND p.status = 'active'
GROUP BY s.supplier_id, s.supplier_name, s.supplier_contact
ORDER BY total_products DESC, last_delivery_date DESC;

-- ============================================================================
-- 12. LOCATION INVENTORY VALUE
-- Calculate inventory value by location
-- ============================================================================

SELECT 
    l.location_id,
    l.location_name,
    COUNT(DISTINCT p.product_id) as unique_products,
    SUM(p.quantity) as total_units,
    SUM(p.quantity * p.unit_price) as total_inventory_value,
    AVG(p.unit_price) as avg_unit_price,
    MIN(b.entry_date) as oldest_stock_date,
    MAX(b.entry_date) as newest_stock_date
FROM tbl_location l
LEFT JOIN tbl_product p ON l.location_id = p.location_id
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
WHERE l.status = 'active'
    AND p.status = 'active'
GROUP BY l.location_id, l.location_name
ORDER BY total_inventory_value DESC; 
-- FIX ALL PRODUCTS QUANTITY - COMPLETE DATABASE SYNC
-- This will fix LAHAT ng products, hindi lang Nova!

-- Step 1: Show ALL products na may mali na quantities
SELECT 'LAHAT NG PRODUCTS NA MAY MALI NA QUANTITIES' as message;
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as current_qty_sa_product_table,
    COALESCE(SUM(ss.available_quantity), 0) as correct_qty_from_stock_summary,
    p.quantity - COALESCE(SUM(ss.available_quantity), 0) as difference,
    CASE 
        WHEN p.quantity > COALESCE(SUM(ss.available_quantity), 0) THEN 'PRODUCT TABLE SOBRANG TAAS'
        WHEN p.quantity < COALESCE(SUM(ss.available_quantity), 0) THEN 'PRODUCT TABLE KULANG'
        ELSE 'OK NA'
    END as problem
FROM tbl_product p
LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity
HAVING p.quantity != COALESCE(SUM(ss.available_quantity), 0)
ORDER BY ABS(p.quantity - COALESCE(SUM(ss.available_quantity), 0)) DESC;

-- Step 2: I-FIX LAHAT NG PRODUCTS!
UPDATE tbl_product p 
SET 
    p.quantity = (
        SELECT COALESCE(SUM(ss.available_quantity), 0)
        FROM tbl_stock_summary ss 
        WHERE ss.product_id = p.product_id
    ),
    p.stock_status = CASE 
        WHEN (
            SELECT COALESCE(SUM(ss.available_quantity), 0)
            FROM tbl_stock_summary ss 
            WHERE ss.product_id = p.product_id
        ) = 0 THEN 'out of stock'
        WHEN (
            SELECT COALESCE(SUM(ss.available_quantity), 0)
            FROM tbl_stock_summary ss 
            WHERE ss.product_id = p.product_id
        ) <= 10 THEN 'low stock'
        ELSE 'in stock'
    END
WHERE p.status = 'active';

-- Step 3: Show results - LAHAT NA NA-FIX!
SELECT 'AFTER FIX - LAHAT NG PRODUCTS' as message;
SELECT 
    'SUMMARY' as type,
    COUNT(*) as total_products,
    SUM(CASE WHEN p.quantity = COALESCE(SUM(ss.available_quantity), 0) THEN 1 ELSE 0 END) as synchronized_products,
    SUM(CASE WHEN p.quantity != COALESCE(SUM(ss.available_quantity), 0) THEN 1 ELSE 0 END) as still_not_sync
FROM tbl_product p
LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
WHERE p.status = 'active';

-- Step 4: Show ALL products after fix (sample lang mga first 20)
SELECT 'SAMPLE - FIRST 20 PRODUCTS AFTER FIX' as message;
SELECT 
    p.product_id,
    p.product_name,
    p.quantity as fixed_quantity,
    p.stock_status,
    p.location_id,
    COALESCE(SUM(ss.available_quantity), 0) as stock_summary_total,
    CASE 
        WHEN p.quantity = COALESCE(SUM(ss.available_quantity), 0) THEN '✓ OK NA'
        ELSE '✗ MALI PA RIN'
    END as status
FROM tbl_product p
LEFT JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
WHERE p.status = 'active'
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status, p.location_id
ORDER BY p.product_id
LIMIT 20;

-- Step 5: Special check para sa specific products na na-transfer
SELECT 'PRODUCTS NA MAY RECENT TRANSFERS' as message;
SELECT DISTINCT
    p.product_id,
    p.product_name,
    p.quantity,
    p.stock_status,
    COUNT(td.transfer_dtl_id) as transfer_count,
    SUM(td.qty) as total_transferred
FROM tbl_product p
JOIN tbl_transfer_dtl td ON p.product_id = td.product_id
JOIN tbl_transfer_header th ON td.transfer_header_id = th.transfer_header_id
WHERE th.date >= '2025-08-01'  -- Recent transfers lang
GROUP BY p.product_id, p.product_name, p.quantity, p.stock_status
ORDER BY total_transferred DESC
LIMIT 10;
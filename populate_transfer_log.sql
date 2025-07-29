-- Transfer Log Population SQL Script
-- Run this script in phpMyAdmin or MySQL to populate tbl_transfer_log from existing transfer data

-- First, let's see what we have
SELECT 'Current transfer log entries:' as info, COUNT(*) as count FROM tbl_transfer_log;

SELECT 'Transfer headers found:' as info, COUNT(*) as count FROM tbl_transfer_header;

SELECT 'Transfer details found:' as info, COUNT(*) as count FROM tbl_transfer_dtl;

-- Show sample transfer data
SELECT 
    th.transfer_header_id,
    th.date,
    sl.location_name as source_location,
    dl.location_name as destination_location,
    COUNT(td.product_id) as products_count
FROM tbl_transfer_header th
LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
LEFT JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
GROUP BY th.transfer_header_id
ORDER BY th.transfer_header_id;

-- Insert transfer log entries from existing transfer data
INSERT INTO tbl_transfer_log (
    transfer_id, 
    product_id, 
    product_name,
    from_location, 
    to_location, 
    quantity, 
    transfer_date, 
    created_at
)
SELECT 
    th.transfer_header_id as transfer_id,
    td.product_id,
    COALESCE(p.product_name, CONCAT('Product ID: ', td.product_id)) as product_name,
    COALESCE(sl.location_name, 'Unknown') as from_location,
    COALESCE(dl.location_name, 'Unknown') as to_location,
    td.quantity,
    th.date as transfer_date,
    NOW() as created_at
FROM tbl_transfer_header th
JOIN tbl_transfer_dtl td ON th.transfer_header_id = td.transfer_header_id
LEFT JOIN tbl_product p ON td.product_id = p.product_id
LEFT JOIN tbl_location sl ON th.source_location_id = sl.location_id
LEFT JOIN tbl_location dl ON th.destination_location_id = dl.location_id
WHERE NOT EXISTS (
    -- Skip if log entry already exists
    SELECT 1 FROM tbl_transfer_log tl 
    WHERE tl.transfer_id = th.transfer_header_id 
    AND tl.product_id = td.product_id
);

-- Show final results
SELECT 'Final transfer log entries:' as info, COUNT(*) as count FROM tbl_transfer_log;

-- Show sample of populated data
SELECT 
    CONCAT('TR-', transfer_id) as transfer_id,
    product_name,
    from_location,
    to_location,
    quantity,
    transfer_date,
    created_at
FROM tbl_transfer_log 
ORDER BY created_at DESC 
LIMIT 10;

-- Show summary by transfer
SELECT 
    CONCAT('TR-', transfer_id) as transfer_id,
    COUNT(*) as products_logged,
    SUM(quantity) as total_quantity,
    MIN(transfer_date) as transfer_date
FROM tbl_transfer_log 
GROUP BY transfer_id
ORDER BY transfer_id; 
# FIFO Transfer Products Fix Summary

## Issue Identified
The **InventoryTransfer.js** component was showing a blank products table because the backend API (`get_products` endpoint) was not properly implementing FIFO (First In, First Out) logic.

## Root Cause
The original API query was:
- Using `tbl_product.quantity` instead of `tbl_stock_summary.available_quantity`
- Not properly joining with `tbl_batch` to get entry dates
- Not implementing FIFO logic to show only the oldest batch per product
- Not filtering properly by location in the stock summary table

## Solutions Implemented

### 1. Updated Backend API (`Api/backend_mysqli.php`)
**Location:** Lines 838-964

**Changes Made:**
- Replaced the old query with proper FIFO logic using **MySQL 8+ CTE (Common Table Expression)**
- Implemented `ROW_NUMBER() OVER()` window function to rank batches by entry date
- Only returns the oldest available batch per product (`WHERE batch_rank = 1`)
- Proper joins between `tbl_product`, `tbl_stock_summary`, and `tbl_batch`
- Filters by `available_quantity > 0` instead of product quantity

**New Query Structure:**
```sql
WITH ranked_batches AS (
  SELECT 
    p.product_id,
    p.product_name,
    -- ... other fields
    ROW_NUMBER() OVER (
      PARTITION BY p.product_id, ss.location_id 
      ORDER BY bt.entry_date ASC, bt.batch_id ASC
    ) as batch_rank
  FROM tbl_product p
  INNER JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
  INNER JOIN tbl_batch bt ON ss.batch_id = bt.batch_id
  WHERE ss.available_quantity > 0
)
SELECT * FROM ranked_batches WHERE batch_rank = 1
```

### 2. Created SQL Reference (`fifo_oldest_batch_query.sql`)
- Provides the complete FIFO query with sample output
- Includes alternative query for older MySQL versions
- Documents expected behavior and table relationships

### 3. Created Compatibility Helper (`fifo_mysql_compatibility.php`)
- Provides fallback queries for MySQL 5.7 and below
- Auto-detects MySQL version and uses appropriate query
- Includes testing functions to verify both approaches

### 4. Created Test Script (`test_fifo_products_api.php`)
- Tests the API endpoint directly
- Validates FIFO behavior (each product appears only once)
- Provides debugging information and verification steps

## Expected Results

### Before Fix:
- ❌ Blank products table in transfer interface
- ❌ No FIFO logic - could transfer from any batch
- ❌ Inconsistent inventory rotation

### After Fix:
- ✅ Products table shows available items with oldest batches first
- ✅ Each product appears only once (oldest batch only)
- ✅ Proper FIFO inventory rotation ensured
- ✅ Correct available quantities from stock summary

## Required Database Tables

The fix assumes these tables exist:
1. **`tbl_product`** - Product master data
2. **`tbl_batch`** - Batch information with `entry_date`
3. **`tbl_stock_summary`** - Links products to batches with `available_quantity`
4. **`tbl_brand`** - Brand information (optional)
5. **`tbl_supplier`** - Supplier information (optional)

## Testing Steps

1. **Test the API directly:**
   ```bash
   php test_fifo_products_api.php
   ```

2. **Check in the web interface:**
   - Go to Inventory Transfer → Create Transfer
   - Complete Step 1 (Store Selection) 
   - Complete Step 2 (Transfer Information)
   - In Step 3, click "Select Products from Warehouse"
   - Products table should now show available items

3. **Verify FIFO behavior:**
   - Each product should appear only once
   - Only products with `available_quantity > 0` should show
   - Batch information should show the oldest entry date per product

## Sample Expected Output

| Product Name | Available Qty | Batch Reference | Entry Date | 
|-------------|---------------|-----------------|------------|
| Paracetamol 500mg | 50 | BATCH001 | 2024-01-15 |
| Vitamin C | 25 | BATCH045 | 2024-02-01 |
| Aspirin 100mg | 75 | BATCH089 | 2024-01-20 |

## Troubleshooting

### If products still don't show:
1. Check if `tbl_stock_summary` has data with `available_quantity > 0`
2. Verify location_id mapping between frontend and database
3. Check MySQL version compatibility (use backup query for MySQL < 8.0)
4. Review PHP error logs for SQL syntax errors

### If wrong products show (not oldest):
1. Verify `tbl_batch.entry_date` has proper dates
2. Check if `ROW_NUMBER()` window function is supported
3. Use the subquery fallback for older MySQL versions

### MySQL Version Issues:
- **MySQL 8.0+**: Use CTE-based query (current implementation)
- **MySQL 5.7 and below**: Use subquery-based fallback in `fifo_mysql_compatibility.php`

## Files Modified/Created

1. ✅ **`Api/backend_mysqli.php`** - Updated `get_products` endpoint
2. ✅ **`fifo_oldest_batch_query.sql`** - Reference SQL query
3. ✅ **`fifo_mysql_compatibility.php`** - MySQL compatibility helper
4. ✅ **`test_fifo_products_api.php`** - API testing script
5. ✅ **`FIFO_TRANSFER_FIX_SUMMARY.md`** - This documentation

## Next Steps

1. Test the updated API endpoint
2. Verify the transfer interface now shows products
3. Test a complete transfer to ensure FIFO logic works end-to-end
4. Monitor for any performance issues with the new query
5. Consider adding caching if query performance is slow with large datasets

The FIFO transfer system should now properly display only the oldest available batch per product, ensuring proper inventory rotation and preventing users from accidentally transferring newer stock before older stock.
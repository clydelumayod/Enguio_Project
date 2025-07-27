# Product Stock Tracking System

A comprehensive system for tracking when products were added or restocked, including batch information for inventory auditing purposes.

## Overview

This system provides MySQL queries and PHP functions to track:
- **When products were first added** (date_received from tbl_batch)
- **When additional quantity was added** (as restocks or reorders)
- **Which batch each quantity belongs to**
- **Grouped by product and location**

## Files Included

1. **`product_stock_tracking.php`** - Main PHP file with functions and web interface
2. **`product_stock_tracking_queries.sql`** - MySQL queries for direct database access
3. **`test_stock_tracking.php`** - Test script to demonstrate functionality
4. **`PRODUCT_STOCK_TRACKING_README.md`** - This documentation

## Database Tables Used

- **`tbl_product`** - Product information and current stock
- **`tbl_batch`** - Batch information with date_received
- **`tbl_supplier`** - Supplier information
- **`tbl_location`** - Location information
- **`tbl_stock_movements`** - Detailed movement tracking (FIFO)

## Key Features

### 1. Stock History Tracking
- Track when products were added or restocked
- Identify which batch each stock belongs to
- Show supplier and location information
- Distinguish between "New Entry" and "Restocked"

### 2. Movement History
- Comprehensive tracking using tbl_stock_movements
- Track IN, OUT, and ADJUSTMENT movements
- Include unit costs and expiration dates
- Reference numbers and notes for audit trails

### 3. Stock Summary by Location
- Current stock levels with batch information
- Grouped by location for easy management
- Last batch date and supplier information

### 4. Audit Reports
- Complete audit trail for inventory verification
- Batch reference tracking
- Supplier performance analysis
- Location-based inventory value

## Usage

### Method 1: PHP Functions

```php
require_once 'product_stock_tracking.php';

// Get all stock history
$history = getProductStockHistory();

// Get stock history for specific product
$productHistory = getProductStockHistory(1);

// Get stock history for specific location
$locationHistory = getProductStockHistory(null, 2);

// Get stock history for date range
$recentHistory = getProductStockHistory(null, null, '2025-01-01', '2025-12-31');

// Get movement history
$movements = getStockMovementHistory();

// Get stock summary by location
$summary = getProductStockSummary();

// Get products by batch reference
$batchProducts = getProductsByBatch('BR-20250716-232504');

// Get comprehensive audit report
$audit = getStockAuditReport();
```

### Method 2: Direct SQL Queries

Run the queries from `product_stock_tracking_queries.sql` directly in your database client:

```sql
-- Basic stock history
SELECT 
    p.product_name,
    b.batch_reference,
    b.entry_date as date_received,
    p.quantity,
    l.location_name,
    CASE 
        WHEN p.date_added = b.entry_date THEN 'New Entry'
        WHEN p.date_added != b.entry_date THEN 'Restocked'
        ELSE 'Unknown'
    END as action_type
FROM tbl_product p
LEFT JOIN tbl_batch b ON p.batch_id = b.batch_id
LEFT JOIN tbl_location l ON p.location_id = l.location_id
WHERE p.status = 'active'
ORDER BY b.entry_date DESC;
```

### Method 3: Web Interface

Access the web interface by opening `product_stock_tracking.php` in your browser. The interface provides:

- **Stock History** - View all stock additions and restocks
- **Movement History** - View detailed movement records
- **Stock Summary** - View current stock by location
- **Products by Batch** - Search products by batch reference
- **Audit Report** - Generate comprehensive audit reports

### Method 4: API Endpoints

The system provides REST API endpoints:

```
GET product_stock_tracking.php?action=stock_history&location_id=2
GET product_stock_tracking.php?action=movement_history&movement_type=IN
GET product_stock_tracking.php?action=stock_summary
GET product_stock_tracking.php?action=products_by_batch&batch_reference=BR-20250716-232504
GET product_stock_tracking.php?action=audit_report&date_from=2025-01-01&date_to=2025-12-31
```

## Output Fields

### Stock History Output
- `product_id` - Product identifier
- `product_name` - Product name
- `barcode` - Product barcode
- `category` - Product category
- `batch_id` - Batch identifier
- `batch_reference` - Batch reference number
- `date_received` - When the batch was received
- `time_received` - Time the batch was received
- `entry_by` - Who entered the batch
- `order_no` - Purchase order number
- `supplier_name` - Supplier name
- `location_name` - Location name
- `current_quantity` - Current stock quantity
- `unit_price` - Unit price
- `expiration` - Expiration date
- `date_added` - When product was added to system
- `stock_status` - Current stock status
- `action_type` - "New Entry" or "Restocked"
- `audit_summary` - Formatted summary for reporting

### Movement History Output
- `movement_id` - Movement identifier
- `movement_type` - IN, OUT, or ADJUSTMENT
- `quantity` - Quantity moved
- `remaining_quantity` - Remaining quantity after movement
- `unit_cost` - Unit cost at time of movement
- `movement_date` - When movement occurred
- `reference_no` - Reference number
- `notes` - Additional notes
- `created_by` - Who created the movement
- `action_description` - Human-readable description

## Filtering Options

All functions support filtering by:

- **Product ID** - Specific product
- **Location ID** - Specific location
- **Date Range** - Start and end dates
- **Batch Reference** - Specific batch
- **Movement Type** - IN, OUT, ADJUSTMENT

## Examples

### Example 1: Track Recent Stock Additions
```php
// Get stock added in the last 30 days
$recentAdditions = getProductStockHistory(
    null, // all products
    null, // all locations
    date('Y-m-d', strtotime('-30 days')), // from 30 days ago
    date('Y-m-d') // to today
);
```

### Example 2: Warehouse Stock Summary
```php
// Get stock summary for warehouse (location_id = 2)
$warehouseStock = getProductStockSummary(2);
```

### Example 3: Products by Specific Batch
```php
// Find all products in a specific batch
$batchProducts = getProductsByBatch('BR-20250716-232504');
```

### Example 4: Movement History for Specific Product
```php
// Get all movements for product ID 1
$productMovements = getStockMovementHistory(1);
```

### Example 5: Audit Report for Date Range
```php
// Generate audit report for Q1 2025
$q1Audit = getStockAuditReport('2025-01-01', '2025-03-31');
```

## Testing

Run the test script to verify everything works:

```bash
php test_stock_tracking.php
```

This will:
- Test all functions
- Display sample data
- Show API endpoints
- Provide usage examples

## Customization

### Adding New Filters
To add new filters, modify the SQL queries in the functions:

```php
// Add brand filter
if ($brand_id) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $brand_id;
}
```

### Adding New Output Fields
To add new fields to the output, modify the SELECT statements:

```sql
-- Add brand information
b.brand_name,
b.brand_category,
```

### Custom Reports
Create custom reports by combining the existing functions:

```php
function getCustomReport($location_id, $date_from, $date_to) {
    $stock = getProductStockSummary($location_id);
    $movements = getStockMovementHistory(null, $location_id, $date_from, $date_to);
    
    // Combine and format data as needed
    return [
        'stock_summary' => $stock,
        'movements' => $movements,
        'total_value' => array_sum(array_column($stock, 'current_stock'))
    ];
}
```

## Troubleshooting

### Common Issues

1. **No data returned**
   - Check if products have batch_id values
   - Verify tbl_stock_movements has data
   - Ensure products have status = 'active'

2. **Database connection errors**
   - Verify database credentials in `Api/conn.php`
   - Check if all required tables exist

3. **Performance issues**
   - Add indexes on frequently queried columns
   - Use date range filters to limit data
   - Consider pagination for large datasets

### Debug Mode

Enable debug mode by adding this to your PHP code:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations

- Validate all input parameters
- Use prepared statements (already implemented)
- Limit access to sensitive audit data
- Log all audit report access
- Consider data retention policies

## Performance Optimization

- Add indexes on frequently queried columns
- Use date range filters to limit data
- Consider caching for frequently accessed reports
- Implement pagination for large datasets
- Use database views for complex queries

## Support

For issues or questions:
1. Check the test script output
2. Verify database structure matches requirements
3. Review error logs
4. Test with sample data first

## License

This system is provided as-is for educational and business use. Modify as needed for your specific requirements. 
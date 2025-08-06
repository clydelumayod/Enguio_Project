# FIFO Transfer System Implementation Guide

## Overview
This system automatically handles First-In-First-Out (FIFO) inventory consumption when products are transferred between locations. When a transfer is approved in `tbl_transfer_header`, the system automatically subtracts quantities from the oldest batches first.

## Key Components

### 1. Main Stored Procedure: `ProcessFIFOTransfer`
This procedure handles the core FIFO logic:
- Takes a product ID, transfer quantity, source location, and reference
- Finds the oldest batches first using entry_date
- Subtracts quantities from batches in FIFO order
- Updates all related tables (`tbl_fifo_stock`, `tbl_stock_summary`, `tbl_product`)
- Records stock movements for audit trail

### 2. Automatic Trigger: `tr_transfer_approved`
- Triggers when a transfer status changes to 'approved'
- Automatically calls `ProcessFIFOTransfer` for each product in the transfer
- Creates destination product entries if they don't exist
- Updates destination quantities
- Logs the transfer in `tbl_transfer_log`

### 3. Helper Views and Functions
- `v_fifo_inventory_status`: Shows current FIFO inventory status
- `GetNextFIFOBatches()`: Preview which batches will be consumed

## Implementation Steps

### Step 1: Execute the FIFO System SQL
```sql
-- Run the fifo_transfer_system_enhanced.sql file
SOURCE fifo_transfer_system_enhanced.sql;
```

### Step 2: Verify Your Current Data Structure
Your database already has the necessary tables:
- ✅ `tbl_fifo_stock` - tracks FIFO stock per batch
- ✅ `tbl_stock_summary` - summary of stock quantities
- ✅ `tbl_transfer_header` / `tbl_transfer_dtl` - transfer operations
- ✅ `tbl_stock_movements` - audit trail

### Step 3: Test the System

#### Example Test Scenario:
```sql
-- 1. Check current stock for Nova (product_id 215)
SELECT * FROM v_fifo_inventory_status 
WHERE product_id = 215;

-- 2. Preview what batches will be consumed for a 30-unit transfer
SELECT GetNextFIFOBatches(215, 2, 30) as preview;

-- 3. Create a transfer request
INSERT INTO tbl_transfer_header (date, source_location_id, destination_location_id, employee_id, status)
VALUES ('2025-08-04', 2, 4, 20, 'pending');

SET @transfer_id = LAST_INSERT_ID();

INSERT INTO tbl_transfer_dtl (transfer_header_id, product_id, qty)
VALUES (@transfer_id, 215, 30);

-- 4. Approve the transfer (this triggers FIFO consumption)
UPDATE tbl_transfer_header 
SET status = 'approved' 
WHERE transfer_header_id = @transfer_id;

-- 5. Verify the results
SELECT * FROM tbl_fifo_stock WHERE product_id = 215;
SELECT * FROM tbl_stock_movements WHERE product_id = 215 ORDER BY movement_date DESC LIMIT 5;
```

## How FIFO Logic Works

### Before Transfer:
```
Product ID 215 (Nova) - Warehouse Location
Batch 60 (2025-08-02): 50 units available
Batch 61 (2025-08-02): 20 units available  
Batch 62 (2025-08-03): 20 units available
Total: 90 units
```

### Transfer Request: 30 units
The system will consume:
1. **Batch 60**: 30 units (oldest batch, has enough stock)
2. Remaining in Batch 60: 20 units
3. Other batches remain unchanged

### Transfer Request: 75 units
The system will consume:
1. **Batch 60**: 50 units (consume all from oldest)
2. **Batch 61**: 20 units (consume all from next oldest)
3. **Batch 62**: 5 units (consume remaining from next batch)
4. Remaining in Batch 62: 15 units

## Key Features

### ✅ Automatic FIFO Consumption
- Always uses oldest batches first based on `entry_date`
- Handles partial batch consumption
- Cascades across multiple batches when needed

### ✅ Data Integrity
- Transaction-based operations (rollback on errors)
- Validates sufficient stock before processing
- Updates all related tables consistently

### ✅ Audit Trail
- Records all stock movements in `tbl_stock_movements`
- Logs transfers in `tbl_transfer_log`
- Maintains batch traceability

### ✅ Stock Status Updates
- Automatically updates `stock_status` (in stock/low stock/out of stock)
- Updates both source and destination locations
- Creates destination product entries if needed

## Error Handling

The system includes comprehensive error handling:

```sql
-- Insufficient stock error
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for transfer';
```

## Monitoring Queries

### Check FIFO Order for a Product:
```sql
SELECT 
    p.product_name,
    fs.batch_reference,
    b.entry_date,
    fs.available_quantity,
    ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY b.entry_date, fs.created_at) as fifo_order
FROM tbl_product p
INNER JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
INNER JOIN tbl_batch b ON fs.batch_id = b.batch_id
WHERE p.product_id = 215 AND fs.available_quantity > 0
ORDER BY b.entry_date, fs.created_at;
```

### Check Recent Stock Movements:
```sql
SELECT 
    sm.*,
    p.product_name,
    b.batch_reference
FROM tbl_stock_movements sm
INNER JOIN tbl_product p ON sm.product_id = p.product_id
INNER JOIN tbl_batch b ON sm.batch_id = b.batch_id
ORDER BY sm.movement_date DESC
LIMIT 10;
```

### Inventory Status Summary:
```sql
SELECT * FROM v_fifo_inventory_status 
ORDER BY total_available DESC;
```

## Manual FIFO Processing (if needed)

You can also manually trigger FIFO consumption:

```sql
CALL ProcessFIFOTransfer(
    215,     -- product_id
    25,      -- quantity to consume
    2,       -- source_location_id
    999,     -- transfer_header_id (reference)
    'MANUAL-ADJ-001'  -- reference number
);
```

## Troubleshooting

### Common Issues:

1. **Transfer not processing**: Check if status is exactly 'approved'
2. **Insufficient stock**: Verify available quantities in `tbl_fifo_stock`
3. **Missing batches**: Ensure products have entries in `tbl_fifo_stock`

### Debug Queries:
```sql
-- Check trigger status
SHOW TRIGGERS LIKE 'tbl_transfer_header';

-- Check procedure exists
SHOW PROCEDURE STATUS WHERE Name = 'ProcessFIFOTransfer';

-- Check current stock
SELECT 
    p.product_name,
    l.location_name,
    SUM(fs.available_quantity) as total_stock
FROM tbl_product p
INNER JOIN tbl_location l ON p.location_id = l.location_id
LEFT JOIN tbl_fifo_stock fs ON p.product_id = fs.product_id
GROUP BY p.product_id, p.product_name, l.location_name
HAVING total_stock > 0;
```

## Benefits

1. **Automated**: No manual intervention needed once set up
2. **Accurate**: Always follows FIFO principle
3. **Traceable**: Complete audit trail of all movements
4. **Consistent**: Updates all related tables automatically
5. **Safe**: Transaction-based with error handling
6. **Flexible**: Works with existing transfer workflow

This system ensures your inventory management follows proper FIFO principles while maintaining data integrity and providing complete traceability.
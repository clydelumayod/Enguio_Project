# FIFO Transfer System Implementation

## Overview

The FIFO (First In, First Out) transfer system ensures that inventory transfers follow the principle of transferring the oldest batches first. This is crucial for maintaining product freshness and proper inventory rotation, especially for products with expiration dates.

## How It Works

### 1. FIFO Logic
- When a transfer is initiated, the system automatically identifies the oldest batch for each product
- Transfers are made from the oldest batch first (earliest entry date)
- Only when the oldest batch is depleted does the system move to the next oldest batch
- This ensures proper inventory rotation and minimizes waste

### 2. Database Structure
The system uses the following tables to implement FIFO:

- `tbl_stock_summary`: Tracks available quantities by batch
- `tbl_batch`: Contains batch information including entry dates
- `tbl_transfer_header`: Transfer transaction headers
- `tbl_transfer_dtl`: Transfer details
- `tbl_transfer_log`: Audit trail of all transfers

### 3. FIFO View
The system includes a `v_fifo_stock` view that orders products by:
1. Product ID
2. Batch entry date (oldest first)
3. Summary ID (for tie-breaking)

## API Endpoints

### 1. Create FIFO Transfer
**Endpoint:** `POST /Api/backend_mysqli.php`
**Action:** `create_fifo_transfer`

**Request Body:**
```json
{
  "action": "create_fifo_transfer",
  "source_location_id": 2,
  "destination_location_id": 4,
  "employee_id": 21,
  "status": "approved",
  "products": [
    {
      "product_id": 183,
      "quantity": 10
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "FIFO transfer completed successfully",
  "transfer_id": 44,
  "products_transferred": 1,
  "source_location": "warehouse",
  "destination_location": "Convenience"
}
```

### 2. Get FIFO Stock Information
**Endpoint:** `POST /Api/backend_mysqli.php`
**Action:** `get_fifo_stock`

**Request Body:**
```json
{
  "action": "get_fifo_stock",
  "location_id": 2,
  "category": "All Product Category",
  "supplier": "All Suppliers",
  "search": ""
}
```

## Frontend Implementation

### 1. UI Indicators
- FIFO system status badge in the header
- Information card explaining FIFO principles
- Success messages indicating FIFO transfer completion

### 2. Transfer Process
1. User selects source and destination locations
2. User selects products and quantities
3. System automatically uses FIFO logic during transfer
4. Oldest batches are transferred first
5. User receives confirmation with FIFO transfer details

## Example Scenario

### Before Transfer
```
Product: C2 Apple
- Batch 1 (Entry Date: 2025-07-19): 80 units available
- Batch 2 (Entry Date: 2025-07-21): 30 units available
```

### Transfer Request
```
Transfer 50 units from Warehouse to Convenience Store
```

### After FIFO Transfer
```
Product: C2 Apple
- Batch 1 (Entry Date: 2025-07-19): 30 units available (50 transferred)
- Batch 2 (Entry Date: 2025-07-21): 30 units available (unchanged)
```

## Benefits

1. **Product Freshness**: Ensures older products are used first
2. **Waste Reduction**: Minimizes expired inventory
3. **Compliance**: Meets regulatory requirements for inventory rotation
4. **Cost Efficiency**: Reduces losses from expired products
5. **Audit Trail**: Complete tracking of batch movements

## Testing

Use the test script `test_fifo_transfer.php` to verify the FIFO system:

```bash
# Run the test script
php test_fifo_transfer.php
```

The test script will:
1. Show current stock summary with FIFO order
2. Perform a test FIFO transfer
3. Display updated stock summary
4. Show transfer logs

## Error Handling

The system includes comprehensive error handling:

1. **Insufficient Stock**: Validates available quantities before transfer
2. **Transaction Rollback**: Ensures data consistency on errors
3. **Detailed Error Messages**: Provides specific error information
4. **Logging**: Maintains complete audit trail

## Database Views

### v_fifo_stock
```sql
CREATE VIEW v_fifo_stock AS
SELECT 
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    ss.batch_id,
    ss.batch_reference,
    ss.available_quantity,
    ss.unit_cost,
    ss.expiration_date,
    b.entry_date as batch_date,
    ROW_NUMBER() OVER (
        PARTITION BY p.product_id 
        ORDER BY b.entry_date ASC, ss.summary_id ASC
    ) AS fifo_order
FROM tbl_product p
JOIN tbl_stock_summary ss ON p.product_id = ss.product_id
JOIN tbl_batch b ON ss.batch_id = b.batch_id
WHERE p.status = 'active' AND ss.available_quantity > 0
ORDER BY p.product_id ASC, fifo_order ASC;
```

## Configuration

The FIFO system is automatically enabled for all transfers. No additional configuration is required.

## Monitoring

Monitor FIFO transfers through:
1. Transfer logs in `tbl_transfer_log`
2. Stock summary updates in `tbl_stock_summary`
3. Frontend transfer history
4. API response messages

## Future Enhancements

Potential improvements:
1. FIFO transfer reports
2. Batch expiration alerts
3. Automated FIFO suggestions
4. Batch cost tracking
5. Transfer optimization algorithms 
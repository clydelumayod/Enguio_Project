# FIFO Transfer System Documentation

## Overview
The FIFO (First In, First Out) Transfer System automatically manages product transfers between locations by deducting quantities from the oldest batches first. This ensures proper inventory rotation and prevents products from expiring.

## Files Created
- `fifo_transfer_system.php` - Core FIFO transfer functions
- `Api/fifo_transfer_api.php` - API endpoints for frontend integration
- `test_fifo_transfer.php` - Test file with usage examples

## Core Function: `performFifoTransfer()`

### Parameters
```php
performFifoTransfer($product_barcode, $source_location_id, $destination_location_id, $requested_quantity, $employee_id)
```

- `$product_barcode` (int) - Product barcode to transfer
- `$source_location_id` (int) - Source location ID
- `$destination_location_id` (int) - Destination location ID
- `$requested_quantity` (int) - Quantity to transfer
- `$employee_id` (int) - Employee performing the transfer

### How It Works
1. **Validates** input parameters and checks location availability
2. **Retrieves** all available product batches from source location ordered by `entry_date` ASC (oldest first)
3. **Verifies** sufficient stock is available
4. **Creates** transfer header record in `tbl_transfer_header`
5. **Processes** batches in FIFO order:
   - Deducts quantities from oldest batches first
   - Creates transfer detail records in `tbl_transfer_dtl`
   - Updates source product quantities
   - Creates/updates destination product records
6. **Updates** stock status based on remaining quantities
7. **Returns** detailed transfer results

### Example Usage
```php
<?php
require_once 'fifo_transfer_system.php';

// Transfer 50 units of C2 Apple from warehouse to convenience store
$result = performFifoTransfer(
    1000000000015,  // C2 Apple barcode
    2,              // From warehouse (location_id: 2)
    4,              // To convenience store (location_id: 4)
    50,             // Transfer 50 units
    21              // Employee ID 21
);

if ($result['success']) {
    echo "Transfer completed successfully!";
    echo "Transfer ID: " . $result['transfer_header_id'];
    
    // Show which batches were used
    foreach ($result['details']['transfer_breakdown'] as $batch) {
        echo "Used {$batch['quantity_transferred']} from batch {$batch['batch_reference']}";
    }
} else {
    echo "Transfer failed: " . $result['message'];
}
?>
```

## API Endpoints

### Base URL
```
/Api/fifo_transfer_api.php
```

### 1. Perform Transfer
**Endpoint:** `POST /Api/fifo_transfer_api.php?action=transfer`

**Request Body:**
```json
{
    "product_barcode": 1000000000015,
    "source_location_id": 2,
    "destination_location_id": 4,
    "requested_quantity": 50,
    "employee_id": 21
}
```

**Response:**
```json
{
    "success": true,
    "message": "FIFO transfer completed successfully",
    "transfer_header_id": 44,
    "details": {
        "product_barcode": 1000000000015,
        "source_location": "warehouse",
        "destination_location": "Convenience",
        "total_quantity_transferred": 50,
        "batches_used": 2,
        "transfer_breakdown": [
            {
                "batch_id": 37,
                "batch_reference": "BR-20250719-221948",
                "entry_date": "2025-07-19",
                "quantity_transferred": 30,
                "remaining_in_batch": 50
            },
            {
                "batch_id": 38,
                "batch_reference": "BR-20250720-163405",
                "entry_date": "2025-07-20",
                "quantity_transferred": 20,
                "remaining_in_batch": 0
            }
        ]
    }
}
```

### 2. Check Stock
**Endpoint:** `GET /Api/fifo_transfer_api.php?action=check_stock&product_barcode=1000000000015&location_id=2`

**Response:**
```json
{
    "success": true,
    "total_available": 80,
    "batches": [
        {
            "product_id": 183,
            "product_name": "C2 Apple",
            "quantity": 50,
            "unit_price": "18.00",
            "expiration": "2026-07-21",
            "stock_status": "in stock",
            "batch_reference": "BR-20250719-221948",
            "entry_date": "2025-07-19",
            "location_name": "warehouse"
        }
    ]
}
```

### 3. Transfer History
**Endpoint:** `GET /Api/fifo_transfer_api.php?action=transfer_history&limit=10&offset=0`

**Optional Parameters:**
- `product_barcode` - Filter by specific product
- `location_id` - Filter by location (source or destination)
- `limit` - Number of records (default: 20)
- `offset` - Starting point (default: 0)

### 4. Available Products
**Endpoint:** `GET /Api/fifo_transfer_api.php?action=available_products&location_id=2&search=apple`

**Parameters:**
- `location_id` (required) - Location to check
- `search` (optional) - Search term for product name or barcode

## Frontend Integration

### JavaScript/React Example
```javascript
// Transfer function
async function performTransfer(transferData) {
    try {
        const response = await fetch('/Api/fifo_transfer_api.php?action=transfer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_barcode: transferData.barcode,
                source_location_id: transferData.sourceLocation,
                destination_location_id: transferData.destinationLocation,
                requested_quantity: transferData.quantity,
                employee_id: transferData.employeeId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Transfer completed:', result);
            // Update UI, show success message
            showTransferSuccess(result);
        } else {
            console.error('Transfer failed:', result.message);
            // Show error message
            showError(result.message);
        }
    } catch (error) {
        console.error('Network error:', error);
        showError('Network error occurred');
    }
}

// Check stock before transfer
async function checkStock(barcode, locationId) {
    try {
        const response = await fetch(
            `/Api/fifo_transfer_api.php?action=check_stock&product_barcode=${barcode}&location_id=${locationId}`
        );
        const result = await response.json();
        
        if (result.success) {
            return result.total_available;
        }
        return 0;
    } catch (error) {
        console.error('Error checking stock:', error);
        return 0;
    }
}
```

## Database Tables Used

### `tbl_product`
- Stores individual product records with batch information
- Updated quantities during transfers
- Stock status automatically updated based on remaining quantity

### `tbl_batch`
- Contains batch information including `entry_date` for FIFO ordering
- Used to determine transfer priority (oldest first)

### `tbl_transfer_header`
- Records each transfer transaction
- Links to employee and locations
- Contains transfer status

### `tbl_transfer_dtl`
- Details which products and quantities were transferred
- One record per product/batch used in transfer

## Stock Status Logic
- `out of stock` - quantity = 0
- `low stock` - quantity <= 10
- `in stock` - quantity > 10

## Error Handling
The system includes comprehensive error handling for:
- Invalid input parameters
- Insufficient stock
- Database connection issues
- Transaction rollback on failures

## Testing
Run the test file to verify functionality:
```bash
# Access via browser
http://localhost/your-project/test_fifo_transfer.php
```

## Location IDs in Your Database
Based on your `tbl_location` table:
- 2: warehouse
- 3: Pharmacy  
- 4: Convenience

## Best Practices
1. Always check stock availability before attempting transfers
2. Use transactions to ensure data consistency
3. Log all transfer activities for audit trails
4. Validate user permissions before allowing transfers
5. Consider implementing transfer approval workflows for high-value items

## Troubleshooting

### Common Issues
1. **"No available stock found"** - Product doesn't exist in source location or has zero quantity
2. **"Insufficient stock"** - Requested quantity exceeds available stock
3. **"Database connection failed"** - Check database credentials in `Api/conn.php`

### Debug Steps
1. Check product exists: Query `tbl_product` for the specific barcode and location
2. Verify batch data: Ensure `tbl_batch` has corresponding entries
3. Check employee permissions: Verify employee_id exists in `tbl_employee`
4. Review database logs: Check for any constraint violations or SQL errors 
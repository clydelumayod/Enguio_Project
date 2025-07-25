# Automatic Inventory Transfer System

## 🎯 **Overview**

The Automatic Inventory Transfer System allows you to transfer products from the warehouse to specific stores (like convenience stores and pharmacies) with automatic inventory updates. When you create a transfer, products are immediately moved from the source location to the destination location.

## ✅ **Features**

### 1. **Automatic Transfer Processing**
- Products are immediately moved from source to destination
- No manual approval required - transfers are completed instantly
- Real-time inventory updates across all locations

### 2. **Smart Product Handling**
- **Existing Products**: If the same product exists in destination, quantity is updated
- **New Products**: If product doesn't exist in destination, a new entry is created with unique barcode
- **Stock Status Updates**: Automatic stock status updates (in stock, low stock, out of stock)

### 3. **Location-Specific APIs**
- `get_products_by_location_name`: Get products filtered by location name
- `get_location_products`: Get products with transfer history
- Enhanced location filtering for better inventory management

## 🔧 **How It Works**

### Transfer Process Flow:

1. **User Creates Transfer**
   ```
   Warehouse → Convenience Store
   Product: Coca Cola (Qty: 10)
   ```

2. **System Validates**
   - Checks if product exists in warehouse
   - Verifies sufficient quantity available
   - Validates locations and staff

3. **Automatic Processing**
   - Decreases quantity in warehouse
   - Increases quantity in convenience store
   - Updates stock status for both locations
   - Creates transfer record

4. **Immediate Availability**
   - Products appear instantly in destination store
   - Can be viewed in convenience store inventory
   - Available for POS transactions

## 📊 **API Endpoints**

### Create Transfer
```javascript
POST /Api/backend.php
{
  "action": "create_transfer",
  "source_location_id": 1,
  "destination_location_id": 2,
  "employee_id": 1,
  "status": "Completed",
  "products": [
    {
      "product_id": 123,
      "quantity": 5
    }
  ]
}
```

### Get Products by Location
```javascript
POST /Api/backend.php
{
  "action": "get_products_by_location_name",
  "location_name": "Convenience Store"
}
```

### Get Transfer History
```javascript
POST /Api/backend.php
{
  "action": "get_transfers_with_details"
}
```

## 🏪 **Store Integration**

### Convenience Store
- **Location Name**: "Convenience Store"
- **Products**: Automatically populated when transfers are made
- **View**: `app/Inventory_Con/ConvenienceStore.js`

### Pharmacy
- **Location Name**: "Pharmacy"
- **Products**: Automatically populated when transfers are made
- **View**: `app/Inventory_Con/PharmacyInventory.js`

### Warehouse
- **Location Name**: "Warehouse"
- **Products**: Source location for transfers
- **View**: `app/Inventory_Con/Warehouse.js`

## 🧪 **Testing**

### Run Test Script
```bash
php test_automatic_transfer.php
```

This script will:
1. Check location availability
2. Verify warehouse products
3. Test automatic transfer
4. Verify products appear in destination
5. Report success/failure

### Manual Testing Steps

1. **Create Transfer**
   - Go to Inventory Transfer
   - Select Warehouse as source
   - Select Convenience Store as destination
   - Add products and quantities
   - Submit transfer

2. **Verify Transfer**
   - Check warehouse inventory (quantity decreased)
   - Check convenience store inventory (products added)
   - Verify transfer appears in transfer history

3. **Test POS Integration**
   - Go to convenience store POS
   - Verify transferred products are available for sale

## 🔄 **Database Changes**

### Transfer Tables
- `tbl_transfer_header`: Transfer records
- `tbl_transfer_dtl`: Transfer line items
- `tbl_product`: Updated with location-specific entries

### Key Features
- **Unique Barcodes**: Each location gets unique product barcodes
- **Stock Status**: Automatic updates based on quantity
- **Location Tracking**: Products linked to specific locations

## 🚀 **Usage Examples**

### Transfer to Convenience Store
```javascript
// 1. Create transfer
const transferData = {
  source_location_id: warehouseId,
  destination_location_id: convenienceStoreId,
  employee_id: staffId,
  status: "Completed",
  products: [
    { product_id: 1, quantity: 10 },
    { product_id: 2, quantity: 5 }
  ]
};

// 2. Submit transfer
const response = await handleApiCall("create_transfer", transferData);

// 3. Products automatically appear in convenience store
```

### View Convenience Store Inventory
```javascript
// Get convenience store products
const response = await handleApiCall("get_products_by_location_name", {
  location_name: "Convenience Store"
});

// Products include transferred items
console.log(response.data);
```

## ⚠️ **Important Notes**

### Barcode Handling
- Each location gets unique barcodes to avoid conflicts
- Format: `original_barcode_location_id_timestamp`
- Example: `123456_2_1703123456`

### Stock Status Rules
- **In Stock**: Quantity > 10
- **Low Stock**: Quantity ≤ 10
- **Out of Stock**: Quantity = 0

### Transfer Status
- **Completed**: Transfer processed immediately
- **New**: Pending transfer (not used in current system)
- **Cancelled**: Cancelled transfer

## 🐛 **Troubleshooting**

### Common Issues

1. **Products Not Appearing in Destination**
   - Check if transfer was successful
   - Verify location names match exactly
   - Check database for transfer records

2. **Barcode Conflicts**
   - System generates unique barcodes automatically
   - No manual intervention required

3. **Quantity Mismatches**
   - Verify source has sufficient quantity
   - Check transfer validation logs

### Debug Logs
```php
// Check transfer logs
error_log("Transfer completed - Product ID: $product_id, Quantity: $transfer_qty");
```

## 📈 **Performance**

### Optimizations
- **Immediate Processing**: No approval workflow
- **Batch Operations**: Multiple products in single transfer
- **Transaction Safety**: Database transactions ensure consistency

### Monitoring
- Transfer success/failure rates
- Product movement tracking
- Inventory accuracy verification

## 🔮 **Future Enhancements**

### Planned Features
- **Transfer Templates**: Predefined transfer configurations
- **Scheduled Transfers**: Automatic transfers at specific times
- **Transfer Notifications**: Email/SMS alerts for transfers
- **Advanced Reporting**: Transfer analytics and insights

### Integration Opportunities
- **POS System**: Direct integration with point of sale
- **Barcode Scanners**: Hardware integration for transfers
- **Mobile App**: Transfer management on mobile devices

---

## 📞 **Support**

For issues or questions about the Automatic Transfer System:
1. Check the troubleshooting section above
2. Run the test script to verify functionality
3. Review transfer logs for error details
4. Contact system administrator for database issues

**Last Updated**: December 2024
**Version**: 1.0.0 
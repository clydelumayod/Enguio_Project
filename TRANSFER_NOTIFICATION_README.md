# Transfer Notification System

This document explains the implementation of the transfer notification system for convenience stores and pharmacies in the Enguio Project.

## Overview

The transfer notification system allows warehouse managers to transfer products to specific stores (convenience store or pharmacy), and the destination stores are automatically notified of incoming transfers. Store managers can then accept the transfers, which adds the products to their inventory.

## Features

### 1. Automatic Notifications
- When a transfer is created from warehouse to convenience store or pharmacy, an automatic notification is generated
- Notifications include transfer details, product count, and transfer ID
- Notifications are stored in the database and linked to specific locations

### 2. Notification Management
- Real-time notification count display
- Mark notifications as read/unread
- Accept transfers directly from notifications
- Notification history tracking

### 3. Product Inventory
- Shows products specific to each store location
- Real-time inventory updates when transfers are accepted
- Stock status tracking (in stock, low stock, out of stock)
- Search and filter functionality

## Database Setup

### 1. Create Notifications Table
Run the SQL script in `create_notifications_table.sql`:

```sql
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `transfer_id` int(11) DEFAULT NULL,
  `notification_type` enum('transfer','low_stock','expiry','system') NOT NULL DEFAULT 'transfer',
  `message` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `fk_notification_location` (`location_id`),
  KEY `fk_notification_transfer` (`transfer_id`),
  CONSTRAINT `fk_notification_location` FOREIGN KEY (`location_id`) REFERENCES `tbl_location` (`location_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `tbl_transfer_header` (`transfer_header_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. Location Configuration
Ensure your locations table has entries for:
- Convenience Store (location_name contains "convenience")
- Pharmacy (location_name contains "pharmacy")
- Warehouse (source location)

## API Endpoints

### 1. Create Notification
```javascript
POST /backend.php
{
  "action": "create_notification",
  "location_id": 3,
  "transfer_id": 1,
  "notification_type": "transfer",
  "message": "New transfer received from Warehouse with 5 products. Transfer ID: TR-1",
  "status": "unread"
}
```

### 2. Get Notifications
```javascript
POST /backend.php
{
  "action": "get_notifications",
  "location_id": 3,
  "status": "all" // or "unread"
}
```

### 3. Mark Notification as Read
```javascript
POST /backend.php
{
  "action": "mark_notification_read",
  "notification_id": 1
}
```

### 4. Get Location Products
```javascript
POST /backend.php
{
  "action": "get_location_products",
  "location_id": 3,
  "search": "product name",
  "category": "all"
}
```

## Usage Flow

### 1. Warehouse Manager Creates Transfer
1. Go to Inventory Transfer page
2. Select source (warehouse) and destination (convenience store/pharmacy)
3. Select products and quantities
4. Submit transfer
5. System automatically creates notification for destination store

### 2. Store Manager Receives Notification
1. Store manager sees notification bell with unread count
2. Click notification bell to view notifications
3. See transfer details and product information
4. Click "Accept Transfer" to approve the transfer
5. Products are added to store inventory

### 3. Inventory Updates
1. When transfer is accepted, products are added to destination location
2. Store inventory is updated in real-time
3. Stock status is automatically calculated
4. Products appear in store's product table

## Components Updated

### 1. Backend (backend.php)
- Added notification creation in transfer process
- Added notification management endpoints
- Added location-specific product retrieval

### 2. Convenience Store (ConvenienceStore.js)
- Complete rewrite with notification system
- Real-time product inventory display
- Transfer acceptance functionality
- Statistics dashboard

### 3. Pharmacy Inventory (PharmacyInventory.js)
- Updated with notification system
- Real-time product inventory display
- Transfer acceptance functionality
- Statistics dashboard

## Features by Store Type

### Convenience Store
- Notification bell with unread count
- Transfer acceptance workflow
- Product inventory management
- Stock status tracking
- Search and filter functionality

### Pharmacy
- Same features as convenience store
- Pharmaceutical product management
- Expiry date tracking (future enhancement)
- Prescription product handling (future enhancement)

## Configuration

### Location Names
The system automatically detects store types based on location names:
- Contains "convenience" → Convenience Store
- Contains "pharmacy" → Pharmacy
- Other locations → Regular warehouse

### Notification Types
- `transfer`: Product transfer notifications
- `low_stock`: Low stock alerts (future)
- `expiry`: Expiry date alerts (future)
- `system`: System notifications (future)

## Future Enhancements

1. **Email Notifications**: Send email alerts to store managers
2. **SMS Notifications**: Send SMS alerts for urgent transfers
3. **Push Notifications**: Real-time browser notifications
4. **Transfer Rejection**: Allow stores to reject transfers with reasons
5. **Transfer Scheduling**: Schedule transfers for specific dates
6. **Bulk Transfer**: Transfer multiple products at once
7. **Transfer History**: Detailed transfer history and reports
8. **Auto-accept Rules**: Set up automatic acceptance rules

## Troubleshooting

### Common Issues

1. **No notifications appearing**
   - Check if location names contain "convenience" or "pharmacy"
   - Verify notifications table exists
   - Check browser console for API errors

2. **Products not appearing after transfer**
   - Verify transfer status is "Completed"
   - Check if products exist in destination location
   - Refresh the page to reload inventory

3. **API errors**
   - Check PHP error logs
   - Verify database connections
   - Ensure all required tables exist

### Debug Steps

1. Check browser console for JavaScript errors
2. Check PHP error logs in `php_errors.log`
3. Verify database table structure
4. Test API endpoints individually
5. Check location IDs and names

## Security Considerations

1. **Authentication**: Add user authentication for store managers
2. **Authorization**: Implement role-based access control
3. **Input Validation**: Validate all API inputs
4. **SQL Injection**: Use prepared statements (already implemented)
5. **XSS Protection**: Sanitize user inputs
6. **CSRF Protection**: Add CSRF tokens for forms

## Performance Optimization

1. **Database Indexing**: Add indexes on frequently queried columns
2. **Caching**: Implement Redis caching for notifications
3. **Pagination**: Add pagination for large notification lists
4. **Real-time Updates**: Implement WebSocket for real-time notifications
5. **Image Optimization**: Optimize product images for faster loading

## Testing

### Manual Testing Steps

1. Create a transfer from warehouse to convenience store
2. Verify notification appears in convenience store
3. Accept the transfer
4. Verify products appear in convenience store inventory
5. Test with pharmacy location
6. Test notification marking as read
7. Test search and filter functionality

### Automated Testing (Future)

1. Unit tests for API endpoints
2. Integration tests for transfer workflow
3. UI tests for notification interactions
4. Performance tests for large datasets

## Support

For issues or questions about the transfer notification system:
1. Check this README for troubleshooting steps
2. Review browser console and PHP error logs
3. Verify database configuration
4. Test API endpoints individually
5. Contact development team for complex issues 
# Brand Management - Clear and Reset

This document explains how to clear the `tbl_brand` table and ensure it receives new values when you input brands.

## Files Created/Modified

1. **`clear_brands.sql`** - SQL script to clear the brand table
2. **`clear_brands.php`** - PHP script to clear the brand table
3. **`test_brands.html`** - Web interface to test brand functionality
4. **`backend.php`** - Modified to include brand clearing functionality
5. **`BRAND_MANAGEMENT_README.md`** - This documentation

## How to Clear the Brand Table

### Option 1: Using the SQL Script
1. Open your MySQL client (phpMyAdmin, MySQL Workbench, etc.)
2. Connect to your `enguio` database
3. Run the SQL commands from `clear_brands.sql`:
   ```sql
   DELETE FROM tbl_brand;
   ALTER TABLE tbl_brand AUTO_INCREMENT = 1;
   ```

### Option 2: Using the PHP Script
1. Make sure your XAMPP/WAMP server is running
2. Navigate to `http://localhost/Enguio_Project/clear_brands.php`
3. The script will automatically clear the table and reset the counter

### Option 3: Using the Web Interface
1. Open `http://localhost/Enguio_Project/test_brands.html`
2. Click the "Clear All Brands" button
3. This will use the API to clear all brands

### Option 4: Using the API Directly
You can make a POST request to your backend with:
```json
{
  "action": "clearBrands"
}
```

## How Brand Addition Works

When you add a brand through the input field:

1. **Frontend sends request** to `backend.php` with action `addBrand`
2. **Backend validates** the brand name (checks for duplicates)
3. **Database inserts** the new brand and returns the new `brand_id`
4. **Frontend receives** confirmation with the new brand ID

## Testing the Functionality

1. **Clear the table first** using any of the methods above
2. **Add a new brand** through your application's brand input field
3. **Verify the brand** appears in your brand list with ID starting from 1

## API Endpoints

### Clear All Brands
- **Action**: `clearBrands`
- **Method**: POST
- **Response**: 
  ```json
  {
    "success": true,
    "message": "All brands cleared successfully"
  }
  ```

### Add Brand
- **Action**: `addBrand`
- **Method**: POST
- **Data**: `{ "brand": "Brand Name" }`
- **Response**:
  ```json
  {
    "success": true,
    "message": "Brand added successfully",
    "brand_id": 1
  }
  ```

### Display Brands
- **Action**: `displayBrand`
- **Method**: POST
- **Response**:
  ```json
  {
    "success": true,
    "brand": [
      { "brand_id": 1, "brand": "Brand Name" }
    ]
  }
  ```

## Troubleshooting

### If brands don't clear:
1. Check database permissions
2. Verify the table name is correct (`tbl_brand`)
3. Ensure your database connection is working

### If new brands don't get ID 1:
1. The auto-increment might not have reset properly
2. Run the SQL commands manually:
   ```sql
   DELETE FROM tbl_brand;
   ALTER TABLE tbl_brand AUTO_INCREMENT = 1;
   ```

### If the API doesn't work:
1. Check that your backend.php is accessible
2. Verify the database connection settings
3. Check browser console for JavaScript errors

## Database Schema

The `tbl_brand` table structure:
```sql
CREATE TABLE `tbl_brand` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`brand_id`)
);
```

## Notes

- The brand table will start with ID 1 after clearing
- Duplicate brand names are prevented by the backend
- The auto-increment counter is reset to 1 when clearing
- All existing brands are permanently deleted when clearing 
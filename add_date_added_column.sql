-- Add date_added column to tbl_product table if it doesn't exist
ALTER TABLE tbl_product ADD COLUMN IF NOT EXISTS date_added DATE DEFAULT CURRENT_DATE;

-- Update existing records to have a default date if date_added is NULL
UPDATE tbl_product SET date_added = CURRENT_DATE WHERE date_added IS NULL; 
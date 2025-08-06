-- Fix shift_id constraint to allow NULL values for roles that don't require shifts
-- This allows admin and inventory roles to have NULL shift_id

ALTER TABLE `tbl_employee` MODIFY `shift_id` int(11) NULL;

-- Add a comment to document the change
ALTER TABLE `tbl_employee` COMMENT = 'Modified shift_id to allow NULL for admin and inventory roles'; 
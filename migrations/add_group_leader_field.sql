-- Add group leader functionality
-- Migration: Add is_leader field to group_members table

ALTER TABLE `group_members` ADD COLUMN `is_leader` TINYINT(1) DEFAULT 0 AFTER `user_id`;

-- Add index for better performance
ALTER TABLE `group_members` ADD INDEX `idx_group_leader` (`group_id`, `is_leader`);

-- Add unique constraint to ensure only one leader per group
-- Note: This constraint will be enforced at application level since MySQL doesn't support partial unique indexes easily
-- The application logic ensures only one leader per group

-- Update existing data: Set the first member of each group as leader (optional)
-- This is commented out as it might not be desired for existing groups
-- UPDATE group_members gm1 
-- SET is_leader = 1 
-- WHERE gm1.id = (
--     SELECT MIN(gm2.id) 
--     FROM group_members gm2 
--     WHERE gm2.group_id = gm1.group_id
-- );

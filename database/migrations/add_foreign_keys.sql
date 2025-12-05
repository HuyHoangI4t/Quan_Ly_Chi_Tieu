-- Migration: Add Foreign Key Constraints
-- Purpose: Enforce referential integrity at database level
-- Date: 2025-12-05

USE quan_ly_chi_tieu;

-- ==========================
-- 1. DROP EXISTING FKS (if any)
-- ==========================
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

-- Check and drop existing foreign keys
SELECT CONCAT('ALTER TABLE ', TABLE_NAME, ' DROP FOREIGN KEY ', CONSTRAINT_NAME, ';')
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'quan_ly_chi_tieu' 
  AND REFERENCED_TABLE_NAME IS NOT NULL;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

-- ==========================
-- 2. ADD FOREIGN KEY CONSTRAINTS
-- ==========================

-- Categories table: user_id references users(id)
-- ON DELETE CASCADE: When user is deleted, their custom categories are also deleted
-- Default categories have user_id = NULL, so not affected
ALTER TABLE categories
ADD CONSTRAINT fk_categories_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Transactions table: user_id references users(id)
-- ON DELETE CASCADE: When user is deleted, their transactions are also deleted
ALTER TABLE transactions
ADD CONSTRAINT fk_transactions_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Transactions table: category_id references categories(id)
-- ON DELETE RESTRICT: Cannot delete category if it has transactions
-- This prevents accidental data loss
ALTER TABLE transactions
ADD CONSTRAINT fk_transactions_category_id 
FOREIGN KEY (category_id) REFERENCES categories(id) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Goals table: user_id references users(id)
-- ON DELETE CASCADE: When user is deleted, their goals are also deleted
ALTER TABLE goals
ADD CONSTRAINT fk_goals_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Goal_transactions table: goal_id references goals(id)
-- ON DELETE CASCADE: When goal is deleted, its transactions are also deleted
ALTER TABLE goal_transactions
ADD CONSTRAINT fk_goal_transactions_goal_id 
FOREIGN KEY (goal_id) REFERENCES goals(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Goal_transactions table: transaction_id references transactions(id)
-- ON DELETE CASCADE: When transaction is deleted, remove from goal tracking
ALTER TABLE goal_transactions
ADD CONSTRAINT fk_goal_transactions_transaction_id 
FOREIGN KEY (transaction_id) REFERENCES transactions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Jar_templates table: user_id references users(id)
-- ON DELETE CASCADE: When user is deleted, their jar templates are also deleted
ALTER TABLE jar_templates
ADD CONSTRAINT fk_jar_templates_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Jar_categories table: jar_id references jar_templates(id)
-- ON DELETE CASCADE: When jar template is deleted, its categories are also deleted
ALTER TABLE jar_categories
ADD CONSTRAINT fk_jar_categories_jar_id 
FOREIGN KEY (jar_id) REFERENCES jar_templates(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ==========================
-- 3. ADD UNIQUE CONSTRAINT FOR SUPER ADMIN
-- ==========================
-- Prevent ID=1 issues by using a flag column instead
-- Add is_super_admin column if not exists
ALTER TABLE users
ADD COLUMN IF NOT EXISTS is_super_admin TINYINT(1) DEFAULT 0 COMMENT 'Super admin cannot be demoted or deleted';

-- Mark first admin as super admin
UPDATE users SET is_super_admin = 1 WHERE id = 1 AND role = 'admin';

-- ==========================
-- 4. ADD INDEXES FOR PERFORMANCE
-- ==========================
-- These indexes improve JOIN performance with foreign keys

-- Index on category_id for faster transaction lookups
CREATE INDEX IF NOT EXISTS idx_transactions_category_id ON transactions(category_id);

-- Index on user_id for faster user-specific queries
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_categories_user_id ON categories(user_id);
CREATE INDEX IF NOT EXISTS idx_goals_user_id ON goals(user_id);
CREATE INDEX IF NOT EXISTS idx_jar_templates_user_id ON jar_templates(user_id);

-- Composite index for faster date-range queries
CREATE INDEX IF NOT EXISTS idx_transactions_user_date ON transactions(user_id, transaction_date);

-- ==========================
-- VERIFICATION QUERIES
-- ==========================
-- Uncomment to verify constraints were added:

-- SELECT 
--     TABLE_NAME,
--     CONSTRAINT_NAME,
--     REFERENCED_TABLE_NAME,
--     DELETE_RULE,
--     UPDATE_RULE
-- FROM information_schema.REFERENTIAL_CONSTRAINTS
-- WHERE CONSTRAINT_SCHEMA = 'quan_ly_chi_tieu'
-- ORDER BY TABLE_NAME;

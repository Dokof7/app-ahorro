-- =============================================================================
-- Migration: Create activities table (fundraising activities per group)
-- Date: 2026-07-08
-- Project: app-ahorro (Fundación Jabes)
--
-- HOW TO RUN (HostGator production):
--   1. Open cPanel → phpMyAdmin → select the production database.
--   2. Click the "SQL" tab and paste the contents of this file.
--   3. Click "Go" to execute.
--   4. Verify: run "SELECT COUNT(*) FROM activities;" and confirm the table
--      was created with 0 rows.
--
-- SAFE to run: additive only — creates a new table, no existing tables are
-- modified.
-- =============================================================================

-- Step 1: Create the activities table.
CREATE TABLE IF NOT EXISTS `activities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `activity_date` DATE NOT NULL,
    `location` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `amount_raised` DECIMAL(10,2) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `activities_group_id_foreign` (`group_id`),
    CONSTRAINT `activities_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification query (run after to confirm):
-- SELECT COUNT(*) AS total FROM activities;

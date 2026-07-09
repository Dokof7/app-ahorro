-- =============================================================================
-- Migration: Add partial registration mode for groups (meeting totals only)
-- Date: 2026-07-08
-- Project: app-ahorro (Fundación Jabes)
--
-- HOW TO RUN (HostGator production):
--   1. Open cPanel → phpMyAdmin → select the production database.
--   2. Click the "SQL" tab and paste the contents of this file.
--   3. Click "Go" to execute.
--   4. Verify: run the verification queries at the bottom and confirm the
--      results look reasonable before deploying the new code.
--
-- SAFE to run: additive only — no existing columns are dropped or modified.
-- =============================================================================

-- Step 1: Add registration_mode to groups (default 'full' preserves current
--         behavior for every existing group).
ALTER TABLE `groups`
    ADD COLUMN `registration_mode` VARCHAR(10) NOT NULL DEFAULT 'full'
    AFTER `membership_fee`;

-- Step 2: Create meeting_totals table — holds meeting-level totals for
--         partial-registration groups (one row per meeting).
CREATE TABLE `meeting_totals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `meeting_id` BIGINT UNSIGNED NOT NULL,
    `shares` INT UNSIGNED NOT NULL DEFAULT 0,
    `savings` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `emergency_fund` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `fine` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `observations` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `meeting_totals_meeting_id_unique` (`meeting_id`),
    CONSTRAINT `meeting_totals_meeting_id_foreign` FOREIGN KEY (`meeting_id`)
        REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification queries (run after to confirm):
-- SELECT registration_mode, COUNT(*) AS total FROM `groups` GROUP BY registration_mode;
-- SHOW CREATE TABLE `meeting_totals`;

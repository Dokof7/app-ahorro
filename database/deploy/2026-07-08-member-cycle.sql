-- =============================================================================
-- Migration: Add cycle column to members table
-- Date: 2026-07-08
-- Project: app-ahorro (Fundación Jabes)
--
-- HOW TO RUN (HostGator production):
--   1. Open cPanel → phpMyAdmin → select the production database.
--   2. Click the "SQL" tab and paste the contents of this file.
--   3. Click "Go" to execute.
--   4. Verify: run "SELECT cycle, COUNT(*) FROM members GROUP BY cycle;"
--      and confirm the counts look reasonable before deploying the new code.
--
-- SAFE to run: additive only — no existing columns are dropped or modified.
-- =============================================================================

-- Step 1: Add the new cycle column (default 1 covers all existing members,
--         who are assumed to be in their first savings cycle).
ALTER TABLE `members`
    ADD COLUMN `cycle` TINYINT UNSIGNED NOT NULL DEFAULT 1
    AFTER `join_date`;

-- Verification query (run after to confirm):
-- SELECT cycle, COUNT(*) AS total FROM members GROUP BY cycle;

-- =============================================================================
-- Migration: Add 4-state attendance status column to attendances table
-- Date: 2026-07-04
-- Project: app-ahorro (Fundación Jabes)
--
-- HOW TO RUN (HostGator production):
--   1. Open cPanel → phpMyAdmin → select the production database.
--   2. Click the "SQL" tab and paste the contents of this file.
--   3. Click "Go" to execute.
--   4. Verify: run "SELECT status, COUNT(*) FROM attendances GROUP BY status;"
--      and confirm the counts look reasonable before deploying the new code.
--
-- SAFE to run: additive only — no existing columns are dropped or modified.
-- =============================================================================

-- Step 1: Add the new status column (default 'absent' covers any new rows
--         created before this backfill runs, which should not happen in practice).
ALTER TABLE `attendances`
    ADD COLUMN `status` VARCHAR(10) NOT NULL DEFAULT 'absent'
    AFTER `member_id`;

-- Step 2: Backfill existing rows from the legacy boolean columns.
--   - attended = 1                          => 'present'
--   - excused_absence = 1 AND attended = 0  => 'excused'
--   - otherwise                             => 'absent'
UPDATE `attendances`
SET `status` = CASE
    WHEN `attended` = 1 THEN 'present'
    WHEN `excused_absence` = 1 AND `attended` = 0 THEN 'excused'
    ELSE 'absent'
END;

-- Verification query (run after to confirm):
-- SELECT status, COUNT(*) AS total FROM attendances GROUP BY status;

-- Migration: structured learner name columns on enrollment_applications
-- Schema: kerrfairtex (only; never touches public or other schemas)
-- Idempotent: safe to run on every container start
-- Reversible: ALTER TABLE ... DROP COLUMN (data loss, development only)
--
-- enroll_api.php inserts and admin_enroll.php reads first_name, middle_name,
-- last_name and name_suffix, but databases created from 004 before this
-- change lack them. ADD COLUMN IF NOT EXISTS is a no-op where they exist.

ALTER TABLE kerrfairtex.enrollment_applications
    ADD COLUMN IF NOT EXISTS first_name TEXT,
    ADD COLUMN IF NOT EXISTS middle_name TEXT,
    ADD COLUMN IF NOT EXISTS last_name TEXT,
    ADD COLUMN IF NOT EXISTS name_suffix TEXT;

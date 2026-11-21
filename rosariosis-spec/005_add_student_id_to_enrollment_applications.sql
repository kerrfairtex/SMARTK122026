-- Migration: Add student_id column to enrollment_applications
-- Schema: kerrfairtex
-- Idempotent: safe to run multiple times
-- Reversible: DROP COLUMN (data loss — only for development)
--
-- Context:
--   admin_enroll.php already references enrollment_applications.student_id
--   (lines 97, 115, 192) but the column does not exist in the live database.
--   This migration links each approved SmartK12 enrollment application to
--   the corresponding RosarioSIS student record created from it.
--
-- Relationship:
--   enrollment_applications.id → student_id → students.student_id
--   students.student_id → student_enrollment.student_id
--
-- Applied after verifying:
--   - Column does not already exist (this migration is idempotent)
--   - students table has student_id as SERIAL PRIMARY KEY (INTEGER type)
--   - No existing column named student_id (checked via information_schema)
--   - Existing approved applications can be matched to students by username
--     (if username was generated from the application's first/last name)

-- 1. Check if column already exists (information_schema guard)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'kerrfairtex'
        AND table_name = 'enrollment_applications'
        AND column_name = 'student_id'
    ) THEN
        -- 2. Add the column as nullable (NULL for all existing rows)
        ALTER TABLE kerrfairtex.enrollment_applications
        ADD COLUMN student_id INTEGER;

        -- 3. Add foreign key reference to students.student_id
        ALTER TABLE kerrfairtex.enrollment_applications
        ADD CONSTRAINT fk_enrollment_applications_students
        FOREIGN KEY (student_id) REFERENCES kerrfairtex.students(student_id);

        RAISE NOTICE 'Migration complete: student_id column added to kerrfairtex.enrollment_applications';
    ELSE
        RAISE NOTICE 'Column student_id already exists on kerrfairtex.enrollment_applications — skipping';
    END IF;
END $$;

-- 4. (Removed) Name-based backfill of student_id.
--    It referenced first_name/last_name columns that 004 did not define, which
--    made this whole file roll back on every boot. It also linked 'approved'
--    applications by name alone, which could block their real enrollment.
--    migrate.php re-runs this file on every container start, so automatic
--    data rewrites do not belong here. Link legacy rows manually if needed.

-- 5. Add index for query performance
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_student_id
ON kerrfairtex.enrollment_applications (student_id)
WHERE student_id IS NOT NULL;

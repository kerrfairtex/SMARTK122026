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

-- 4. Backfill: link existing approved/enrolled applications to student records
--    Matching rule: the student's username was generated from the application's
--    first_name + last_name (see admin_enroll.php enroll action). We can match
--    by checking if the student's first_name/last_name match the application's.
--    This is best-effort: only backfills rows where student_id IS NULL and
--    a matching student record can be found.
UPDATE kerrfairtex.enrollment_applications ea
SET student_id = s.student_id
FROM kerrfairtex.students s
WHERE ea.student_id IS NULL
  AND ea.status IN ('approved', 'enrolled')
  AND s.first_name = ea.first_name
  AND s.last_name = ea.last_name
  AND s.username = LOWER(SUBSTRING(ea.first_name, 1, 1) || ea.last_name)
  AND s.created_at > ea.created_at;  -- student was created AFTER the application (causality)

-- 5. Add index for query performance
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_student_id
ON kerrfairtex.enrollment_applications (student_id)
WHERE student_id IS NOT NULL;

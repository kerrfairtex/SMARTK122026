-- Migration 007: Learner Reference Number (LRN) on student records
--
-- Adds an "LRN" text field to RosarioSIS General Info (category 1), the
-- same way School Setup > Student Fields does (custom_fields row +
-- students.custom_<id> column), and fills empty LRN, Gender and Birthdate
-- fields on existing students from the application they were enrolled from.
--
-- Idempotent: safe to run on every container start (migrate.php).
-- Only fills NULL/empty values; never overwrites data entered in RosarioSIS.

DO $$
DECLARE
    lrn_id integer;
BEGIN
    SELECT id INTO lrn_id FROM kerrfairtex.custom_fields
    WHERE title = 'LRN' AND category_id = 1
    ORDER BY id LIMIT 1;

    IF lrn_id IS NULL THEN
        INSERT INTO kerrfairtex.custom_fields (type, title, sort_order, category_id, required)
        VALUES ('text', 'LRN', 0, 1, NULL)
        RETURNING id INTO lrn_id;
        RAISE NOTICE 'Created LRN student field %', lrn_id;
    END IF;

    EXECUTE format('ALTER TABLE kerrfairtex.students ADD COLUMN IF NOT EXISTS %I text', 'custom_' || lrn_id);

    -- LRN from the linked application (digits only, 12 long).
    EXECUTE format(
        'UPDATE kerrfairtex.students s SET %1$I = ea.learner_ref_no
         FROM kerrfairtex.enrollment_applications ea
         WHERE ea.student_id = s.student_id
         AND ea.learner_ref_no ~ ''^[0-9]{12}$''
         AND (s.%1$I IS NULL OR s.%1$I = '''')',
        'custom_' || lrn_id
    );
END $$;

-- Gender (custom_200000000, select Male/Female) and Birthdate
-- (custom_200000004) are RosarioSIS default fields; fill them only if
-- those columns exist in this install.
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'kerrfairtex' AND table_name = 'students'
        AND column_name = 'custom_200000000') THEN
        UPDATE kerrfairtex.students s SET custom_200000000 = ea.sex
        FROM kerrfairtex.enrollment_applications ea
        WHERE ea.student_id = s.student_id
        AND ea.sex IN ('Male', 'Female')
        AND (s.custom_200000000 IS NULL OR s.custom_200000000 = '');
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'kerrfairtex' AND table_name = 'students'
        AND column_name = 'custom_200000004') THEN
        UPDATE kerrfairtex.students s SET custom_200000004 = ea.birth_date
        FROM kerrfairtex.enrollment_applications ea
        WHERE ea.student_id = s.student_id
        AND ea.birth_date IS NOT NULL
        AND s.custom_200000004 IS NULL;
    END IF;
END $$;

-- Speeds up the duplicate-LRN check on new applications.
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_lrn
ON kerrfairtex.enrollment_applications (learner_ref_no)
WHERE learner_ref_no IS NOT NULL;

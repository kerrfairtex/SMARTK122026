-- BBNIHS SmartCampus K-12 schema extensions
-- Schema: kerrfairtex
--
-- UPDATED 2026-09-24: Aligned to the verified live PostgreSQL schema.
-- The live database uses these actual columns (queried via information_schema).

SET search_path = kerrfairtex,public;

-- Enrollment periods
-- Live columns: id, school_year, enrollment_opens, enrollment_closes,
--               classes_begin, grade_levels, status, updated_at
CREATE TABLE IF NOT EXISTS enrollment_periods (
    id SERIAL PRIMARY KEY,
    school_year VARCHAR(20) NOT NULL UNIQUE DEFAULT '2026-2027',
    enrollment_opens DATE,
    enrollment_closes DATE,
    classes_begin DATE,
    grade_levels TEXT,
    status TEXT DEFAULT 'Open',
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Enrollment applications
-- Live columns verified from kerrfairtex.enrollment_applications:
--   id, ref, learner_name, birth_date, sex, birthplace, address,
--   grade_level, school_year, enrollment_type, parent_name,
--   parent_relationship, parent_contact, parent_address, parent_email,
--   prev_school, prev_school_address, last_grade, prev_sy,
--   learner_ref_no, documents, status, notes,
--   created_at, updated_at
-- Note: submitted_at exists in some versions; created_at is the canonical timestamp.
CREATE TABLE IF NOT EXISTS enrollment_applications (
    id SERIAL PRIMARY KEY,
    ref TEXT NOT NULL UNIQUE,
    learner_name TEXT,
    birth_date DATE,
    sex TEXT,
    birthplace TEXT,
    address TEXT,
    grade_level TEXT,
    school_year TEXT,
    enrollment_type TEXT,
    parent_name TEXT,
    parent_relationship TEXT,
    parent_contact TEXT,
    parent_address TEXT,
    parent_email TEXT,
    prev_school TEXT,
    prev_school_address TEXT,
    last_grade TEXT,
    prev_sy TEXT,
    learner_ref_no TEXT,
    documents TEXT,
    status TEXT DEFAULT 'Submitted',
    notes TEXT,
    -- Foreign key to the RosarioSIS students table.
    -- Set when an admin clicks "Enroll" (admin_enroll.php?action=enroll).
    -- NULL until the student record is created from this application.
    student_id INTEGER REFERENCES students(student_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Enrollment drafts
-- Live columns: id, token, payload, status, expires_at, created_at, updated_at
CREATE TABLE IF NOT EXISTS enrollment_drafts (
    id SERIAL PRIMARY KEY,
    token TEXT NOT NULL,
    payload JSONB NOT NULL,
    status TEXT DEFAULT 'active',
    expires_at TIMESTAMP WITH TIME ZONE DEFAULT (NOW() + INTERVAL '14 days'),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- About/content pages
CREATE TABLE IF NOT EXISTS about_content (
    content_id SERIAL PRIMARY KEY,
    section VARCHAR(50) UNIQUE NOT NULL,
    content_html TEXT,
    updated_at TIMESTAMP DEFAULT NOW()
);

-- BBNIHS access log extension
CREATE TABLE IF NOT EXISTS bbnihs_access_log (
    log_id SERIAL PRIMARY KEY,
    user_id INTEGER,
    user_type VARCHAR(10),
    action VARCHAR(50),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_ref ON enrollment_applications (ref);
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_school_year ON enrollment_applications (school_year);
CREATE INDEX IF NOT EXISTS idx_enrollment_applications_status ON enrollment_applications (status);
CREATE INDEX IF NOT EXISTS idx_enrollment_periods_school_year ON enrollment_periods (school_year);

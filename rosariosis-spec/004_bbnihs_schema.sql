-- BBNIHS SmartCampus K-12 schema extensions
-- Schema: kerrfairtex
SET search_path = kerrfairtex,public;

-- Enrollment periods
CREATE TABLE IF NOT EXISTS enrollment_periods (
    enrollment_period_id SERIAL PRIMARY KEY,
    syear INTEGER NOT NULL,
    school_id INTEGER NOT NULL,
    title VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    max_applicants INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Enrollment applications
CREATE TABLE IF NOT EXISTS enrollment_applications (
    application_id SERIAL PRIMARY KEY,
    student_id INTEGER,
    enrollment_period_id INTEGER REFERENCES enrollment_periods(enrollment_period_id),
    status VARCHAR(20) DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT NOW(),
    reviewed_by INTEGER REFERENCES staff(STAFF_ID),
    reviewed_at TIMESTAMP,
    data_json JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Enrollment drafts
CREATE TABLE IF NOT EXISTS enrollment_drafts (
    draft_id SERIAL PRIMARY KEY,
    student_id INTEGER,
    data_json JSONB,
    updated_at TIMESTAMP DEFAULT NOW()
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

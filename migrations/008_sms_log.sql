-- Migration 008: log of SMS notifications to parents/guardians
--
-- One row per attempted message (sent, dry-run when no API key is set, or
-- failed). Phone numbers are stored masked; message text is not stored.
-- Idempotent.

CREATE TABLE IF NOT EXISTS kerrfairtex.sms_log (
    id SERIAL PRIMARY KEY,
    application_id INTEGER REFERENCES kerrfairtex.enrollment_applications(id) ON DELETE SET NULL,
    template TEXT NOT NULL,
    phone_masked TEXT NOT NULL,
    status TEXT NOT NULL,          -- sent | dry_run | failed | skipped
    provider_message_id TEXT,
    detail TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sms_log_application
ON kerrfairtex.sms_log (application_id, created_at DESC);

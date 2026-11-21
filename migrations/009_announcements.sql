-- Migration 009: public announcements for the homepage "News & Events"
--
-- Managed by admins in announcements.php; read by news_api.php.
-- Idempotent. No sample rows: the school adds its own posts.

CREATE TABLE IF NOT EXISTS kerrfairtex.announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'news'
        CHECK (category IN ('news', 'event', 'advisory')),
    event_date DATE,               -- events only
    expires_on DATE,               -- hidden from the homepage after this day
    is_published BOOLEAN NOT NULL DEFAULT TRUE,
    created_by INTEGER,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_announcements_public
ON kerrfairtex.announcements (is_published, created_at DESC);

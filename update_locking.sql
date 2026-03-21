-- Add is_locked and proctor_alerts to assignments table
ALTER TABLE assignments ADD COLUMN is_locked BOOLEAN DEFAULT FALSE;
ALTER TABLE assignments ADD COLUMN proctor_alerts INT DEFAULT 0;

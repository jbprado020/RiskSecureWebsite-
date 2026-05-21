-- Add end_at and duration_minutes to meeting_schedules for overlap detection
ALTER TABLE meeting_schedules
  ADD COLUMN end_at DATETIME NULL AFTER meeting_at,
  ADD COLUMN duration_minutes INT NOT NULL DEFAULT 30 AFTER end_at;

-- Add index to help overlap queries by agent and time range
CREATE INDEX idx_meeting_agent_times ON meeting_schedules (agent_id, meeting_at, end_at);

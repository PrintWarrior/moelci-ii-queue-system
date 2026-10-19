-- Apply once to existing databases after taking a backup.
-- These four attributes are not consumed by the current application.
ALTER TABLE roles DROP COLUMN role_description;
ALTER TABLE services
    DROP COLUMN service_description,
    DROP COLUMN estimated_duration;
ALTER TABLE operating_hours DROP COLUMN is_active;

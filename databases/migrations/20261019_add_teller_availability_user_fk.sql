-- Existing installations: apply once to the database containing both tables.
-- New installations created by ensureTellerAvailabilityTable already include this key.
-- MySQL/MariaDB rejects this change if any availability user_id is orphaned.
ALTER TABLE teller_availability
    ADD CONSTRAINT fk_teller_availability_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;

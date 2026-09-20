-- Connect relationship columns that were added after the original schema.
-- All constraints use stable application IDs and were checked for orphans
-- before this migration was applied.

ALTER TABLE attendance_photo_logs
    ADD CONSTRAINT fk_attendance_photo_attendance
        FOREIGN KEY (AttendanceID) REFERENCES attendance (AttendanceID)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_attendance_photo_worker
        FOREIGN KEY (WorkerID) REFERENCES worker (WorkerID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_attendance_photo_site
        FOREIGN KEY (SiteID) REFERENCES projectsite (SiteID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_attendance_photo_timekeeper_user
        FOREIGN KEY (TimekeeperID) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE timekeeper_assignment
    ADD CONSTRAINT fk_timekeeper_assignment_user
        FOREIGN KEY (UserID) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_timekeeper_assignment_site
        FOREIGN KEY (SiteID) REFERENCES projectsite (SiteID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE projectsite
    ADD CONSTRAINT fk_projectsite_timekeeper_user
        FOREIGN KEY (Timekeeper_UserID) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE payroll_records
    ADD CONSTRAINT fk_payroll_records_submitted_by
        FOREIGN KEY (submitted_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_payroll_records_approved_by
        FOREIGN KEY (approved_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_payroll_records_rejected_by
        FOREIGN KEY (rejected_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE timekeeper_reports
    ADD CONSTRAINT fk_timekeeper_reports_timekeeper_user
        FOREIGN KEY (TimekeeperID) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- Reset operational data while preserving existing Admin account records,
-- system configuration, and reference catalogs/statuses.
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE admin_notifications;
TRUNCATE TABLE approvals;
TRUNCATE TABLE attendance_photo_logs;
TRUNCATE TABLE payrollattendance;
TRUNCATE TABLE payroll_deduction;
TRUNCATE TABLE payslip;
TRUNCATE TABLE payroll_records;
TRUNCATE TABLE payrollbatch;
TRUNCATE TABLE payroll;
TRUNCATE TABLE overtime_requests;
TRUNCATE TABLE attendance;
TRUNCATE TABLE siteassignmenthistory;
TRUNCATE TABLE workerassignment;
TRUNCATE TABLE payrollstaffassignment;
TRUNCATE TABLE timekeeper_assignment;
TRUNCATE TABLE timekeeper_reports;
TRUNCATE TABLE site_schedule;
TRUNCATE TABLE user_site_priorities;
TRUNCATE TABLE brokenequipment;
TRUNCATE TABLE projecthistory;
TRUNCATE TABLE positions;
TRUNCATE TABLE worker_profile;
TRUNCATE TABLE deduction;
TRUNCATE TABLE projectsite;
TRUNCATE TABLE worker;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE remember_login_tokens;

TRUNCATE TABLE assistantmanager;
TRUNCATE TABLE managers;
TRUNCATE TABLE hr;
TRUNCATE TABLE payrollstaff;
TRUNCATE TABLE timekeeper;

DELETE u
FROM users u
LEFT JOIN admin a ON a.UserID = u.id
WHERE a.UserID IS NULL;

ALTER TABLE users AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

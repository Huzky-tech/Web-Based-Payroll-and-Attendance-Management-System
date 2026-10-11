-- Hostinger-safe Bakery synchronization
-- Scope: global weekly payroll settings, the five named Bakery workers, the Bakery site,
-- and Bakery attendance from 2026-10-08 through 2026-10-11 only.
-- This script does NOT drop tables or change payroll/attendance for other sites.
--
-- Before running: export a backup from Hostinger phpMyAdmin.
-- Also upload the referenced files from local uploads/attendance_photos/ to the same
-- directory on Hostinger after this script finishes.

START TRANSACTION;

-- Required worker deduction-selection column. Safe if it already exists.
SET @has_deduction_types := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'worker'
      AND COLUMN_NAME = 'GovernmentDeductionTypes'
);
SET @sql := IF(@has_deduction_types = 0,
    'ALTER TABLE worker ADD COLUMN GovernmentDeductionTypes TEXT NULL AFTER GovernmentDeductionStatus',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Required site shift columns. Safe if they already exist.
SET @has_shift_start := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projectsite' AND COLUMN_NAME = 'ShiftStart');
SET @sql := IF(@has_shift_start = 0,
    'ALTER TABLE projectsite ADD COLUMN ShiftStart TIME NULL, ADD COLUMN LunchStart TIME NULL, ADD COLUMN LunchEnd TIME NULL, ADD COLUMN ShiftEnd TIME NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Required attendance evidence columns. Safe if they already exist.
SET @has_lunch_out := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'Lunch_Out');
SET @sql := IF(@has_lunch_out = 0,
    'ALTER TABLE attendance ADD COLUMN Lunch_Out TIME NULL, ADD COLUMN Lunch_In TIME NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_photo_path := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'PhotoPath');
SET @sql := IF(@has_photo_path = 0,
    'ALTER TABLE attendance ADD COLUMN PhotoPath VARCHAR(500) NULL, ADD COLUMN Latitude DECIMAL(10,7) NULL, ADD COLUMN Longitude DECIMAL(10,7) NULL, ADD COLUMN DistanceFromSite DECIMAL(10,2) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_am_photo := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'AMTimeInPhoto');
SET @sql := IF(@has_am_photo = 0,
    'ALTER TABLE attendance ADD COLUMN AMTimeInPhoto VARCHAR(500) NULL, ADD COLUMN PMTimeInPhoto VARCHAR(500) NULL, ADD COLUMN TimeOutPhoto VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The application now uses weekly payroll only.
ALTER TABLE payroll_settings MODIFY pay_periods VARCHAR(50) DEFAULT 'Weekly';
UPDATE payroll_settings
SET pay_periods = 'Weekly', sss_rate = 950.00, philhealth_rate = 262.50, pagibig_rate = 200.00;

-- Find Bakery by its name, so the hosted database may use a different numeric SiteID.
SET @bakery_site_id := (SELECT SiteID FROM projectsite WHERE Site_Name = 'Bakery' LIMIT 1);
SET @bakery_timekeeper_user_id := (
    SELECT Timekeeper_UserID FROM projectsite WHERE SiteID = @bakery_site_id LIMIT 1
);

-- Bakery work hours: 8 AM–12 PM and 1 PM–5 PM. This only changes the Bakery site.
UPDATE projectsite
SET Start_Date = '2026-10-08', ShiftStart = '08:00:00', LunchStart = '12:00:00',
    LunchEnd = '13:00:00', ShiftEnd = '17:00:00', Site_Manager = 'Johnny Calunsag'
WHERE SiteID = @bakery_site_id;

-- These rates and selected deductions are the current settings for the named Bakery workers.
-- They are identified by name so their hosted WorkerID may differ from the local database.
UPDATE worker SET Position = 'Skilled', RateType = 'Hourly', RateAmount = 68.75,
    GovernmentDeductionStatus = 'With Deductions',
    GovernmentDeductionTypes = '["sss","philhealth","pagibig"]'
WHERE First_Name = 'Juan' AND Last_Name = 'De La Cruz';

UPDATE worker SET Position = 'Watchman', RateType = 'Hourly', RateAmount = 56.25,
    GovernmentDeductionStatus = 'With Deductions',
    GovernmentDeductionTypes = '["sss","philhealth","pagibig"]'
WHERE First_Name = 'Marcelito' AND Last_Name = 'Ramos';

UPDATE worker SET Position = 'Driver', RateType = 'Hourly', RateAmount = 81.25,
    GovernmentDeductionStatus = 'With Deductions',
    GovernmentDeductionTypes = '["sss","philhealth","pagibig"]'
WHERE First_Name = 'Carlo' AND Last_Name = 'Garcia';

UPDATE worker SET Position = 'Laborer', RateType = 'Hourly', RateAmount = 62.50,
    GovernmentDeductionStatus = 'No Deductions', GovernmentDeductionTypes = NULL
WHERE First_Name = 'Andress' AND Last_Name = 'Flores';

UPDATE worker SET Position = 'Laborer', RateType = 'Hourly', RateAmount = 62.50,
    GovernmentDeductionStatus = 'With Deductions', GovernmentDeductionTypes = '["philhealth"]'
WHERE First_Name = 'Gabriel' AND Last_Name = 'Aquino';

-- Keep or create the Bakery worker assignments without affecting their assignments on other sites.
DROP TEMPORARY TABLE IF EXISTS bakery_worker_sync;
CREATE TEMPORARY TABLE bakery_worker_sync (
    first_name VARCHAR(100), last_name VARCHAR(100), role_on_site VARCHAR(100)
);
INSERT INTO bakery_worker_sync VALUES
('Juan','De La Cruz','Skilled'), ('Marcelito','Ramos','Watchman'),
('Carlo','Garcia','Driver'), ('Andress','Flores','Laborer'), ('Gabriel','Aquino','Laborer');

UPDATE workerassignment wa
JOIN worker w ON w.WorkerID = wa.WorkerID
JOIN bakery_worker_sync bw
    ON bw.first_name COLLATE utf8mb4_general_ci = w.First_Name COLLATE utf8mb4_general_ci
   AND bw.last_name COLLATE utf8mb4_general_ci = w.Last_Name COLLATE utf8mb4_general_ci
SET wa.Role_On_Site = bw.role_on_site, wa.Assigned_Date = '2026-10-08'
WHERE wa.SiteID = @bakery_site_id;

INSERT INTO workerassignment (WorkerID, SiteID, Assigned_Date, Role_On_Site)
SELECT w.WorkerID, @bakery_site_id, '2026-10-08', bw.role_on_site
FROM bakery_worker_sync bw
JOIN worker w
    ON w.First_Name COLLATE utf8mb4_general_ci = bw.first_name COLLATE utf8mb4_general_ci
   AND w.Last_Name COLLATE utf8mb4_general_ci = bw.last_name COLLATE utf8mb4_general_ci
WHERE @bakery_site_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM workerassignment wa
      WHERE wa.WorkerID = w.WorkerID AND wa.SiteID = @bakery_site_id
  );

-- Replace only the specified Bakery attendance period, then insert the current evidence data.
DELETE apl FROM attendance_photo_logs apl
JOIN attendance a ON a.AttendanceID = apl.AttendanceID
WHERE a.SiteID = @bakery_site_id AND a.Date BETWEEN '2026-10-08' AND '2026-10-11';

DELETE FROM attendance
WHERE SiteID = @bakery_site_id AND Date BETWEEN '2026-10-08' AND '2026-10-11';

DROP TEMPORARY TABLE IF EXISTS bakery_attendance_sync;
CREATE TEMPORARY TABLE bakery_attendance_sync (
    first_name VARCHAR(100), last_name VARCHAR(100), event_date DATE,
    hours_worked DECIMAL(10,2), time_in TIME, lunch_out TIME NULL, lunch_in TIME NULL,
    time_out TIME, attendance_status VARCHAR(20), is_late TINYINT(1),
    am_photo VARCHAR(500), pm_photo VARCHAR(500), out_photo VARCHAR(500)
);

-- Oct 8–10 use the three supplied attendance-evidence photos. Oct 11 uses the supplied keyboard photo.
INSERT INTO bakery_attendance_sync VALUES
('Juan','De La Cruz','2026-10-08',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Marcelito','Ramos','2026-10-08',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Carlo','Garcia','2026-10-08',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Andress','Flores','2026-10-08',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Gabriel','Aquino','2026-10-08',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Juan','De La Cruz','2026-10-09',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Marcelito','Ramos','2026-10-09',7.50,'08:30:00','12:00:00','13:00:00','17:00:00','Present',1,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Carlo','Garcia','2026-10-09',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Andress','Flores','2026-10-09',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Gabriel','Aquino','2026-10-09',7.75,'08:15:00','12:00:00','13:00:00','17:00:00','Present',1,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Juan','De La Cruz','2026-10-10',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Marcelito','Ramos','2026-10-10',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Carlo','Garcia','2026-10-10',0.00,'00:00:00',NULL,NULL,'00:00:00','Absent',0,NULL,NULL,NULL),
('Andress','Flores','2026-10-10',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Gabriel','Aquino','2026-10-10',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg'),
('Juan','De La Cruz','2026-10-11',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg'),
('Marcelito','Ramos','2026-10-11',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg'),
('Carlo','Garcia','2026-10-11',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg'),
('Andress','Flores','2026-10-11',7.75,'08:15:00','12:00:00','13:00:00','17:00:00','Present',1,'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg'),
('Gabriel','Aquino','2026-10-11',8.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present',0,'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg','uploads/attendance_photos/attendance_6_time_out_1788849685.jpg');

INSERT INTO attendance
    (WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours, Time_In, Lunch_Out, Lunch_In, Time_Out,
     AttendanceStatus, PhotoPath, Latitude, Longitude, DistanceFromSite,
     AMTimeInPhoto, PMTimeInPhoto, TimeOutPhoto, IsLate)
SELECT w.WorkerID, @bakery_site_id, bas.event_date, bas.hours_worked, 0,
       bas.time_in, bas.lunch_out, bas.lunch_in, bas.time_out, bas.attendance_status,
       bas.am_photo, 8.4832650, 124.6353610, 0.00,
       bas.am_photo, bas.pm_photo, bas.out_photo, bas.is_late
FROM bakery_attendance_sync bas
JOIN worker w
    ON w.First_Name COLLATE utf8mb4_general_ci = bas.first_name COLLATE utf8mb4_general_ci
   AND w.Last_Name COLLATE utf8mb4_general_ci = bas.last_name COLLATE utf8mb4_general_ci
WHERE @bakery_site_id IS NOT NULL;

-- Create the three evidence-log entries for every present record when the Bakery site has a timekeeper.
INSERT INTO attendance_photo_logs
    (AttendanceID, WorkerID, SiteID, TimekeeperID, AttendanceType, EventDate, EventTime,
     Latitude, Longitude, DistanceFromSite, PhotoPath)
SELECT a.AttendanceID, a.WorkerID, a.SiteID, @bakery_timekeeper_user_id,
       'Time In', a.Date, a.Time_In, a.Latitude, a.Longitude, a.DistanceFromSite, a.AMTimeInPhoto
FROM attendance a WHERE a.SiteID = @bakery_site_id AND a.Date BETWEEN '2026-10-08' AND '2026-10-11'
  AND a.AttendanceStatus = 'Present' AND @bakery_timekeeper_user_id IS NOT NULL
UNION ALL
SELECT a.AttendanceID, a.WorkerID, a.SiteID, @bakery_timekeeper_user_id,
       'Lunch In', a.Date, a.Lunch_In, a.Latitude, a.Longitude, a.DistanceFromSite, a.PMTimeInPhoto
FROM attendance a WHERE a.SiteID = @bakery_site_id AND a.Date BETWEEN '2026-10-08' AND '2026-10-11'
  AND a.AttendanceStatus = 'Present' AND @bakery_timekeeper_user_id IS NOT NULL
UNION ALL
SELECT a.AttendanceID, a.WorkerID, a.SiteID, @bakery_timekeeper_user_id,
       'Time Out', a.Date, a.Time_Out, a.Latitude, a.Longitude, a.DistanceFromSite, a.TimeOutPhoto
FROM attendance a WHERE a.SiteID = @bakery_site_id AND a.Date BETWEEN '2026-10-08' AND '2026-10-11'
  AND a.AttendanceStatus = 'Present' AND @bakery_timekeeper_user_id IS NOT NULL;

DROP TEMPORARY TABLE IF EXISTS bakery_attendance_sync;
DROP TEMPORARY TABLE IF EXISTS bakery_worker_sync;
COMMIT;

-- Verification: expected result is 20 rows, 19 Present (including 3 late) and 1 Absent.
SELECT Date, AttendanceStatus, IsLate, COUNT(*) AS workers
FROM attendance
WHERE SiteID = @bakery_site_id AND Date BETWEEN '2026-10-08' AND '2026-10-11'
GROUP BY Date, AttendanceStatus, IsLate
ORDER BY Date, AttendanceStatus, IsLate;

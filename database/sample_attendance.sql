-- Sample Attendance Data for Testing
-- Run this in phpMyAdmin or MySQL console after ensuring worker and projectsite records exist

USE payroll_db;

-- Sample workers (add if not exist)
INSERT IGNORE INTO workerstatus (WorkerStatusID, Status) VALUES (1, 'Active');
INSERT IGNORE INTO worker (WorkerID, First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID) VALUES
(1, 'John', 'Doe', 'Hourly', 150.00, '09123456789', '2024-01-01', 1),
(2, 'Jane', 'Smith', 'Hourly', 150.00, '09123456790', '2024-01-01', 1),
(3, 'Robert', 'Brown', 'Hourly', 150.00, '09123456791', '2024-01-01', 1);

-- Sample site assignments
INSERT IGNORE INTO workerassignment (AssignmentID, WorkerID, SiteID, Assigned_Date, Role_On_Site) VALUES
(1, 1, 1, '2024-10-01', 'Carpenter'),
(2, 2, 1, '2024-10-01', 'Electrician'),
(3, 3, 1, '2024-10-01', 'Laborer');

-- Sample attendance records for today and yesterday
INSERT INTO attendance (WorkerID, SiteID, Date, Time_In, Time_Out, Hours_Worked, Overtime_Hours, AttendanceStatus) VALUES
-- Today - mixed status
(1, 1, CURDATE(), '07:55:00', '17:05:00', 8.17, 0.17, 'Present'),
(2, 1, CURDATE(), '08:32:00', '17:00:00', 7.47, 0.00, 'Late'),
(3, 1, CURDATE(), NULL, NULL, 0.00, 0.00, 'Absent'),

-- Yesterday - full day
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 8.00, 0.00, 'Present'),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:45:00', '18:30:00', 9.75, 1.75, 'Present'),

-- Older records
(1, 1, '2024-10-01', '08:10:00', '16:45:00', 7.58, 0.00, 'Late'),
(3, 1, '2024-10-01', '08:00:00', '17:15:00', 8.25, 0.25, 'Present');

SELECT 'Sample data inserted successfully' as status;


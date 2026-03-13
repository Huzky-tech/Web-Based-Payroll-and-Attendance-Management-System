-- Sample Worker and Attendance Data
USE payroll_db;

-- Add sample workers
INSERT IGNORE INTO workerstatus (WorkerStatusID, Status) VALUES (1, 'Active'), (2, 'OnLeave');

INSERT IGNORE INTO worker (WorkerID, First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, UserID) VALUES
(1, 'John', 'Doe', 'Hourly', 25.00, '09123456789', '2024-01-01', 1, NULL),
(2, 'Jane', 'Smith', 'Hourly', 22.50, '09123456788', '2024-01-15', 1, NULL),
(3, 'Bob', 'Johnson', 'Hourly', 28.00, '09123456787', '2024-02-01', 1, NULL);

-- Sample assignments
INSERT IGNORE INTO workerassignment (AssignmentID, WorkerID, SiteID, Assigned_Date, Role_On_Site) VALUES
(1, 1, 1, '2024-07-01', 'Lead Carpenter'),
(2, 2, 1, '2024-07-01', 'Electrician'),
(3, 3, 1, '2024-07-01', 'Laborer');

-- Sample attendance (today)
INSERT INTO attendance (AttendanceID, WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours, Time_In, Time_Out, AttendanceStatus) VALUES
(1, 1, 1, CURDATE(), 8.00, 0.00, '08:00:00', '17:00:00', 'Present'),
(2, 2, 1, CURDATE(), 7.50, 0.00, '08:15:00', '16:45:00', 'Late'),
(3, 3, 1, CURDATE(), 8.00, 0.00, '07:55:00', '17:00:00', 'Present');

SELECT 'Sample data loaded! Refresh admin/attendance.php' as Status;


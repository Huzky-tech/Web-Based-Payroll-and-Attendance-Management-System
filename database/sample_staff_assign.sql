-- Sample Payroll Staff Assignment Data
-- Run in phpMyAdmin to populate the module for testing

USE payroll_db;

-- Add sample payroll staff (link to existing users)
INSERT INTO payrollstaff (UserID) VALUES 
(1), -- admin@example.com
(2); -- calunsagm66@gmail.com

-- Sample assignments
INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) VALUES 
(1, 1, NOW()),  -- Admin to Test Site
(1, 4, NOW()),  -- Admin to Haynako road
(2, 3, NOW());  -- User2 to gwen school

SELECT 
  'Sample data added! Visit admin/site_assign.php' as message;


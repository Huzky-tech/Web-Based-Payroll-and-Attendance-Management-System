-- Sample Payroll Staff and Assignments for Testing Site Assign Page
USE payroll_db;

-- Add sample payroll staff (link to existing users)
INSERT IGNORE INTO payrollstaff (UserID) VALUES 
(1), -- admin
(2); -- calunsagm66@gmail.com

-- Get PayrollStaff_IDs (assume auto-increment gives PayrollStaff_ID 1,2)
-- Sample assignments
INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) VALUES 
(1, 1, NOW()),  -- Admin to Test Site
(1, 4, NOW()),  -- Admin to Haynako road Bulua
(2, 6, NOW());  -- User2 to Downtown road

-- Verify
SELECT 
  ps.PayrollStaff_ID, u.full_name, u.email,
  COUNT(psa.staffAssignID) as assigned_sites
FROM payrollstaff ps 
JOIN users u ON ps.UserID = u.id 
LEFT JOIN payrollstaffassignment psa ON ps.PayrollStaff_ID = psa.PayrollStaff_ID
GROUP BY ps.PayrollStaff_ID;

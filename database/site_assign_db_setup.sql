-- Database: payroll_system (extending existing database)
-- Additional Tables: staff, assignments, audit_logs

USE payroll_system;

-- Staff table (payroll staff members)
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Assignments table (staff to site assignments)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    site_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (staff_id, site_id)
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user VARCHAR(255) NOT NULL,
    action TEXT NOT NULL,
    details TEXT
);

-- Insert sample staff data
INSERT INTO staff (staff_id, name, email) VALUES
('staff-a', 'Payroll Staff A', 'staff.a@company.com'),
('staff-b', 'Payroll Staff B', 'staff.b@company.com'),
('staff-c', 'Payroll Staff C', 'staff.c@company.com'),
('staff-d', 'Payroll Staff D', 'staff.d@company.com');

-- Insert assignments (Staff A assigned to Road Street Site)
INSERT INTO assignments (staff_id, site_id) VALUES
(1, 1); -- Staff A (id=1) to Road Street Site (id=1)

-- Database: employee
-- Tables: employees, payroll_staff, audit_logs

USE payroll_db;

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    site VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive', 'On-leave') DEFAULT 'Active',
    salary VARCHAR(50) NOT NULL,
    join_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payroll Staff table
CREATE TABLE payroll_staff (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    sites JSON DEFAULT ('[]'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Audit Logs table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample employees
INSERT INTO employees (name, position, site, status, salary, join_date) VALUES
('John Doe', 'Construction Worker', 'Main Street Project', 'Active', '₱15,000', '2022-05-15'),
('Jane Smith', 'Electrician', 'Downtown Office Complex', 'Active', '₱18,000', '2021-11-03'),
('Michael Johnson', 'Plumber', 'Riverside Apartments', 'Inactive', '₱16,500', '2022-02-28'),
('Sarah Williams', 'Carpenter', 'Park Avenue Mall', 'Active', '₱15,500', '2023-01-10'),
('Robert Brown', 'Construction Worker', 'Main Street Project', 'On-leave', '₱14,000', '2022-07-22'),
('Emily Davis', 'Site Manager', 'Downtown Office Complex', 'Active', '₱25,000', '2021-09-15');

-- Insert sample payroll staff
INSERT INTO payroll_staff (id, name, email, sites) VALUES
('staff-a', 'Payroll Staff A', 'staff.a@company.com', '["Road Street Site"]'),
('staff-b', 'Payroll Staff B', 'staff.b@company.com', '[]'),
('staff-c', 'Payroll Staff C', 'staff.c@company.com', '[]'),
('staff-d', 'Payroll Staff D', 'staff.d@company.com', '[]');

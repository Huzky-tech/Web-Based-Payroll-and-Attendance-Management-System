-- Database: payroll_system
-- Tables: workers, sites

CREATE DATABASE IF NOT EXISTS payroll_system;
USE payroll_system;

-- Sites table
CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(255) NOT NULL UNIQUE,
    location VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workers table
CREATE TABLE workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    initials VARCHAR(10),
    role VARCHAR(255) NOT NULL,
    status ENUM('active', 'onleave', 'inactive') DEFAULT 'active',
    site_id INT,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50) NOT NULL,
    joined_date DATE,
    attendance_rate INT DEFAULT 90,
    color ENUM('gold', 'yellow', 'blue') DEFAULT 'blue',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL
);

-- Insert sample sites
INSERT INTO sites (site_name, location, status) VALUES
('Road Street Site', 'Downtown Area', 'active'),
('Building Construction Site', 'Industrial Zone', 'active'),
('Bridge Project Alpha', 'River District', 'active');

-- Insert sample workers
INSERT INTO workers (name, initials, role, status, site_id, email, phone, joined_date, attendance_rate, color) VALUES
('John Smith', 'JS', 'Construction Worker', 'active', 1, 'john.smith@example.com', '+1 234-567-8901', '2023-01-15', 95, 'gold'),
('Sarah Johnson', 'SJ', 'Foreman', 'active', 2, 'sarah.j@example.com', '+1 234-567-8902', '2022-11-20', 98, 'yellow'),
('Mike Brown', 'MB', 'Equipment Operator', 'onleave', 1, 'mike.b@example.com', '+1 234-567-8903', '2023-03-10', 92, 'gold'),
('Ava Miller', 'AM', 'Safety Officer', 'active', 3, 'ava.m@example.com', '+1 234-567-8904', '2022-08-05', 96, 'blue');

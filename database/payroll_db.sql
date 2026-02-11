CREATE DATABASE IF NOT EXISTS payroll_db;
USE payroll_db;

SET FOREIGN_KEY_CHECKS = 0;

/* =========================
   USERS (EXISTING)
========================= */

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (email, password)
VALUES ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE email=email;

/* =========================
   LOOKUP / STATUS TABLES
========================= */

CREATE TABLE WorkerStatus (
    WorkerStatusID INT AUTO_INCREMENT PRIMARY KEY,
    Status ENUM('Active','OnLeave','Inactive') NOT NULL
);

CREATE TABLE LocationStatus (
    LocationID INT AUTO_INCREMENT PRIMARY KEY,
    Status VARCHAR(50) NOT NULL
);

CREATE TABLE DelayedCategory (
    Delayed_Category INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL
);

CREATE TABLE Deduction (
    DeductionID INT AUTO_INCREMENT PRIMARY KEY,
    Deduction_Name VARCHAR(100) NOT NULL UNIQUE
);

/* =========================
   USER ROLE TABLES
========================= */

CREATE TABLE Admin (
    Admin_ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

CREATE TABLE HR (
    HR_ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

CREATE TABLE Timekeeper (
    Timekeeper_ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

CREATE TABLE AssistantManager (
    AssistantManager_ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

CREATE TABLE PayrollStaff (
    PayrollStaff_ID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

/* =========================
   WORKER
========================= */

CREATE TABLE Worker (
    WorkerID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(100) NOT NULL,
    Last_Name VARCHAR(100) NOT NULL,
    RateType ENUM('Hourly','Salary') NOT NULL,
    RateAmount DECIMAL(10,2) NOT NULL,
    Phone VARCHAR(20) UNIQUE,
    DateHired DATE NOT NULL,
    WorkerStatusID INT NOT NULL,
    UserID INT,

    FOREIGN KEY (WorkerStatusID) REFERENCES WorkerStatus(WorkerStatusID),
    FOREIGN KEY (UserID) REFERENCES users(id),

    INDEX idx_worker_status (WorkerStatusID),
    INDEX idx_worker_user (UserID)
);

CREATE TABLE Positions (
    PositionsID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    PositionName VARCHAR(100) NOT NULL,
    BasedHourlyRate DECIMAL(10,2) NOT NULL,
    OvertimeMultiplier DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID),
    INDEX idx_positions_worker (WorkerID)
);

/* =========================
   APPROVALS & AUDIT
========================= */

CREATE TABLE Approvals (
    ApprovalID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    Action_Type VARCHAR(50) NOT NULL,
    Approval_By INT NOT NULL,
    Approval_Status VARCHAR(50) NOT NULL,
    Date DATETIME NOT NULL,

    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID),
    FOREIGN KEY (Approval_By) REFERENCES users(id)
);

CREATE TABLE Audit_logs (
    Audit_logsID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Action VARCHAR(255) NOT NULL,
    Details TEXT,
    Date DATETIME NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(id)
);

/* =========================
   PROJECT / SITE (NO CIRCULAR FK)
========================= */

CREATE TABLE ProjectSite (
    SiteID INT AUTO_INCREMENT PRIMARY KEY,
    Site_Name VARCHAR(100) NOT NULL,
    Location VARCHAR(255),
    Project_Type VARCHAR(50),
    Start_Date DATE NOT NULL,
    End_Date DATE,
    Required_Workers INT,
    Site_Manager VARCHAR(100),
    Status VARCHAR(50),
    LocationID INT NOT NULL,

    FOREIGN KEY (LocationID) REFERENCES LocationStatus(LocationID),
    INDEX idx_site_location (LocationID)
);

CREATE TABLE ProjectHistory (
    History_ID INT AUTO_INCREMENT PRIMARY KEY,
    SiteID INT NOT NULL,
    Status VARCHAR(50) NOT NULL,
    Updated_By INT NOT NULL,
    Update_Date DATETIME NOT NULL,
    Notes TEXT,

    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID),
    FOREIGN KEY (Updated_By) REFERENCES users(id),

    INDEX idx_history_site (SiteID)
);

CREATE TABLE Site_schedule (
    Site_ScheduleID INT AUTO_INCREMENT PRIMARY KEY,
    SiteID INT NOT NULL,
    DayOfWeek VARCHAR(20) NOT NULL,
    ShiftStart TIME NOT NULL,
    ShiftEnd TIME NOT NULL,
    BreakDuration INT DEFAULT 0,

    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID)
);

/* =========================
   ASSIGNMENTS
========================= */

CREATE TABLE WorkerAssignment (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    SiteID INT NOT NULL,
    Assigned_Date DATE NOT NULL,
    Role_On_Site VARCHAR(100),

    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID)
);

CREATE TABLE SiteAssignmentHistory (
    Site_HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    SiteID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE,

    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID)
);

/* =========================
   ATTENDANCE
========================= */

CREATE TABLE Attendance (
    AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    SiteID INT NOT NULL,
    Date DATE NOT NULL,
    Hours_Worked DECIMAL(5,2) NOT NULL,
    Overtime_Hours DECIMAL(5,2) DEFAULT 0,
    Time_In TIME NOT NULL,
    Time_Out TIME NOT NULL,
    AttendanceStatus ENUM('Present','Late','Absent') NOT NULL,

    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID),

    INDEX idx_worker_date (WorkerID, Date),
    INDEX idx_site_date (SiteID, Date)
);

CREATE TABLE Timekeeper_Reports (
    TK_ReportsID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    SiteID INT NOT NULL,
    ReportDate DATE NOT NULL,
    DelayType VARCHAR(100),
    AdditionalNotes TEXT,
    Status VARCHAR(50),
    Delayed_Category INT,

    FOREIGN KEY (UserID) REFERENCES users(id),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID),
    FOREIGN KEY (Delayed_Category) REFERENCES DelayedCategory(Delayed_Category)
);

/* =========================
   PAYROLL
========================= */

CREATE TABLE Payroll (
    PayrollID INT AUTO_INCREMENT PRIMARY KEY,
    WorkerID INT NOT NULL,
    Pay_Period_Start DATE NOT NULL,
    Pay_Period_End DATE NOT NULL,
    Gross_Pay DECIMAL(10,2) NOT NULL,
    Total_Deductions DECIMAL(10,2) NOT NULL,
    Net_Pay DECIMAL(10,2) NOT NULL,
    Date_Processed DATE NOT NULL,

    FOREIGN KEY (WorkerID) REFERENCES Worker(WorkerID)
);

CREATE TABLE PayrollAttendance (
    PayrollAttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollID INT NOT NULL,
    AttendanceID INT NOT NULL,

    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID),
    FOREIGN KEY (AttendanceID) REFERENCES Attendance(AttendanceID)
);

CREATE TABLE Payroll_Records (
    Payroll_RecordsID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollID INT NOT NULL,
    SiteID INT NOT NULL,
    Period_start DATE NOT NULL,
    Period_end DATE NOT NULL,
    Total_gross_pay DECIMAL(10,2) NOT NULL,
    Total_deductions DECIMAL(10,2) NOT NULL,
    Total_net_pay DECIMAL(10,2) NOT NULL,
    Status VARCHAR(50) NOT NULL,

    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID)
);

/* ✅ FIXED PAYROLL DEDUCTIONS (NORMALIZED) */
CREATE TABLE Payroll_Deduction (
    PayrollDeductionID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollID INT NOT NULL,
    DeductionID INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID),
    FOREIGN KEY (DeductionID) REFERENCES Deduction(DeductionID),

    INDEX idx_pd_payroll (PayrollID),
    INDEX idx_pd_deduction (DeductionID)
);

/* =========================
   EQUIPMENT
========================= */

CREATE TABLE BrokenEquipment (
    BrokenEquipment_ID INT AUTO_INCREMENT PRIMARY KEY,
    Equipment_Name VARCHAR(100) NOT NULL,
    Quantity INT NOT NULL,
    Date DATE NOT NULL,
    Time TIME NOT NULL,
    Description TEXT,
    Replacement_Cost DECIMAL(10,2) NOT NULL
);

/* =========================
   PAYSLIP & BATCH
========================= */

CREATE TABLE Payslip (
    PayslipID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollID INT NOT NULL,
    Issue_Date DATE NOT NULL,
    Payslip_Number VARCHAR(50) UNIQUE,

    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID)
);

CREATE TABLE PayrollBatch (
    Payroll_BatchID INT AUTO_INCREMENT PRIMARY KEY,
    SiteID INT NOT NULL,
    Date_Created DATE NOT NULL,
    Status VARCHAR(50) NOT NULL,
    Total_Amount DECIMAL(12,2) NOT NULL,
    UserID INT NOT NULL,

    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID),
    FOREIGN KEY (UserID) REFERENCES users(id)
);

CREATE TABLE PayrollStaffAssignment (
    staffAssignID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollStaff_ID INT NOT NULL,
    SiteID INT NOT NULL,
    Created_at DATETIME NOT NULL,
    Updated_at DATETIME,

    FOREIGN KEY (PayrollStaff_ID) REFERENCES PayrollStaff(PayrollStaff_ID),
    FOREIGN KEY (SiteID) REFERENCES ProjectSite(SiteID)
);

/* =========================
   SETTINGS TABLES
========================= */

-- Extend users table
ALTER TABLE users ADD COLUMN full_name VARCHAR(255);
ALTER TABLE users ADD COLUMN role ENUM('Worker','Payroll Staff','Head Manager','Admin') DEFAULT 'Worker';
ALTER TABLE users ADD COLUMN status ENUM('Active','Inactive') DEFAULT 'Active';
ALTER TABLE users ADD COLUMN last_login DATETIME;

-- Company Settings
CREATE TABLE company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'Philippians CDO Construction Company',
    tax_id VARCHAR(50) DEFAULT '123-45-6789',
    phone VARCHAR(20) DEFAULT '(555) 123-4567',
    email VARCHAR(255) DEFAULT 'info@philippianscdo.com',
    address TEXT DEFAULT '123 Main Street, CDO City',
    logo_path VARCHAR(255)
);

-- Payroll Settings
CREATE TABLE payroll_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pay_periods VARCHAR(50) DEFAULT 'Semi-monthly (1-15, 16-end)',
    sss_rate DECIMAL(5,2) DEFAULT 0.00,
    philhealth_rate DECIMAL(5,2) DEFAULT 3.00,
    pagibig_rate DECIMAL(5,2) DEFAULT 2.00,
    tax_table VARCHAR(50) DEFAULT 'Latest BIR Tax Table',
    allow_overtime BOOLEAN DEFAULT TRUE,
    allow_night_diff BOOLEAN DEFAULT TRUE,
    overtime_rate DECIMAL(5,2) DEFAULT 1.25,
    night_diff_rate DECIMAL(5,2) DEFAULT 1.10
);

-- Notification Settings
CREATE TABLE notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_notifications BOOLEAN DEFAULT TRUE,
    in_system_notifications BOOLEAN DEFAULT TRUE,
    leave_request_updates BOOLEAN DEFAULT TRUE,
    payroll_processing BOOLEAN DEFAULT TRUE,
    attendance_issues BOOLEAN DEFAULT FALSE,
    system_updates BOOLEAN DEFAULT FALSE,
    daily_reports BOOLEAN DEFAULT FALSE,
    email_digest_frequency VARCHAR(20) DEFAULT 'Daily'
);

-- Security Settings
CREATE TABLE security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    password_expiry_days INT DEFAULT 90,
    min_password_length INT DEFAULT 8,
    require_special_char BOOLEAN DEFAULT TRUE,
    require_number BOOLEAN DEFAULT TRUE,
    require_uppercase BOOLEAN DEFAULT TRUE,
    max_login_attempts INT DEFAULT 5,
    session_timeout_minutes INT DEFAULT 30,
    enable_2fa BOOLEAN DEFAULT FALSE,
    enable_ip_restriction BOOLEAN DEFAULT FALSE
);

-- System Settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    debug_mode BOOLEAN DEFAULT FALSE,
    data_retention_days INT DEFAULT 365,
    backup_schedule VARCHAR(20) DEFAULT 'Daily',
    timezone VARCHAR(50) DEFAULT 'Asia/Manila (GMT+8)',
    date_format VARCHAR(20) DEFAULT 'MM/DD/YYYY',
    time_format VARCHAR(20) DEFAULT '12-hour (AM/PM)',
    system_version VARCHAR(20) DEFAULT '1.0.5',
    last_update DATE DEFAULT '2023-07-01',
    server_environment VARCHAR(50) DEFAULT 'Production',
    database_size VARCHAR(20) DEFAULT '125 MB'
);

-- Insert default data
INSERT INTO company_settings (company_name, tax_id, phone, email, address) VALUES ('Philippians CDO Construction Company', '123-45-6789', '(555) 123-4567', 'info@philippianscdo.com', '123 Main Street, CDO City');
INSERT INTO payroll_settings (pay_periods, philhealth_rate, pagibig_rate, tax_table, allow_overtime, allow_night_diff, overtime_rate, night_diff_rate) VALUES ('Semi-monthly (1-15, 16-end)', 3.00, 2.00, 'Latest BIR Tax Table', TRUE, TRUE, 1.25, 1.10);
INSERT INTO notification_settings (email_notifications, in_system_notifications, leave_request_updates, payroll_processing) VALUES (TRUE, TRUE, TRUE, TRUE);
INSERT INTO security_settings (password_expiry_days, min_password_length, require_special_char, require_number, require_uppercase, max_login_attempts, session_timeout_minutes) VALUES (90, 8, TRUE, TRUE, TRUE, 5, 30);
INSERT INTO system_settings (maintenance_mode, debug_mode, data_retention_days, backup_schedule, timezone, date_format, time_format, system_version, last_update, server_environment, database_size) VALUES (FALSE, FALSE, 365, 'Daily', 'Asia/Manila (GMT+8)', 'MM/DD/YYYY', '12-hour (AM/PM)', '1.0.5', '2023-07-01', 'Production', '125 MB');

SET FOREIGN_KEY_CHECKS = 1;

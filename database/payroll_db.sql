-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 05:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `payroll_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`, `UserID`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `ApprovalID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `Action_Type` varchar(50) NOT NULL,
  `Approval_By` int(11) NOT NULL,
  `Approval_Status` varchar(50) NOT NULL,
  `Date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assistantmanager`
--

CREATE TABLE `assistantmanager` (
  `AssistantManager_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Hours_Worked` decimal(5,2) NOT NULL,
  `Overtime_Hours` decimal(5,2) DEFAULT 0.00,
  `Time_In` time NOT NULL,
  `Time_Out` time NOT NULL,
  `AttendanceStatus` enum('Present','Late','Absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `Audit_logsID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Details` text DEFAULT NULL,
  `Date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`Audit_logsID`, `UserID`, `Action`, `Details`, `Date`) VALUES
(1, 1, 'User Login', 'Successful login', '2026-02-03 02:00:29'),
(2, 1, 'User Role Update', 'Changed role to Manager', '2026-02-04 02:00:29'),
(3, 1, 'Failed Login Attempt', 'IP 192.168.1.1 blocked', '2026-02-05 02:00:29'),
(4, 1, 'Payroll Processed', 'Batch 2026-02-09', '2026-02-06 02:00:29'),
(5, 1, 'Attendance Modified', 'Adjusted hours for Worker 1', '2026-02-07 02:00:29'),
(6, 1, 'System Config Change', 'Password policy update', '2026-02-08 02:00:29'),
(7, 1, 'Site Assignment', 'Reassigned staff to Site A', '2026-02-09 02:00:29'),
(8, 1, 'Database Backup', 'Full backup completed', '2026-02-09 02:00:29'),
(9, 1, 'Site Created', 'Created site: Haynako road', '2026-02-09 17:47:37'),
(10, 1, 'Site Created', 'Created site: Downtown road', '2026-02-09 19:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `brokenequipment`
--

CREATE TABLE `brokenequipment` (
  `BrokenEquipment_ID` int(11) NOT NULL,
  `Equipment_Name` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Description` text DEFAULT NULL,
  `Replacement_Cost` decimal(10,2) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Timekeeper_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT 'Philippians CDO Construction Company',
  `tax_id` varchar(50) DEFAULT '123-45-6789',
  `phone` varchar(20) DEFAULT '(555) 123-4567',
  `email` varchar(255) DEFAULT 'info@philippianscdo.com',
  `address` text DEFAULT '123 Main Street, CDO City',
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `tax_id`, `phone`, `email`, `address`, `logo_path`) VALUES
(1, 'Philippians CDO Construction Company', '123-45-6789', '(555) 123-4567', 'info@philippianscdo.com', '123 Main Street, CDO City', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deduction`
--

CREATE TABLE `deduction` (
  `DeductionID` int(11) NOT NULL,
  `Deduction_Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delayedcategory`
--

CREATE TABLE `delayedcategory` (
  `Delayed_Category` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr`
--

CREATE TABLE `hr` (
  `HR_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locationstatus`
--

CREATE TABLE `locationstatus` (
  `LocationID` int(11) NOT NULL,
  `Status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locationstatus`
--

INSERT INTO `locationstatus` (`LocationID`, `Status`) VALUES
(1, 'Active'),
(2, 'Inactive'),
(3, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `in_system_notifications` tinyint(1) DEFAULT 1,
  `leave_request_updates` tinyint(1) DEFAULT 1,
  `payroll_processing` tinyint(1) DEFAULT 1,
  `attendance_issues` tinyint(1) DEFAULT 0,
  `system_updates` tinyint(1) DEFAULT 0,
  `daily_reports` tinyint(1) DEFAULT 0,
  `email_digest_frequency` varchar(20) DEFAULT 'Daily'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `email_notifications`, `in_system_notifications`, `leave_request_updates`, `payroll_processing`, `attendance_issues`, `system_updates`, `daily_reports`, `email_digest_frequency`) VALUES
(1, 1, 1, 0, 0, 0, 0, 0, 'Daily');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `PayrollID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `Pay_Period_Start` date NOT NULL,
  `Pay_Period_End` date NOT NULL,
  `Gross_Pay` decimal(10,2) NOT NULL,
  `Total_Deductions` decimal(10,2) NOT NULL,
  `Net_Pay` decimal(10,2) NOT NULL,
  `Date_Processed` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrollattendance`
--

CREATE TABLE `payrollattendance` (
  `PayrollAttendanceID` int(11) NOT NULL,
  `PayrollID` int(11) NOT NULL,
  `AttendanceID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrollbatch`
--

CREATE TABLE `payrollbatch` (
  `Payroll_BatchID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Date_Created` date NOT NULL,
  `Status` varchar(50) NOT NULL,
  `Total_Amount` decimal(12,2) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrollstaff`
--

CREATE TABLE `payrollstaff` (
  `PayrollStaff_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrollstaffassignment`
--

CREATE TABLE `payrollstaffassignment` (
  `staffAssignID` int(11) NOT NULL,
  `PayrollStaff_ID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Created_at` datetime NOT NULL,
  `Updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deduction`
--

CREATE TABLE `payroll_deduction` (
  `PayrollDeductionID` int(11) NOT NULL,
  `PayrollID` int(11) NOT NULL,
  `DeductionID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `Payroll_RecordsID` int(11) NOT NULL,
  `PayrollID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Period_start` date NOT NULL,
  `Period_end` date NOT NULL,
  `Total_gross_pay` decimal(10,2) NOT NULL,
  `Total_deductions` decimal(10,2) NOT NULL,
  `Total_net_pay` decimal(10,2) NOT NULL,
  `Status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL,
  `pay_periods` varchar(50) DEFAULT 'Semi-monthly (1-15, 16-end)',
  `sss_rate` decimal(5,2) DEFAULT 0.00,
  `philhealth_rate` decimal(5,2) DEFAULT 3.00,
  `pagibig_rate` decimal(5,2) DEFAULT 2.00,
  `tax_table` varchar(50) DEFAULT 'Latest BIR Tax Table',
  `allow_overtime` tinyint(1) DEFAULT 1,
  `allow_night_diff` tinyint(1) DEFAULT 1,
  `overtime_rate` decimal(5,2) DEFAULT 1.25,
  `night_diff_rate` decimal(5,2) DEFAULT 1.10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `pay_periods`, `sss_rate`, `philhealth_rate`, `pagibig_rate`, `tax_table`, `allow_overtime`, `allow_night_diff`, `overtime_rate`, `night_diff_rate`) VALUES
(1, 'Semi-monthly (1-15, 16-end)', 0.00, 3.00, 2.00, '0', 1, 1, 1.25, 1.10);

-- --------------------------------------------------------

--
-- Table structure for table `payslip`
--

CREATE TABLE `payslip` (
  `PayslipID` int(11) NOT NULL,
  `PayrollID` int(11) NOT NULL,
  `Issue_Date` date NOT NULL,
  `Payslip_Number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `PositionsID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `PositionName` varchar(100) NOT NULL,
  `BasedHourlyRate` decimal(10,2) NOT NULL,
  `OvertimeMultiplier` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projecthistory`
--

CREATE TABLE `projecthistory` (
  `History_ID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `Updated_By` int(11) NOT NULL,
  `Update_Date` datetime NOT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projectsite`
--

CREATE TABLE `projectsite` (
  `SiteID` int(11) NOT NULL,
  `Site_Name` varchar(100) NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Project_Type` varchar(50) DEFAULT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date DEFAULT NULL,
  `LocationID` int(11) NOT NULL,
  `Required_Workers` int(11) DEFAULT NULL,
  `Site_Manager` varchar(100) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projectsite`
--

INSERT INTO `projectsite` (`SiteID`, `Site_Name`, `Location`, `Project_Type`, `Start_Date`, `End_Date`, `LocationID`, `Required_Workers`, `Site_Manager`, `Status`) VALUES
(1, 'Test Site', 'Test Location', NULL, '2023-10-01', NULL, 1, 10, 'John Doe', 'Active'),
(2, 'school', 'tertre', NULL, '2026-02-08', NULL, 1, 10, 'rrfreweqrw', 'active'),
(3, 'gwen school building', 'carmen', NULL, '2026-02-08', NULL, 1, 30, 'John bwesit', 'active'),
(4, 'Haynako road', 'Bulua', NULL, '2026-02-08', NULL, 1, 20, 'mj', 'active'),
(5, 'Haynako road', 'tertre', NULL, '2026-02-09', NULL, 1, 15, 'mj', 'active'),
(6, 'Downtown road', 'Bulua', NULL, '2026-02-09', NULL, 1, 12, 'mj', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `id` int(11) NOT NULL,
  `password_expiry_days` int(11) DEFAULT 90,
  `min_password_length` int(11) DEFAULT 8,
  `require_special_char` tinyint(1) DEFAULT 1,
  `require_number` tinyint(1) DEFAULT 1,
  `require_uppercase` tinyint(1) DEFAULT 1,
  `max_login_attempts` int(11) DEFAULT 5,
  `session_timeout_minutes` int(11) DEFAULT 30,
  `enable_2fa` tinyint(1) DEFAULT 0,
  `enable_ip_restriction` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`id`, `password_expiry_days`, `min_password_length`, `require_special_char`, `require_number`, `require_uppercase`, `max_login_attempts`, `session_timeout_minutes`, `enable_2fa`, `enable_ip_restriction`) VALUES
(1, 90, 8, 1, 1, 1, 5, 30, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `siteassignmenthistory`
--

CREATE TABLE `siteassignmenthistory` (
  `Site_HistoryID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_schedule`
--

CREATE TABLE `site_schedule` (
  `Site_ScheduleID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `DayOfWeek` varchar(20) NOT NULL,
  `ShiftStart` time NOT NULL,
  `ShiftEnd` time NOT NULL,
  `BreakDuration` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `debug_mode` tinyint(1) DEFAULT 0,
  `data_retention_days` int(11) DEFAULT 365,
  `backup_schedule` varchar(20) DEFAULT 'Daily',
  `timezone` varchar(50) DEFAULT 'Asia/Manila (GMT+8)',
  `date_format` varchar(20) DEFAULT 'MM/DD/YYYY',
  `time_format` varchar(20) DEFAULT '12-hour (AM/PM)',
  `system_version` varchar(20) DEFAULT '1.0.5',
  `last_update` date DEFAULT '2023-07-01',
  `server_environment` varchar(50) DEFAULT 'Production',
  `database_size` varchar(20) DEFAULT '125 MB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `maintenance_mode`, `debug_mode`, `data_retention_days`, `backup_schedule`, `timezone`, `date_format`, `time_format`, `system_version`, `last_update`, `server_environment`, `database_size`) VALUES
(1, 0, 0, 365, 'Daily', 'Asia/Manila (GMT+8)', 'MM/DD/YYYY', '12-hour (AM/PM)', '1.0.5', '2023-07-01', 'Production', '125 MB');

-- --------------------------------------------------------

--
-- Table structure for table `timekeeper`
--

CREATE TABLE `timekeeper` (
  `Timekeeper_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timekeeper_reports`
--

CREATE TABLE `timekeeper_reports` (
  `TK_ReportsID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `ReportDate` date NOT NULL,
  `DelayType` varchar(100) DEFAULT NULL,
  `AdditionalNotes` text DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Delayed_Category` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`, `full_name`, `status`, `last_login`) VALUES
(1, 'admin@example.com', '$2y$10$IAhdjAkf0L8BqSJjKhF0n.55P6AbXxgpRnIy0Pj.YDSx3jjyrO1Ie', '2026-01-21 16:23:48', NULL, 'Active', NULL),
(2, 'calunsagm66@gmail.com', '$2y$10$B1Za9IAJTT65Yx6JYwP0aOMl2PrpeRM1knnlyu8P4F6VOYrPfpB46', '2026-01-21 17:06:34', NULL, 'Active', NULL),
(7, 'worker@example.com', '$2y$10$VsOk0T6lVTmZlvMqAtOvGOi2a/6kdjjaTDkFHiST9Eo0fc3QKy4hO', '2026-02-11 03:13:55', 'worker', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `worker`
--

CREATE TABLE `worker` (
  `WorkerID` int(11) NOT NULL,
  `First_Name` varchar(100) NOT NULL,
  `Last_Name` varchar(100) NOT NULL,
  `RateType` enum('Hourly','Salary') NOT NULL,
  `RateAmount` decimal(10,2) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `DateHired` date NOT NULL,
  `WorkerStatusID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workerassignment`
--

CREATE TABLE `workerassignment` (
  `AssignmentID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Assigned_Date` date NOT NULL,
  `Role_On_Site` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workerstatus`
--

CREATE TABLE `workerstatus` (
  `WorkerStatusID` int(11) NOT NULL,
  `Status` enum('Active','OnLeave','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`ApprovalID`),
  ADD KEY `WorkerID` (`WorkerID`),
  ADD KEY `approvals` (`Approval_By`);

--
-- Indexes for table `assistantmanager`
--
ALTER TABLE `assistantmanager`
  ADD PRIMARY KEY (`AssistantManager_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `unique_worker_date` (`WorkerID`,`Date`),
  ADD KEY `idx_worker_date` (`WorkerID`,`Date`),
  ADD KEY `idx_site_date` (`SiteID`,`Date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`Audit_logsID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `brokenequipment`
--
ALTER TABLE `brokenequipment`
  ADD PRIMARY KEY (`BrokenEquipment_ID`),
  ADD KEY `brokenequipment_fk_site` (`SiteID`),
  ADD KEY `fk_brokenequipment_timekeeper` (`Timekeeper_ID`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deduction`
--
ALTER TABLE `deduction`
  ADD PRIMARY KEY (`DeductionID`),
  ADD UNIQUE KEY `Deduction_Name` (`Deduction_Name`);

--
-- Indexes for table `delayedcategory`
--
ALTER TABLE `delayedcategory`
  ADD PRIMARY KEY (`Delayed_Category`);

--
-- Indexes for table `hr`
--
ALTER TABLE `hr`
  ADD PRIMARY KEY (`HR_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `locationstatus`
--
ALTER TABLE `locationstatus`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`PayrollID`),
  ADD UNIQUE KEY `unique_worker_period` (`WorkerID`,`Pay_Period_Start`,`Pay_Period_End`);

--
-- Indexes for table `payrollattendance`
--
ALTER TABLE `payrollattendance`
  ADD PRIMARY KEY (`PayrollAttendanceID`),
  ADD KEY `PayrollID` (`PayrollID`),
  ADD KEY `AttendanceID` (`AttendanceID`);

--
-- Indexes for table `payrollbatch`
--
ALTER TABLE `payrollbatch`
  ADD PRIMARY KEY (`Payroll_BatchID`),
  ADD KEY `SiteID` (`SiteID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `payrollstaff`
--
ALTER TABLE `payrollstaff`
  ADD PRIMARY KEY (`PayrollStaff_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `payrollstaffassignment`
--
ALTER TABLE `payrollstaffassignment`
  ADD PRIMARY KEY (`staffAssignID`),
  ADD KEY `PayrollStaff_ID` (`PayrollStaff_ID`),
  ADD KEY `SiteID` (`SiteID`);

--
-- Indexes for table `payroll_deduction`
--
ALTER TABLE `payroll_deduction`
  ADD PRIMARY KEY (`PayrollDeductionID`),
  ADD KEY `idx_pd_payroll` (`PayrollID`),
  ADD KEY `idx_pd_deduction` (`DeductionID`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`Payroll_RecordsID`),
  ADD KEY `PayrollID` (`PayrollID`),
  ADD KEY `SiteID` (`SiteID`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslip`
--
ALTER TABLE `payslip`
  ADD PRIMARY KEY (`PayslipID`),
  ADD UNIQUE KEY `Payslip_Number` (`Payslip_Number`),
  ADD KEY `PayrollID` (`PayrollID`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`PositionsID`),
  ADD KEY `idx_positions_worker` (`WorkerID`);

--
-- Indexes for table `projecthistory`
--
ALTER TABLE `projecthistory`
  ADD PRIMARY KEY (`History_ID`),
  ADD KEY `Updated_By` (`Updated_By`),
  ADD KEY `idx_history_site` (`SiteID`);

--
-- Indexes for table `projectsite`
--
ALTER TABLE `projectsite`
  ADD PRIMARY KEY (`SiteID`),
  ADD KEY `idx_site_location` (`LocationID`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siteassignmenthistory`
--
ALTER TABLE `siteassignmenthistory`
  ADD PRIMARY KEY (`Site_HistoryID`),
  ADD KEY `WorkerID` (`WorkerID`),
  ADD KEY `SiteID` (`SiteID`);

--
-- Indexes for table `site_schedule`
--
ALTER TABLE `site_schedule`
  ADD PRIMARY KEY (`Site_ScheduleID`),
  ADD KEY `SiteID` (`SiteID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timekeeper`
--
ALTER TABLE `timekeeper`
  ADD PRIMARY KEY (`Timekeeper_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  ADD PRIMARY KEY (`TK_ReportsID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `SiteID` (`SiteID`),
  ADD KEY `Delayed_Category` (`Delayed_Category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `worker`
--
ALTER TABLE `worker`
  ADD PRIMARY KEY (`WorkerID`),
  ADD UNIQUE KEY `Phone` (`Phone`),
  ADD KEY `idx_worker_status` (`WorkerStatusID`),
  ADD KEY `idx_worker_user` (`UserID`);

--
-- Indexes for table `workerassignment`
--
ALTER TABLE `workerassignment`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD UNIQUE KEY `unique_worker_site` (`WorkerID`,`SiteID`),
  ADD KEY `SiteID` (`SiteID`);

--
-- Indexes for table `workerstatus`
--
ALTER TABLE `workerstatus`
  ADD PRIMARY KEY (`WorkerStatusID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assistantmanager`
--
ALTER TABLE `assistantmanager`
  MODIFY `AssistantManager_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `Audit_logsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `brokenequipment`
--
ALTER TABLE `brokenequipment`
  MODIFY `BrokenEquipment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deduction`
--
ALTER TABLE `deduction`
  MODIFY `DeductionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delayedcategory`
--
ALTER TABLE `delayedcategory`
  MODIFY `Delayed_Category` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr`
--
ALTER TABLE `hr`
  MODIFY `HR_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locationstatus`
--
ALTER TABLE `locationstatus`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `PayrollID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrollattendance`
--
ALTER TABLE `payrollattendance`
  MODIFY `PayrollAttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrollbatch`
--
ALTER TABLE `payrollbatch`
  MODIFY `Payroll_BatchID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrollstaff`
--
ALTER TABLE `payrollstaff`
  MODIFY `PayrollStaff_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrollstaffassignment`
--
ALTER TABLE `payrollstaffassignment`
  MODIFY `staffAssignID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deduction`
--
ALTER TABLE `payroll_deduction`
  MODIFY `PayrollDeductionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `Payroll_RecordsID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payslip`
--
ALTER TABLE `payslip`
  MODIFY `PayslipID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `PositionsID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projecthistory`
--
ALTER TABLE `projecthistory`
  MODIFY `History_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projectsite`
--
ALTER TABLE `projectsite`
  MODIFY `SiteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_settings`
--
ALTER TABLE `security_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siteassignmenthistory`
--
ALTER TABLE `siteassignmenthistory`
  MODIFY `Site_HistoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_schedule`
--
ALTER TABLE `site_schedule`
  MODIFY `Site_ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timekeeper`
--
ALTER TABLE `timekeeper`
  MODIFY `Timekeeper_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  MODIFY `TK_ReportsID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `worker`
--
ALTER TABLE `worker`
  MODIFY `WorkerID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workerassignment`
--
ALTER TABLE `workerassignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workerstatus`
--
ALTER TABLE `workerstatus`
  MODIFY `WorkerStatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `WorkerID` FOREIGN KEY (`WorkerID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `approvals` FOREIGN KEY (`Approval_By`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  ADD CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`Approval_By`) REFERENCES `users` (`id`);

--
-- Constraints for table `assistantmanager`
--
ALTER TABLE `assistantmanager`
  ADD CONSTRAINT `assistantmanager_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `brokenequipment`
--
ALTER TABLE `brokenequipment`
  ADD CONSTRAINT `brokenequipment_fk_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  ADD CONSTRAINT `fk_brokenequipment_timekeeper` FOREIGN KEY (`Timekeeper_ID`) REFERENCES `timekeeper` (`Timekeeper_ID`);

--
-- Constraints for table `hr`
--
ALTER TABLE `hr`
  ADD CONSTRAINT `hr_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`);

--
-- Constraints for table `payrollattendance`
--
ALTER TABLE `payrollattendance`
  ADD CONSTRAINT `payrollattendance_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  ADD CONSTRAINT `payrollattendance_ibfk_2` FOREIGN KEY (`AttendanceID`) REFERENCES `attendance` (`AttendanceID`);

--
-- Constraints for table `payrollbatch`
--
ALTER TABLE `payrollbatch`
  ADD CONSTRAINT `payrollbatch_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  ADD CONSTRAINT `payrollbatch_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `payrollstaff`
--
ALTER TABLE `payrollstaff`
  ADD CONSTRAINT `payrollstaff_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `payrollstaffassignment`
--
ALTER TABLE `payrollstaffassignment`
  ADD CONSTRAINT `payrollstaffassignment_ibfk_1` FOREIGN KEY (`PayrollStaff_ID`) REFERENCES `payrollstaff` (`PayrollStaff_ID`),
  ADD CONSTRAINT `payrollstaffassignment_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);

--
-- Constraints for table `payroll_deduction`
--
ALTER TABLE `payroll_deduction`
  ADD CONSTRAINT `payroll_deduction_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  ADD CONSTRAINT `payroll_deduction_ibfk_2` FOREIGN KEY (`DeductionID`) REFERENCES `deduction` (`DeductionID`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);

--
-- Constraints for table `payslip`
--
ALTER TABLE `payslip`
  ADD CONSTRAINT `payslip_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`);

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`);

--
-- Constraints for table `projecthistory`
--
ALTER TABLE `projecthistory`
  ADD CONSTRAINT `projecthistory_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  ADD CONSTRAINT `projecthistory_ibfk_2` FOREIGN KEY (`Updated_By`) REFERENCES `users` (`id`);

--
-- Constraints for table `projectsite`
--
ALTER TABLE `projectsite`
  ADD CONSTRAINT `projectsite_ibfk_1` FOREIGN KEY (`LocationID`) REFERENCES `locationstatus` (`LocationID`);

--
-- Constraints for table `siteassignmenthistory`
--
ALTER TABLE `siteassignmenthistory`
  ADD CONSTRAINT `siteassignmenthistory_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  ADD CONSTRAINT `siteassignmenthistory_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);

--
-- Constraints for table `site_schedule`
--
ALTER TABLE `site_schedule`
  ADD CONSTRAINT `site_schedule_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);

--
-- Constraints for table `timekeeper`
--
ALTER TABLE `timekeeper`
  ADD CONSTRAINT `timekeeper_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  ADD CONSTRAINT `timekeeper_reports_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `timekeeper_reports_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  ADD CONSTRAINT `timekeeper_reports_ibfk_3` FOREIGN KEY (`Delayed_Category`) REFERENCES `delayedcategory` (`Delayed_Category`);

--
-- Constraints for table `worker`
--
ALTER TABLE `worker`
  ADD CONSTRAINT `worker_ibfk_1` FOREIGN KEY (`WorkerStatusID`) REFERENCES `workerstatus` (`WorkerStatusID`),
  ADD CONSTRAINT `worker_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `workerassignment`
--
ALTER TABLE `workerassignment`
  ADD CONSTRAINT `workerassignment_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  ADD CONSTRAINT `workerassignment_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

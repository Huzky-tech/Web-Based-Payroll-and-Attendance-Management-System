-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 13, 2026 at 03:24 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u892105897_db_652dtUql`
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
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `NotificationID` int(11) NOT NULL,
  `RecipientUserID` int(11) DEFAULT NULL,
  `NotificationType` varchar(50) NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`NotificationID`, `RecipientUserID`, `NotificationType`, `ReferenceID`, `Title`, `Message`, `IsRead`, `CreatedAt`) VALUES
(1019, 1, 'Password Changed', 71, 'Password changed', 'Shandy Pacana changed their password (first-time setup).', 1, '2026-10-08 02:08:28'),
(1020, 1, 'User Activity', 453, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Miguel Santos (ID: 39) with Approved approval status', 1, '2026-10-08 02:15:24'),
(1021, 1, 'Password Changed', 77, 'Password changed', 'Roberto Garcia changed their password (first-time setup).', 1, '2026-10-08 02:16:22'),
(1022, 1, 'User Activity', 457, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Carlo Garcia (ID: 40) with Approved approval status', 1, '2026-10-08 02:17:40'),
(1023, 1, 'User Activity', 462, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Andress Flores (ID: 41) with Approved approval status', 1, '2026-10-08 02:19:37'),
(1024, 1, 'User Activity', 464, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Gabriel Aquino (ID: 42) with Approved approval status', 1, '2026-10-08 02:22:07'),
(1025, 1, 'User Activity', 466, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Mark Anthony Reyes (ID: 43) with Approved approval status', 1, '2026-10-08 02:26:08'),
(1026, 1, 'User Activity', 468, 'Shandy Pacana · Assistant Admin', 'Worker Added: Assistant Admin added worker: Patrick Navarro (ID: 44) with Approved approval status', 1, '2026-10-08 02:29:18'),
(1027, 1, 'Password Changed', 70, 'Password changed', 'Bjorn Rosal changed their password (first-time setup).', 1, '2026-10-08 02:31:04'),
(1028, 70, 'Site Assignment', 11, 'New site assignment', 'You have been assigned to Bakery.', 0, '2026-10-08 02:51:37'),
(1029, NULL, 'timekeeper_report', 4, 'New Timekeeper Report: Daily Accomplishment Report', 'Jodisa Deligero submitted \"Daily Accomplishment Report - 10/08/2026\" for Bakery.', 1, '2026-10-08 03:50:51'),
(1030, 71, 'timekeeper_report', 4, 'New Timekeeper Report: Daily Accomplishment Report', 'Jodisa Deligero submitted \"Daily Accomplishment Report - 10/08/2026\" for Bakery.', 1, '2026-10-08 03:50:51'),
(1031, 70, 'Site Assignment', 12, 'New site assignment', 'You have been assigned to Coc building.', 0, '2026-10-08 07:18:58'),
(1032, 70, 'Site Assignment', 13, 'New site assignment', 'You have been assigned to Carmen Tunnel Road.', 0, '2026-10-08 07:39:49'),
(1033, NULL, 'Payroll Submitted', 7, 'Payroll awaiting approval', 'Bjorn Rosal submitted payroll for Carmen Tunnel Road (2026-10-08 to 2026-10-15, 3 workers).', 1, '2026-10-08 07:41:23'),
(1034, 70, 'Payroll Submitted', 7, 'Payroll submitted', 'Carmen Tunnel Road - Oct 8, 2026 to Oct 15, 2026', 0, '2026-10-08 07:41:23'),
(1035, 70, 'Payroll Approved', 7, 'Payroll approved', 'Carmen Tunnel Road - Oct 8, 2026 to Oct 15, 2026', 0, '2026-10-08 07:43:34'),
(1036, 71, 'Payroll Submitted', 7, 'Payroll awaiting approval', 'Bjorn Rosal submitted payroll for Carmen Tunnel Road (2026-10-08 to 2026-10-15, 3 workers).', 0, '2026-10-08 07:41:23'),
(1037, 1, 'Password Changed', 77, 'Password changed', 'Roberto Garcia changed their password (password reset).', 1, '2026-10-11 22:44:18'),
(1038, NULL, 'timekeeper_report', 5, 'New Timekeeper Report: Daily Accomplishment Report', 'Laurice Caduyac submitted \"Daily Accomplishment Report - 10/13/2026\" for Hotel.', 1, '2026-10-12 16:44:14');

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

--
-- Dumping data for table `approvals`
--

INSERT INTO `approvals` (`ApprovalID`, `WorkerID`, `Action_Type`, `Approval_By`, `Approval_Status`, `Date`) VALUES
(22, 36, 'Employee Creation', 1, 'Approved', '2026-10-08 02:08:28'),
(23, 37, 'Employee Creation', 1, 'Approved', '2026-10-08 02:10:25'),
(24, 38, 'Employee Creation', 1, 'Approved', '2026-10-08 02:12:13'),
(25, 39, 'Employee Creation', 71, 'Approved', '2026-10-08 02:15:24'),
(26, 40, 'Employee Creation', 71, 'Approved', '2026-10-08 02:17:40'),
(27, 41, 'Employee Creation', 71, 'Approved', '2026-10-08 02:19:37'),
(28, 42, 'Employee Creation', 71, 'Approved', '2026-10-08 02:22:07'),
(29, 43, 'Employee Creation', 71, 'Approved', '2026-10-08 02:26:08'),
(30, 44, 'Employee Creation', 71, 'Approved', '2026-10-08 02:29:18'),
(31, 45, 'Employee Creation', 1, 'Approved', '2026-10-08 05:15:24'),
(32, 46, 'Employee Creation', 1, 'Approved', '2026-10-08 05:21:30'),
(33, 47, 'Employee Creation', 1, 'Approved', '2026-10-08 07:17:14'),
(34, 48, 'Employee Creation', 1, 'Approved', '2026-10-11 22:49:42'),
(35, 49, 'Employee Creation', 1, 'Approved', '2026-10-11 22:51:18'),
(36, 50, 'Employee Creation', 1, 'Approved', '2026-10-11 22:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `assistantmanager`
--

CREATE TABLE `assistantmanager` (
  `AssistantManager_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assistantmanager`
--

INSERT INTO `assistantmanager` (`AssistantManager_ID`, `UserID`) VALUES
(4, 71);

-- --------------------------------------------------------

--
-- Table structure for table `assistant_notification_settings`
--

CREATE TABLE `assistant_notification_settings` (
  `UserID` int(11) NOT NULL,
  `in_system_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `payroll_processing` tinyint(1) NOT NULL DEFAULT 1,
  `attendance_issues` tinyint(1) NOT NULL DEFAULT 1,
  `site_assignments` tinyint(1) NOT NULL DEFAULT 1,
  `overtime_requests` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `assistant_notification_settings`
--

INSERT INTO `assistant_notification_settings` (`UserID`, `in_system_notifications`, `payroll_processing`, `attendance_issues`, `site_assignments`, `overtime_requests`, `updated_at`) VALUES
(71, 1, 1, 1, 1, 1, '2026-10-08 02:09:07');

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
  `Lunch_Out` time DEFAULT NULL,
  `Lunch_In` time DEFAULT NULL,
  `Time_Out` time NOT NULL,
  `AttendanceStatus` enum('Present','Late','Absent') NOT NULL,
  `PhotoPath` varchar(500) DEFAULT NULL,
  `Latitude` decimal(10,7) DEFAULT NULL,
  `Longitude` decimal(10,7) DEFAULT NULL,
  `DistanceFromSite` decimal(10,2) DEFAULT NULL,
  `AMTimeInPhoto` varchar(500) DEFAULT NULL,
  `PMTimeInPhoto` varchar(500) DEFAULT NULL,
  `TimeOutPhoto` varchar(500) DEFAULT NULL,
  `IsLate` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `WorkerID`, `SiteID`, `Date`, `Hours_Worked`, `Overtime_Hours`, `Time_In`, `Lunch_Out`, `Lunch_In`, `Time_Out`, `AttendanceStatus`, `PhotoPath`, `Latitude`, `Longitude`, `DistanceFromSite`, `AMTimeInPhoto`, `PMTimeInPhoto`, `TimeOutPhoto`, `IsLate`) VALUES
(36, 37, 16, '2026-10-08', 0.21, 0.00, '11:55:31', '11:57:22', '12:05:15', '12:08:00', 'Present', 'uploads/attendance_photos/attendance_37_time_in_1791431854.jpg', 8.4820660, 124.6379946, 0.00, NULL, NULL, NULL, 1),
(37, 43, 14, '2026-10-08', 0.17, 0.00, '11:55:00', '12:00:00', '12:05:00', '12:10:00', 'Present', 'uploads/attendance_photos/attendance_43_time_in_1791431855.jpg', 8.4820769, 124.6380164, 0.00, NULL, NULL, NULL, 0),
(38, 39, 16, '2026-10-08', 0.21, 0.00, '11:56:00', '12:07:56', '12:05:00', '12:08:48', 'Present', 'uploads/attendance_photos/attendance_39_time_in_1791431856.jpg', 8.4820597, 124.6379927, 0.00, NULL, NULL, NULL, 1),
(39, 47, 15, '2026-10-08', 0.00, 0.00, '15:32:29', '15:35:28', NULL, '00:00:00', 'Present', 'uploads/attendance_photos/attendance_47_time_in_1791444773.jpg', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(40, 36, 13, '2026-10-08', 8.00, 0.00, '10:55:47', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(41, 38, 13, '2026-10-08', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(42, 40, 13, '2026-10-08', 8.00, 0.00, '10:57:45', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(43, 41, 13, '2026-10-08', 8.00, 0.00, '10:56:58', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(44, 42, 13, '2026-10-08', 8.00, 0.00, '10:55:27', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(45, 36, 13, '2026-10-09', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(46, 38, 13, '2026-10-09', 7.50, 0.00, '08:30:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(47, 40, 13, '2026-10-09', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(48, 41, 13, '2026-10-09', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(49, 42, 13, '2026-10-09', 7.75, 0.00, '08:15:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 1),
(50, 36, 13, '2026-10-10', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(51, 38, 13, '2026-10-10', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(52, 40, 13, '2026-10-10', 0.00, 0.00, '00:00:00', NULL, NULL, '00:00:00', 'Absent', NULL, 8.4832650, 124.6353610, 0.00, NULL, NULL, NULL, 0),
(53, 41, 13, '2026-10-10', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(54, 42, 13, '2026-10-10', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', 0),
(55, 36, 13, '2026-10-11', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 0),
(56, 38, 13, '2026-10-11', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 0),
(57, 40, 13, '2026-10-11', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 0),
(58, 41, 13, '2026-10-11', 7.75, 0.00, '08:15:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 1),
(59, 42, 13, '2026-10-11', 8.00, 0.00, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', 0),
(71, 37, 16, '2026-10-12', 9.49, 0.00, '07:00:00', '12:00:00', '13:00:00', '16:29:39', 'Present', 'uploads/attendance_photos/attendance_37_time_in_1791759643.jpg', 8.3615140, 124.8124482, 0.00, NULL, NULL, NULL, 0),
(72, 48, 16, '2026-10-12', 10.01, 0.00, '07:00:00', '12:00:00', '13:00:00', '17:00:22', 'Present', 'uploads/attendance_photos/attendance_48_time_in_1791759665.jpg', 8.3615264, 124.8124447, 0.00, NULL, NULL, NULL, 0),
(73, 39, 16, '2026-10-12', 9.98, 0.00, '07:01:00', '12:00:00', '13:01:00', '16:59:44', 'Present', 'uploads/attendance_photos/attendance_39_time_in_1791759681.jpg', 8.3615264, 124.8124447, 0.00, NULL, NULL, NULL, 0),
(74, 49, 16, '2026-10-12', 10.00, 0.00, '07:01:00', '12:00:00', '13:01:00', '17:00:43', 'Present', 'uploads/attendance_photos/attendance_49_time_in_1791759713.jpg', 8.3615259, 124.8124487, 0.00, NULL, NULL, NULL, 0),
(75, 50, 16, '2026-10-12', 9.98, 0.00, '07:02:00', '12:00:00', '13:01:00', '17:01:00', 'Present', 'uploads/attendance_photos/attendance_50_time_in_1791759751.jpg', 8.3615194, 124.8124531, 0.00, NULL, NULL, NULL, 0),
(76, 41, 13, '2026-10-12', 8.98, 0.00, '08:00:38', '12:00:00', '13:00:36', '16:59:27', 'Present', 'uploads/attendance_photos/attendance_41_time_in_1791763262.jpg', 8.3615170, 124.8124520, 0.00, NULL, NULL, NULL, 0),
(77, 42, 13, '2026-10-12', 8.97, 0.00, '08:00:56', '12:00:00', '13:00:12', '16:58:59', 'Present', 'uploads/attendance_photos/attendance_42_time_in_1791763278.jpg', 8.3615170, 124.8124520, 0.00, NULL, NULL, NULL, 0),
(78, 36, 13, '2026-10-12', 8.97, 0.00, '08:01:14', '12:00:00', '13:00:23', '16:59:10', 'Present', 'uploads/attendance_photos/attendance_36_time_in_1791763296.jpg', 8.3615190, 124.8124539, 0.00, NULL, NULL, NULL, 0),
(79, 40, 13, '2026-10-12', 8.97, 0.00, '08:01:34', '12:00:00', '13:00:50', '16:59:40', 'Present', 'uploads/attendance_photos/attendance_40_time_in_1791763309.jpg', 8.3615190, 124.8124539, 0.00, NULL, NULL, NULL, 0),
(80, 38, 13, '2026-10-12', 8.97, 0.00, '08:01:53', '12:00:00', '13:01:03', '16:59:54', 'Present', 'uploads/attendance_photos/attendance_38_time_in_1791763341.jpg', 8.3615208, 124.8124526, 0.00, NULL, NULL, NULL, 0),
(81, 37, 16, '2026-10-13', 0.00, 0.00, '07:30:22', '12:00:00', NULL, '00:00:00', 'Present', 'uploads/attendance_photos/attendance_37_time_in_1791847874.jpg', 8.3615288, 124.8124431, 0.00, NULL, NULL, NULL, 1),
(82, 39, 16, '2026-10-13', 0.00, 0.00, '07:30:37', '12:00:00', NULL, '00:00:00', 'Present', 'uploads/attendance_photos/attendance_39_time_in_1791847875.jpg', 8.3615257, 124.8124420, 0.00, NULL, NULL, NULL, 1),
(83, 48, 16, '2026-10-13', 0.00, 0.00, '07:34:11', '12:00:00', NULL, '00:00:00', 'Present', 'uploads/attendance_photos/attendance_48_time_in_1791848081.jpg', 8.3615164, 124.8124574, 0.00, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_photo_logs`
--

CREATE TABLE `attendance_photo_logs` (
  `LogID` int(11) NOT NULL,
  `AttendanceID` int(11) DEFAULT NULL,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `TimekeeperID` int(11) NOT NULL,
  `AttendanceType` varchar(30) NOT NULL,
  `EventDate` date NOT NULL,
  `EventTime` time NOT NULL,
  `Latitude` decimal(10,7) DEFAULT NULL,
  `Longitude` decimal(10,7) DEFAULT NULL,
  `DistanceFromSite` decimal(10,2) DEFAULT NULL,
  `PhotoPath` varchar(500) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_photo_logs`
--

INSERT INTO `attendance_photo_logs` (`LogID`, `AttendanceID`, `WorkerID`, `SiteID`, `TimekeeperID`, `AttendanceType`, `EventDate`, `EventTime`, `Latitude`, `Longitude`, `DistanceFromSite`, `PhotoPath`, `CreatedAt`) VALUES
(90, 37, 43, 14, 73, 'Time In', '2026-10-08', '11:55:46', 8.4820769, 124.6380164, NULL, 'uploads/attendance_photos/attendance_43_time_in_1791431855.jpg', '2026-10-08 03:57:35'),
(92, 36, 37, 14, 73, 'Lunch In', '2026-10-08', '12:05:15', 8.4820758, 124.6379897, NULL, 'uploads/attendance_photos/attendance_37_lunch_in_1791432350.jpg', '2026-10-08 04:05:50'),
(93, 37, 43, 14, 73, 'Lunch In', '2026-10-08', '12:05:30', 8.4820701, 124.6379921, NULL, 'uploads/attendance_photos/attendance_43_lunch_in_1791432351.jpg', '2026-10-08 04:05:51'),
(94, 38, 39, 14, 73, 'Lunch In', '2026-10-08', '12:05:43', 8.4820694, 124.6379961, NULL, 'uploads/attendance_photos/attendance_39_lunch_in_1791432352.jpg', '2026-10-08 04:05:52'),
(95, 36, 37, 14, 73, 'Time Out', '2026-10-08', '12:08:00', 8.4820469, 124.6379714, NULL, 'uploads/attendance_photos/attendance_37_time_out_1791432483.jpg', '2026-10-08 04:08:03'),
(96, 39, 47, 15, 88, 'Time In', '2026-10-08', '15:32:29', NULL, NULL, NULL, 'uploads/attendance_photos/attendance_47_time_in_1791444773.jpg', '2026-10-08 07:32:53'),
(98, 41, 38, 13, 72, 'Time In', '2026-10-08', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(102, 45, 36, 13, 72, 'Time In', '2026-10-09', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(103, 46, 38, 13, 72, 'Time In', '2026-10-09', '08:30:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(104, 47, 40, 13, 72, 'Time In', '2026-10-09', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(105, 48, 41, 13, 72, 'Time In', '2026-10-09', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(106, 49, 42, 13, 72, 'Time In', '2026-10-09', '08:15:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(107, 50, 36, 13, 72, 'Time In', '2026-10-10', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(108, 51, 38, 13, 72, 'Time In', '2026-10-10', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(109, 53, 41, 13, 72, 'Time In', '2026-10-10', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(110, 54, 42, 13, 72, 'Time In', '2026-10-10', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg', '2026-10-11 17:26:38'),
(111, 55, 36, 13, 72, 'Time In', '2026-10-11', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(112, 56, 38, 13, 72, 'Time In', '2026-10-11', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(113, 57, 40, 13, 72, 'Time In', '2026-10-11', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(114, 58, 41, 13, 72, 'Time In', '2026-10-11', '08:15:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(115, 59, 42, 13, 72, 'Time In', '2026-10-11', '08:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(116, 40, 36, 13, 72, 'Lunch In', '2026-10-08', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(117, 41, 38, 13, 72, 'Lunch In', '2026-10-08', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(118, 42, 40, 13, 72, 'Lunch In', '2026-10-08', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(119, 43, 41, 13, 72, 'Lunch In', '2026-10-08', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(120, 44, 42, 13, 72, 'Lunch In', '2026-10-08', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(121, 45, 36, 13, 72, 'Lunch In', '2026-10-09', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(122, 46, 38, 13, 72, 'Lunch In', '2026-10-09', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(123, 47, 40, 13, 72, 'Lunch In', '2026-10-09', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(124, 48, 41, 13, 72, 'Lunch In', '2026-10-09', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(125, 49, 42, 13, 72, 'Lunch In', '2026-10-09', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(126, 50, 36, 13, 72, 'Lunch In', '2026-10-10', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(127, 51, 38, 13, 72, 'Lunch In', '2026-10-10', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(128, 53, 41, 13, 72, 'Lunch In', '2026-10-10', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(129, 54, 42, 13, 72, 'Lunch In', '2026-10-10', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg', '2026-10-11 17:26:38'),
(130, 55, 36, 13, 72, 'Lunch In', '2026-10-11', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(131, 56, 38, 13, 72, 'Lunch In', '2026-10-11', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(132, 57, 40, 13, 72, 'Lunch In', '2026-10-11', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(133, 58, 41, 13, 72, 'Lunch In', '2026-10-11', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(134, 59, 42, 13, 72, 'Lunch In', '2026-10-11', '13:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(135, 40, 36, 13, 72, 'Time Out', '2026-10-08', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(136, 41, 38, 13, 72, 'Time Out', '2026-10-08', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(137, 42, 40, 13, 72, 'Time Out', '2026-10-08', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(138, 43, 41, 13, 72, 'Time Out', '2026-10-08', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(139, 44, 42, 13, 72, 'Time Out', '2026-10-08', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(140, 45, 36, 13, 72, 'Time Out', '2026-10-09', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(141, 46, 38, 13, 72, 'Time Out', '2026-10-09', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(142, 47, 40, 13, 72, 'Time Out', '2026-10-09', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(143, 48, 41, 13, 72, 'Time Out', '2026-10-09', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(144, 49, 42, 13, 72, 'Time Out', '2026-10-09', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(145, 50, 36, 13, 72, 'Time Out', '2026-10-10', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(146, 51, 38, 13, 72, 'Time Out', '2026-10-10', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(147, 53, 41, 13, 72, 'Time Out', '2026-10-10', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(148, 54, 42, 13, 72, 'Time Out', '2026-10-10', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg', '2026-10-11 17:26:38'),
(149, 55, 36, 13, 72, 'Time Out', '2026-10-11', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(150, 56, 38, 13, 72, 'Time Out', '2026-10-11', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(151, 57, 40, 13, 72, 'Time Out', '2026-10-11', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(152, 58, 41, 13, 72, 'Time Out', '2026-10-11', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(153, 59, 42, 13, 72, 'Time Out', '2026-10-11', '17:00:00', 8.4832650, 124.6353610, 0.00, 'uploads/attendance_photos/attendance_6_time_out_1788849685.jpg', '2026-10-11 17:26:38'),
(160, 36, 37, 16, 73, 'Time In', '2026-10-08', '11:55:31', 8.4820660, 124.6379946, NULL, 'uploads/attendance_photos/attendance_37_time_in_1791759484.jpg', '2026-10-11 22:58:04'),
(161, 38, 39, 16, 73, 'Time In', '2026-10-08', '11:56:00', 8.4820597, 124.6379927, NULL, 'uploads/attendance_photos/attendance_39_time_in_1791759485.jpg', '2026-10-11 22:58:05'),
(162, 71, 37, 16, 73, 'Time In', '2026-10-12', '07:00:27', 8.3615140, 124.8124482, NULL, 'uploads/attendance_photos/attendance_37_time_in_1791759643.jpg', '2026-10-11 23:00:43'),
(163, 72, 48, 16, 73, 'Time In', '2026-10-12', '07:00:57', 8.3615264, 124.8124447, NULL, 'uploads/attendance_photos/attendance_48_time_in_1791759665.jpg', '2026-10-11 23:01:05'),
(164, 73, 39, 16, 73, 'Time In', '2026-10-12', '07:01:15', 8.3615264, 124.8124447, NULL, 'uploads/attendance_photos/attendance_39_time_in_1791759681.jpg', '2026-10-11 23:01:21'),
(165, 74, 49, 16, 73, 'Time In', '2026-10-12', '07:01:46', 8.3615259, 124.8124487, NULL, 'uploads/attendance_photos/attendance_49_time_in_1791759713.jpg', '2026-10-11 23:01:53'),
(166, 75, 50, 16, 73, 'Time In', '2026-10-12', '07:02:15', 8.3615194, 124.8124531, NULL, 'uploads/attendance_photos/attendance_50_time_in_1791759751.jpg', '2026-10-11 23:02:31'),
(167, 44, 42, 13, 72, 'Time In', '2026-10-08', '10:55:27', 8.4820626, 124.6379775, NULL, 'uploads/attendance_photos/attendance_42_time_in_1791763182.jpg', '2026-10-11 23:59:42'),
(168, 40, 36, 13, 72, 'Time In', '2026-10-08', '10:55:47', 8.4820539, 124.6379626, NULL, 'uploads/attendance_photos/attendance_36_time_in_1791763185.jpg', '2026-10-11 23:59:45'),
(169, 43, 41, 13, 72, 'Time In', '2026-10-08', '10:56:58', 8.4820437, 124.6379378, NULL, 'uploads/attendance_photos/attendance_41_time_in_1791763188.jpg', '2026-10-11 23:59:48'),
(170, 42, 40, 13, 72, 'Time In', '2026-10-08', '10:57:45', 8.4820555, 124.6379526, NULL, 'uploads/attendance_photos/attendance_40_time_in_1791763191.jpg', '2026-10-11 23:59:51'),
(171, 76, 41, 13, 72, 'Time In', '2026-10-12', '08:00:38', 8.3615170, 124.8124520, NULL, 'uploads/attendance_photos/attendance_41_time_in_1791763262.jpg', '2026-10-12 00:01:02'),
(172, 77, 42, 13, 72, 'Time In', '2026-10-12', '08:00:56', 8.3615170, 124.8124520, NULL, 'uploads/attendance_photos/attendance_42_time_in_1791763278.jpg', '2026-10-12 00:01:18'),
(173, 78, 36, 13, 72, 'Time In', '2026-10-12', '08:01:14', 8.3615190, 124.8124539, NULL, 'uploads/attendance_photos/attendance_36_time_in_1791763296.jpg', '2026-10-12 00:01:36'),
(174, 79, 40, 13, 72, 'Time In', '2026-10-12', '08:01:34', 8.3615190, 124.8124539, NULL, 'uploads/attendance_photos/attendance_40_time_in_1791763309.jpg', '2026-10-12 00:01:49'),
(175, 80, 38, 13, 72, 'Time In', '2026-10-12', '08:01:53', 8.3615208, 124.8124526, NULL, 'uploads/attendance_photos/attendance_38_time_in_1791763341.jpg', '2026-10-12 00:02:21'),
(176, 77, 42, 13, 72, 'Lunch In', '2026-10-12', '13:00:12', 8.4787084, 124.6268819, NULL, 'uploads/attendance_photos/attendance_42_lunch_in_1791781214.jpg', '2026-10-12 05:00:14'),
(177, 78, 36, 13, 72, 'Lunch In', '2026-10-12', '13:00:23', 8.4787087, 124.6268758, NULL, 'uploads/attendance_photos/attendance_36_lunch_in_1791781226.jpg', '2026-10-12 05:00:27'),
(178, 76, 41, 13, 72, 'Lunch In', '2026-10-12', '13:00:36', 8.4787038, 124.6268844, NULL, 'uploads/attendance_photos/attendance_41_lunch_in_1791781237.jpg', '2026-10-12 05:00:38'),
(179, 79, 40, 13, 72, 'Lunch In', '2026-10-12', '13:00:50', 8.4787020, 124.6268863, NULL, 'uploads/attendance_photos/attendance_40_lunch_in_1791781253.jpg', '2026-10-12 05:00:53'),
(180, 80, 38, 13, 72, 'Lunch In', '2026-10-12', '13:01:03', 8.4787036, 124.6268901, NULL, 'uploads/attendance_photos/attendance_38_lunch_in_1791781267.jpg', '2026-10-12 05:01:07'),
(181, 38, 39, 16, 73, 'Time Out', '2026-10-08', '12:08:48', 8.4820564, 124.6379670, NULL, 'uploads/attendance_photos/attendance_39_time_out_1791781705.jpg', '2026-10-12 05:08:25'),
(182, 71, 37, 16, 73, 'Lunch In', '2026-10-12', '13:00:34', 8.3615022, 124.8124462, NULL, 'uploads/attendance_photos/attendance_37_lunch_in_1791781720.jpg', '2026-10-12 05:08:40'),
(183, 72, 48, 16, 73, 'Lunch In', '2026-10-12', '13:00:53', 8.3615049, 124.8124439, NULL, 'uploads/attendance_photos/attendance_48_lunch_in_1791781720.jpg', '2026-10-12 05:08:40'),
(184, 73, 39, 16, 73, 'Lunch In', '2026-10-12', '13:01:11', 8.3615125, 124.8124505, NULL, 'uploads/attendance_photos/attendance_39_lunch_in_1791781720.jpg', '2026-10-12 05:08:40'),
(185, 74, 49, 16, 73, 'Lunch In', '2026-10-12', '13:01:33', 8.3615132, 124.8124460, NULL, 'uploads/attendance_photos/attendance_49_lunch_in_1791781721.jpg', '2026-10-12 05:08:41'),
(186, 75, 50, 16, 73, 'Lunch In', '2026-10-12', '13:01:50', 8.3615128, 124.8124508, NULL, 'uploads/attendance_photos/attendance_50_lunch_in_1791781721.jpg', '2026-10-12 05:08:41'),
(187, 71, 37, 16, 73, 'Time Out', '2026-10-12', '16:29:39', 8.3615059, 124.8124457, NULL, 'uploads/attendance_photos/attendance_37_time_out_1791793813.jpg', '2026-10-12 08:30:13'),
(188, 77, 42, 13, 72, 'Time Out', '2026-10-12', '16:58:59', 8.4787056, 124.6268896, NULL, 'uploads/attendance_photos/attendance_42_time_out_1791795541.jpg', '2026-10-12 08:59:01'),
(189, 78, 36, 13, 72, 'Time Out', '2026-10-12', '16:59:10', 8.4787072, 124.6268909, NULL, 'uploads/attendance_photos/attendance_36_time_out_1791795552.jpg', '2026-10-12 08:59:12'),
(190, 76, 41, 13, 72, 'Time Out', '2026-10-12', '16:59:27', 8.4786957, 124.6268903, NULL, 'uploads/attendance_photos/attendance_41_time_out_1791795576.jpg', '2026-10-12 08:59:36'),
(191, 79, 40, 13, 72, 'Time Out', '2026-10-12', '16:59:40', 8.4787024, 124.6268910, NULL, 'uploads/attendance_photos/attendance_40_time_out_1791795582.jpg', '2026-10-12 08:59:42'),
(192, 73, 39, 16, 73, 'Time Out', '2026-10-12', '16:59:44', 8.3615145, 124.8124540, NULL, 'uploads/attendance_photos/attendance_39_time_out_1791795589.jpg', '2026-10-12 08:59:49'),
(193, 80, 38, 13, 72, 'Time Out', '2026-10-12', '16:59:54', 8.4786998, 124.6268810, NULL, 'uploads/attendance_photos/attendance_38_time_out_1791795596.jpg', '2026-10-12 08:59:56'),
(194, 72, 48, 16, 73, 'Time Out', '2026-10-12', '17:00:22', 8.3615271, 124.8124459, NULL, 'uploads/attendance_photos/attendance_48_time_out_1791795641.jpg', '2026-10-12 09:00:41'),
(195, 74, 49, 16, 73, 'Time Out', '2026-10-12', '17:00:43', 8.3615042, 124.8124441, NULL, 'uploads/attendance_photos/attendance_49_time_out_1791795651.jpg', '2026-10-12 09:00:51'),
(196, 75, 50, 16, 73, 'Time Out', '2026-10-12', '17:01:00', 8.3615151, 124.8124518, NULL, 'uploads/attendance_photos/attendance_50_time_out_1791795672.jpg', '2026-10-12 09:01:12'),
(197, 81, 37, 16, 73, 'Time In', '2026-10-13', '07:30:22', 8.3615288, 124.8124431, NULL, 'uploads/attendance_photos/attendance_37_time_in_1791847874.jpg', '2026-10-12 23:31:14'),
(198, 82, 39, 16, 73, 'Time In', '2026-10-13', '07:30:37', 8.3615257, 124.8124420, NULL, 'uploads/attendance_photos/attendance_39_time_in_1791847875.jpg', '2026-10-12 23:31:15'),
(199, 83, 48, 16, 73, 'Time In', '2026-10-13', '07:34:11', 8.3615164, 124.8124574, NULL, 'uploads/attendance_photos/attendance_48_time_in_1791848081.jpg', '2026-10-12 23:34:41');

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
(436, 1, 'User Login', 'Successful login as Admin', '2026-10-08 02:02:55'),
(437, 1, 'User Login', 'Successful login as Admin', '2026-10-08 02:03:31'),
(438, 1, 'User Account Created', 'Created Payroll Staff account for Bjorn Rosal (bjla.rosal.coc@phinmaed.com).', '2026-10-08 02:04:03'),
(439, 1, 'User Account Created', 'Created Assistant Admin account for Shandy Pacana (shandypacana456@gmail.com).', '2026-10-08 02:04:11'),
(440, 1, 'User Account Created', 'Created Timekeeper account for Jodisa Deligero (joca.deligero.coc@phinmaed.com).', '2026-10-08 02:05:40'),
(441, 1, 'User Account Created', 'Created Timekeeper account for Laurice Caduyac (lacl.caduyac.coc@phinmaed.com).', '2026-10-08 02:06:27'),
(442, 1, 'User Account Created', 'Created Manager account for Johnny Calunsag (iche.calunsag12.coc@phinmaed.com).', '2026-10-08 02:07:18'),
(443, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Skilled', '2026-10-08 02:08:28'),
(444, 1, 'Worker Added', 'Admin added worker: Juan De La Cruz (ID: 36) with Approved approval status', '2026-10-08 02:08:28'),
(445, 71, 'Password Changed', 'Completed first-time password setup.', '2026-10-08 02:08:28'),
(446, 71, 'User Login', 'Successful login as Assistant Admin after first-time password setup', '2026-10-08 02:08:28'),
(447, 1, 'User Account Created', 'Created HR account for Mark Logan (djodisamae@gmail.com).', '2026-10-08 02:09:03'),
(448, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-08 02:10:25'),
(449, 1, 'Worker Added', 'Admin added worker: Roberto Garcia (ID: 37) with Approved approval status', '2026-10-08 02:10:25'),
(450, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Watchman', '2026-10-08 02:12:13'),
(451, 1, 'Worker Added', 'Admin added worker: Marcelito Ramos (ID: 38) with Approved approval status', '2026-10-08 02:12:13'),
(452, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Skilled', '2026-10-08 02:15:24'),
(453, 71, 'Worker Added', 'Assistant Admin added worker: Miguel Santos (ID: 39) with Approved approval status', '2026-10-08 02:15:24'),
(454, 77, 'Password Changed', 'Completed first-time password setup.', '2026-10-08 02:16:22'),
(455, 77, 'User Login', 'Successful login as Worker after first-time password setup', '2026-10-08 02:16:22'),
(456, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Driver', '2026-10-08 02:17:40'),
(457, 71, 'Worker Added', 'Assistant Admin added worker: Carlo Garcia (ID: 40) with Approved approval status', '2026-10-08 02:17:40'),
(458, 1, 'User Login', 'Successful login as Admin', '2026-10-08 02:18:10'),
(459, 1, 'Site Created', 'Created site: Bakery', '2026-10-08 02:18:36'),
(460, 1, 'User Account Created', 'Created Manager account for Micheal Afton (michael@gmail.com).', '2026-10-08 02:18:57'),
(461, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-08 02:19:37'),
(462, 71, 'Worker Added', 'Assistant Admin added worker: Andress Flores (ID: 41) with Approved approval status', '2026-10-08 02:19:37'),
(463, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-08 02:22:07'),
(464, 71, 'Worker Added', 'Assistant Admin added worker: Gabriel Aquino (ID: 42) with Approved approval status', '2026-10-08 02:22:07'),
(465, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-08 02:26:08'),
(466, 71, 'Worker Added', 'Assistant Admin added worker: Mark Anthony Reyes (ID: 43) with Approved approval status', '2026-10-08 02:26:08'),
(467, 71, 'Employee Position Note', 'Preferred position captured during employee creation: Operator', '2026-10-08 02:29:18'),
(468, 71, 'Worker Added', 'Assistant Admin added worker: Patrick Navarro (ID: 44) with Approved approval status', '2026-10-08 02:29:18'),
(469, 70, 'Password Changed', 'Completed first-time password setup.', '2026-10-08 02:31:04'),
(470, 70, 'User Login', 'Successful login as Payroll Staff after first-time password setup', '2026-10-08 02:31:04'),
(471, 1, 'User Login', 'Successful login as Admin', '2026-10-08 02:33:20'),
(472, 71, 'User Login', 'Successful login as Assistant Admin', '2026-10-08 02:40:23'),
(473, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Jodisa Deligero to Bakery', '2026-10-08 02:48:40'),
(474, 1, 'Assign Worker to Site', 'Admin assigned Andress Flores to Bakery', '2026-10-08 02:48:41'),
(475, 1, 'Assign Worker to Site', 'Admin assigned Carlo Garcia to Bakery', '2026-10-08 02:48:41'),
(476, 1, 'Assign Worker to Site', 'Admin assigned Gabriel Aquino to Bakery', '2026-10-08 02:48:41'),
(477, 71, 'Assign Worker to Site', 'Assistant Admin assigned Juan De La Cruz to Bakery', '2026-10-08 02:49:10'),
(478, 71, 'Assign Worker to Site', 'Assistant Admin assigned Marcelito Ramos to Bakery', '2026-10-08 02:49:11'),
(479, 70, 'Site Assignment Removed', 'Payroll Staff removed site \'Bakery\' from worker \'Gabriel Aquino\'', '2026-10-08 02:49:37'),
(480, 70, 'Site Assignment Removed', 'Payroll Staff removed site \'Bakery\' from worker \'Andress Flores\'', '2026-10-08 02:49:37'),
(481, 70, 'Site Assignment Removed', 'Payroll Staff removed site \'Bakery\' from worker \'Carlo Garcia\'', '2026-10-08 02:49:37'),
(482, 70, 'Site Assignment Removed', 'Payroll Staff removed site \'Bakery\' from worker \'Marcelito Ramos\'', '2026-10-08 02:49:45'),
(483, 70, 'Site Assignment Removed', 'Payroll Staff removed site \'Bakery\' from worker \'Juan De La Cruz\'', '2026-10-08 02:49:45'),
(484, 1, 'User Password Changed', 'Changed password for Jodisa Deligero', '2026-10-08 02:50:07'),
(485, 70, 'Assign Worker to Site', 'Payroll Staff assigned Andress Flores to Bakery', '2026-10-08 02:50:08'),
(486, 70, 'Assign Worker to Site', 'Payroll Staff assigned Carlo Garcia to Bakery', '2026-10-08 02:50:08'),
(487, 70, 'Assign Worker to Site', 'Payroll Staff assigned Gabriel Aquino to Bakery', '2026-10-08 02:50:09'),
(488, 70, 'Assign Worker to Site', 'Payroll Staff assigned Marcelito Ramos to Bakery', '2026-10-08 02:50:09'),
(489, 70, 'Assign Worker to Site', 'Payroll Staff assigned Juan De La Cruz to Bakery', '2026-10-08 02:50:09'),
(490, 71, 'Site Assigned to Staff', 'Assigned site \'Bakery\' to staff \'Bjorn Rosal\'', '2026-10-08 02:51:37'),
(491, 1, 'Site Updated', 'Admin updated site: Bakery (ID: 13)', '2026-10-08 02:53:31'),
(492, 1, 'User Login', 'Successful login as Admin', '2026-10-08 03:07:20'),
(493, 1, 'User Login', 'Successful login as Admin', '2026-10-08 03:20:20'),
(494, 1, 'User Login', 'Successful login as Admin', '2026-10-08 03:20:58'),
(495, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"10:55:00\",\"Lunch_Out\":\"11:00:00\",\"Lunch_In\":\"11:07:00\",\"Time_Out\":\"11:10:00\",\"Mark_Absent\":false,\"Hours_Worked\":0.13,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-08 03:22:06'),
(496, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":null,\"Lunch_Out\":null,\"Lunch_In\":null,\"Time_Out\":null,\"Mark_Absent\":true,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Absent\"}', '2026-10-08 03:22:17'),
(497, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":null,\"Lunch_Out\":null,\"Lunch_In\":null,\"Time_Out\":null,\"Mark_Absent\":true,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Absent\"}', '2026-10-08 03:22:29'),
(498, 71, 'Overtime Request Approved', 'Assistant Admin Shandy Pacana approved Overtime Request #8', '2026-10-08 03:27:21'),
(499, 71, 'User Login', 'Successful login as Assistant Admin', '2026-10-08 03:35:53'),
(500, 71, 'Site Created', 'Created site: Carmen Tunnel Road', '2026-10-08 03:38:43'),
(501, 1, 'Employee Archived', 'Archived employee: Patrick Navarro (ID: 44)', '2026-10-08 03:39:43'),
(502, 71, 'Site Updated', 'Assistant Admin updated site: Carmen Tunnel Road (ID: 14)', '2026-10-08 03:52:30'),
(503, 1, 'Account Restored', 'Restored deactivated account: Laurice Caduyac', '2026-10-08 03:53:08'),
(504, 1, 'User Password Changed', 'Changed password for Laurice Caduyac', '2026-10-08 03:53:28'),
(505, 71, 'Site Timekeeper Assigned', 'Assistant Admin assigned Timekeeper Laurice Caduyac to Carmen Tunnel Road', '2026-10-08 03:53:31'),
(506, 71, 'Assign Worker to Site', 'Assistant Admin assigned Mark Anthony Reyes to Carmen Tunnel Road', '2026-10-08 03:53:31'),
(507, 71, 'Assign Worker to Site', 'Assistant Admin assigned Miguel Santos to Carmen Tunnel Road', '2026-10-08 03:53:31'),
(508, 71, 'Assign Worker to Site', 'Assistant Admin assigned Roberto Garcia to Carmen Tunnel Road', '2026-10-08 03:53:32'),
(509, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"11:55:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"12:05:00\",\"Time_Out\":\"12:10:00\",\"Mark_Absent\":false,\"Hours_Worked\":0.17,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-08 04:13:45'),
(510, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"11:56:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"12:05:00\",\"Time_Out\":\"12:10:00\",\"Mark_Absent\":false,\"Hours_Worked\":0.15,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-08 04:14:00'),
(511, 1, 'User Login', 'Successful login as Admin', '2026-10-08 04:59:03'),
(512, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-08 05:15:24'),
(513, 1, 'Worker Added', 'Admin added worker: James Go (ID: 45) with Approved approval status', '2026-10-08 05:15:24'),
(514, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Operator', '2026-10-08 05:21:30'),
(515, 1, 'Worker Added', 'Admin added worker: Sam Smith (ID: 46) with Approved approval status', '2026-10-08 05:21:30'),
(516, 1, 'User Account Created', 'Created Timekeeper account for Alicia Bautista (bautista@gmail.com).', '2026-10-08 05:22:41'),
(517, 1, 'User Password Changed', 'Changed password for Alicia Bautista', '2026-10-08 05:23:24'),
(518, 1, 'User Login', 'Successful login as Admin', '2026-10-08 06:33:57'),
(519, 1, 'User Login', 'Successful login as Admin', '2026-10-08 07:11:20'),
(520, 1, 'Site Created', 'Created site: Coc building', '2026-10-08 07:14:38'),
(521, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Lead Man', '2026-10-08 07:17:14'),
(522, 1, 'Worker Added', 'Admin added worker: Ralfie Calunsag (ID: 47) with Approved approval status', '2026-10-08 07:17:14'),
(523, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Alicia Bautista to Coc building', '2026-10-08 07:18:16'),
(524, 1, 'Assign Worker to Site', 'Admin assigned Sam Smith to Coc building', '2026-10-08 07:18:16'),
(525, 1, 'Assign Worker to Site', 'Admin assigned Ralfie Calunsag to Coc building', '2026-10-08 07:18:17'),
(526, 1, 'Assign Worker to Site', 'Admin assigned James Go to Coc building', '2026-10-08 07:18:17'),
(527, 1, 'Site Assignment Removed', 'Removed site \'Bakery\' from staff \'Bjorn Rosal\'', '2026-10-08 07:18:48'),
(528, 1, 'Site Assigned to Staff', 'Assigned site \'Coc building\' to staff \'Bjorn Rosal\'', '2026-10-08 07:18:58'),
(529, 1, 'Site Updated', 'Admin updated site: Coc building (ID: 15)', '2026-10-08 07:27:49'),
(530, 70, 'User Login', 'Successful login as Payroll Staff', '2026-10-08 07:39:29'),
(531, 1, 'Site Assignment Removed', 'Removed site \'Coc building\' from staff \'Bjorn Rosal\'', '2026-10-08 07:39:46'),
(532, 1, 'Site Assigned to Staff', 'Assigned site \'Carmen Tunnel Road\' to staff \'Bjorn Rosal\'', '2026-10-08 07:39:49'),
(533, 1, 'Payroll Approved', 'Admin Meejay Calunsag approved payroll record #7 for site #14', '2026-10-08 07:43:34'),
(534, 71, 'User Login', 'Successful login as Assistant Admin', '2026-10-08 07:45:11'),
(535, 77, 'User Login', 'Successful login as Worker', '2026-10-08 07:48:27'),
(536, 1, 'User Login', 'Successful login as Admin', '2026-10-08 07:57:07'),
(537, 1, 'User Login', 'Successful login as Admin', '2026-10-09 03:24:39'),
(538, 1, 'User Login', 'Successful login as Admin', '2026-10-10 18:19:29'),
(539, 1, 'User Login', 'Successful login as Admin', '2026-10-11 05:52:16'),
(540, 1, 'User Login', 'Successful login as Admin', '2026-10-11 17:31:50'),
(541, 1, 'User Login', 'Successful login as Admin', '2026-10-11 22:42:45'),
(542, 77, 'Password Changed', 'Reset password using verified email recovery.', '2026-10-11 22:44:18'),
(543, 77, 'User Login', 'Successful login as Worker', '2026-10-11 22:44:29'),
(544, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Operator', '2026-10-11 22:49:42'),
(545, 1, 'Worker Added', 'Admin added worker: Ythann Kei (ID: 48) with Approved approval status', '2026-10-11 22:49:42'),
(546, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-11 22:51:18'),
(547, 1, 'Worker Added', 'Admin added worker: Arian Labis (ID: 49) with Approved approval status', '2026-10-11 22:51:18'),
(548, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-10-11 22:52:46'),
(549, 1, 'Worker Added', 'Admin added worker: Bernard Mata (ID: 50) with Approved approval status', '2026-10-11 22:52:46'),
(550, 1, 'Site Archived', 'Admin User archived site: Carmen Tunnel Road; unassigned 3 worker(s)', '2026-10-11 22:53:21'),
(551, 1, 'Site Created', 'Created site: Hotel', '2026-10-11 22:54:07'),
(552, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Laurice Caduyac to Hotel', '2026-10-11 22:54:31'),
(553, 1, 'Assign Worker to Site', 'Admin assigned Arian Labis to Hotel', '2026-10-11 22:54:31'),
(554, 1, 'Assign Worker to Site', 'Admin assigned Bernard Mata to Hotel', '2026-10-11 22:54:32'),
(555, 1, 'Assign Worker to Site', 'Admin assigned Ythann Kei to Hotel', '2026-10-11 22:54:32'),
(556, 1, 'Assign Worker to Site', 'Admin assigned Roberto Garcia to Hotel', '2026-10-11 22:54:32'),
(557, 1, 'Assign Worker to Site', 'Admin assigned Miguel Santos to Hotel', '2026-10-11 22:54:32'),
(558, 1, 'User Login', 'Successful login as Admin', '2026-10-11 23:16:03'),
(559, 1, 'Employee Updated', 'Admin updated employee: Marcelito Ramos (ID: 38)', '2026-10-11 23:20:03'),
(560, 1, 'Employee Updated', 'Admin updated employee: Juan De La Cruz (ID: 36)', '2026-10-11 23:20:46'),
(561, 1, 'User Login', 'Successful login as Admin', '2026-10-12 03:58:22'),
(562, 71, 'User Login', 'Successful login as Assistant Admin', '2026-10-12 04:07:46'),
(563, 1, 'User Login', 'Successful login as Admin', '2026-10-12 04:42:58'),
(564, 1, 'User Login', 'Successful login as Admin', '2026-10-12 05:02:25'),
(565, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"07:00:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"13:00:00\",\"Time_Out\":null,\"Mark_Absent\":false,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-12 05:12:36'),
(566, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"07:00:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"13:00:00\",\"Time_Out\":null,\"Mark_Absent\":false,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-12 05:13:24'),
(567, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"07:01:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"13:01:00\",\"Time_Out\":null,\"Mark_Absent\":false,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-12 05:13:32'),
(568, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"07:02:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"13:01:00\",\"Time_Out\":null,\"Mark_Absent\":false,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-12 05:13:39'),
(569, 1, 'Update Attendance', 'Updated attendance: {\"Time_In\":\"07:01:00\",\"Lunch_Out\":\"12:00:00\",\"Lunch_In\":\"13:01:00\",\"Time_Out\":null,\"Mark_Absent\":false,\"Hours_Worked\":0,\"Overtime_Hours\":0,\"AttendanceStatus\":\"Present\"}', '2026-10-12 05:13:45'),
(570, 1, 'User Login', 'Successful login as Admin', '2026-10-12 08:30:38'),
(571, 71, 'User Login', 'Successful login as Assistant Admin', '2026-10-12 08:50:40'),
(572, 1, 'Employee Updated', 'Admin updated employee: Ythann Kei (ID: 48)', '2026-10-12 09:03:08'),
(573, 1, 'Employee Updated', 'Admin updated employee: Ythann Kei (ID: 48)', '2026-10-12 09:03:55'),
(574, 1, 'User Login', 'Successful login as Admin', '2026-10-12 10:41:26'),
(575, 1, 'User Login', 'Successful login as Admin', '2026-10-12 11:12:09'),
(576, 1, 'User Login', 'Successful login as Admin', '2026-10-12 11:16:18'),
(577, 77, 'User Login', 'Successful login as Worker', '2026-10-12 11:17:46'),
(578, 1, 'User Login', 'Successful login as Admin', '2026-10-12 23:28:17'),
(579, 1, 'User Login', 'Successful login as Admin', '2026-10-13 00:58:53'),
(580, 1, 'User Login', 'Successful login as Admin', '2026-10-13 03:10:39'),
(581, 1, 'User Login', 'Successful login as Admin', '2026-10-13 03:18:55'),
(582, 1, 'User Login', 'Successful login as Admin', '2026-10-13 03:22:52');

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
  `logo_path` varchar(255) DEFAULT NULL,
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `tax_id`, `phone`, `email`, `address`, `logo_path`, `ConfigurationID`) VALUES
(1, 'Philippians CDO Construction Company', '123-45-6789', '(555) 123-4567', 'info@philippianscdo.com', '123 Main Street, CDO City', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `configuration_registry`
--

CREATE TABLE `configuration_registry` (
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL,
  `ConfigurationName` varchar(100) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `configuration_registry`
--

INSERT INTO `configuration_registry` (`ConfigurationID`, `ConfigurationName`, `CreatedAt`) VALUES
(1, 'Primary System Configuration', '2026-09-21 07:50:16');

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
-- Table structure for table `employee_position_catalog`
--

CREATE TABLE `employee_position_catalog` (
  `id` int(10) UNSIGNED NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `salary_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `weekly_rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_position_catalog`
--

INSERT INTO `employee_position_catalog` (`id`, `position_name`, `hourly_rate`, `salary_rate`, `created_at`, `weekly_rate`) VALUES
(2, 'Laborer', 62.50, 500.00, '2026-08-13 22:43:25', 500.00),
(3662, 'Roofer', 62.50, 500.00, '2026-09-21 20:54:33', 500.00),
(4400, 'Watchman', 56.55, 450.00, '2026-09-30 02:38:18', 450.00),
(4764, 'Lead Man', 87.50, 700.00, '2026-09-30 04:47:12', 700.00),
(4820, 'Driver', 81.25, 650.00, '2026-09-30 04:49:22', 650.00),
(4843, 'Operator', 81.25, 650.00, '2026-09-30 04:52:09', 650.00),
(5120, 'Skilled', 68.75, 2750.00, '2026-09-30 05:33:02', 550.00);

-- --------------------------------------------------------

--
-- Table structure for table `hr`
--

CREATE TABLE `hr` (
  `HR_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr`
--

INSERT INTO `hr` (`HR_ID`, `UserID`) VALUES
(3, 76);

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
-- Table structure for table `managers`
--

CREATE TABLE `managers` (
  `ManagerID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `managers`
--

INSERT INTO `managers` (`ManagerID`, `UserID`) VALUES
(1, 24),
(2, 51),
(3, 74),
(4, 81);

-- --------------------------------------------------------

--
-- Table structure for table `notification_read_cutoffs`
--

CREATE TABLE `notification_read_cutoffs` (
  `RecipientUserID` int(11) NOT NULL,
  `ReadThrough` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `notification_read_cutoffs`
--

INSERT INTO `notification_read_cutoffs` (`RecipientUserID`, `ReadThrough`) VALUES
(1, '2026-10-12 08:50:25'),
(40, '2026-10-03 14:59:07');

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
  `email_digest_frequency` varchar(20) DEFAULT 'Daily',
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `email_notifications`, `in_system_notifications`, `leave_request_updates`, `payroll_processing`, `attendance_issues`, `system_updates`, `daily_reports`, `email_digest_frequency`, `ConfigurationID`) VALUES
(1, 1, 1, 1, 1, 0, 0, 0, 'Daily', 1);

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `OvertimeID` int(11) NOT NULL,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `RequestDate` date NOT NULL,
  `OvertimeType` varchar(50) NOT NULL,
  `OvertimeStart` time NOT NULL,
  `OvertimeEnd` time NOT NULL,
  `TotalHours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `Reason` text NOT NULL,
  `SubmittedBy` int(11) NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Pending',
  `ApprovedBy` int(11) DEFAULT NULL,
  `ApprovedDate` datetime DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`OvertimeID`, `WorkerID`, `SiteID`, `RequestDate`, `OvertimeType`, `OvertimeStart`, `OvertimeEnd`, `TotalHours`, `Reason`, `SubmittedBy`, `Status`, `ApprovedBy`, `ApprovedDate`, `CreatedAt`) VALUES
(1, 3, 1, '2026-10-01', 'Emergency Overtime', '18:00:00', '20:00:00', 2.00, 'Submitted via mobile app', 25, 'Approved', 1, '2026-09-30 07:28:16', '2026-09-30 06:37:40'),
(2, 14, 1, '2026-10-01', 'Regular Overtime', '17:00:00', '18:00:00', 1.00, 'Submitted via mobile app', 39, 'Approved', 1, '2026-10-01 06:54:33', '2026-10-01 06:54:18'),
(3, 1, 4, '2026-10-02', 'Regular Overtime', '17:00:00', '19:00:00', 2.00, 'Submitted via mobile app', 25, 'Approved', 1, '2026-10-05 13:29:06', '2026-10-01 07:10:58'),
(4, 3, 2, '2026-10-02', 'Regular Overtime', '06:00:00', '07:00:00', 1.00, 'Submitted via mobile app', 39, 'Approved', 1, '2026-10-01 21:56:22', '2026-10-01 21:55:03'),
(5, 29, 10, '2026-10-07', 'Regular Overtime', '21:20:00', '23:00:00', 1.67, 'Submitted via mobile app', 65, 'Approved', 56, '2026-10-07 13:20:04', '2026-10-07 13:18:22'),
(6, 23, 10, '2026-10-07', 'Regular Overtime', '21:20:00', '23:00:00', 1.67, 'Submitted via mobile app', 65, 'Approved', 56, '2026-10-07 13:20:22', '2026-10-07 13:19:27'),
(7, 30, 11, '2026-10-08', 'Regular Overtime', '17:00:00', '19:00:00', 2.00, 'Submitted via mobile app', 68, 'Pending', NULL, NULL, '2026-10-08 01:40:49'),
(8, 42, 13, '2026-10-08', 'Regular Overtime', '12:00:00', '13:00:00', 1.00, 'Submitted via mobile app', 72, 'Approved', 71, '2026-10-08 03:27:21', '2026-10-08 03:27:07');

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

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`PayrollID`, `WorkerID`, `Pay_Period_Start`, `Pay_Period_End`, `Gross_Pay`, `Total_Deductions`, `Net_Pay`, `Date_Processed`) VALUES
(14, 42, '2026-10-08', '2026-10-15', 15.63, 5.00, 10.63, '2026-10-08'),
(15, 36, '2026-10-08', '2026-10-15', 8.94, 5.00, 3.94, '2026-10-08'),
(16, 41, '2026-10-08', '2026-10-15', 0.00, 0.00, 0.00, '2026-10-08'),
(17, 40, '2026-10-08', '2026-10-15', 0.00, 5.00, -5.00, '2026-10-08'),
(18, 38, '2026-10-08', '2026-10-15', 0.00, 5.00, -5.00, '2026-10-08'),
(19, 37, '2026-10-08', '2026-10-15', 13.13, 0.00, 13.13, '2026-10-08'),
(20, 43, '2026-10-08', '2026-10-15', 10.63, 5.00, 5.63, '2026-10-08'),
(21, 39, '2026-10-08', '2026-10-15', 10.31, 0.00, 10.31, '2026-10-08');

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

--
-- Dumping data for table `payrollstaff`
--

INSERT INTO `payrollstaff` (`PayrollStaff_ID`, `UserID`) VALUES
(4, 70);

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

--
-- Dumping data for table `payrollstaffassignment`
--

INSERT INTO `payrollstaffassignment` (`staffAssignID`, `PayrollStaff_ID`, `SiteID`, `Created_at`, `Updated_at`) VALUES
(13, 4, 14, '2026-10-08 07:39:49', NULL);

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
  `Status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `worker_count` int(11) NOT NULL DEFAULT 0,
  `regular_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`Payroll_RecordsID`, `PayrollID`, `SiteID`, `Period_start`, `Period_end`, `Total_gross_pay`, `Total_deductions`, `Total_net_pay`, `Status`, `submitted_by`, `submitted_at`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `worker_count`, `regular_hours`, `overtime_hours`) VALUES
(1, 1, 1, '2026-09-30', '2026-10-03', 0.00, 0.00, 0.00, 'Rejected', 21, '2026-09-30 05:38:48', NULL, NULL, 22, '2026-09-30 05:39:05', 'shibal', 1, 0.00, 0.00),
(2, 2, 1, '2026-09-30', '2026-10-03', 348.75, 1.25, 347.50, 'Approved', 21, '2026-09-30 06:44:41', 22, '2026-09-30 06:45:15', NULL, NULL, NULL, 3, 0.29, 2.00),
(3, 4, 7, '2026-10-01', '2026-10-03', 0.00, 0.00, 0.00, 'Approved', 40, '2026-10-03 16:47:05', 22, '2026-10-05 11:27:57', NULL, NULL, NULL, 3, 0.00, 0.00),
(4, 7, 10, '2026-10-07', '2026-10-10', 443.21, 9.62, 433.59, 'Approved', 56, '2026-10-07 14:29:27', 57, '2026-10-07 14:35:13', NULL, NULL, NULL, 4, 1.99, 3.34),
(5, 11, 11, '2026-10-08', '2026-10-15', 93.13, 0.00, 93.13, 'Approved', 69, '2026-10-07 20:54:05', 1, '2026-10-07 20:54:33', NULL, NULL, NULL, 3, 1.49, 0.00),
(6, 14, 13, '2026-10-08', '2026-10-15', 24.57, 20.00, 4.57, 'Approved', 1, '2026-10-08 03:22:49', 1, '2026-10-08 03:22:49', NULL, NULL, NULL, 5, 0.38, 0.00),
(7, 19, 14, '2026-10-08', '2026-10-15', 34.07, 5.00, 29.07, 'Approved', 70, '2026-10-08 07:41:23', 1, '2026-10-08 07:43:34', NULL, NULL, NULL, 3, 0.53, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL,
  `pay_periods` varchar(50) DEFAULT 'Weekly',
  `sss_rate` decimal(5,2) DEFAULT 0.00,
  `philhealth_rate` decimal(5,2) DEFAULT 3.00,
  `pagibig_rate` decimal(5,2) DEFAULT 2.00,
  `tax_table` varchar(50) DEFAULT 'Latest BIR Tax Table',
  `allow_overtime` tinyint(1) DEFAULT 1,
  `allow_night_diff` tinyint(1) DEFAULT 1,
  `overtime_rate` decimal(5,2) DEFAULT 1.25,
  `night_diff_rate` decimal(5,2) DEFAULT 1.10,
  `late_worker_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `position_default_deductions` text DEFAULT NULL,
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `pay_periods`, `sss_rate`, `philhealth_rate`, `pagibig_rate`, `tax_table`, `allow_overtime`, `allow_night_diff`, `overtime_rate`, `night_diff_rate`, `late_worker_deduction`, `position_default_deductions`, `ConfigurationID`) VALUES
(1, 'Weekly', 950.00, 262.50, 200.00, 'Latest BIR Tax Table', 1, 1, 1.00, 1.10, 0.00, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_submission_snapshots`
--

CREATE TABLE `payroll_submission_snapshots` (
  `Payroll_RecordsID` int(11) NOT NULL,
  `SnapshotData` longtext NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `payroll_submission_snapshots`
--

INSERT INTO `payroll_submission_snapshots` (`Payroll_RecordsID`, `SnapshotData`, `CreatedAt`) VALUES
(4, '{\"version\":1,\"site_name\":\"Bulua Park\",\"submitted_by_name\":\"Bjorn Rosal\",\"settings\":{\"id\":\"1\",\"pay_periods\":\"Weekly\",\"sss_rate\":\"0.00\",\"philhealth_rate\":\"3.00\",\"pagibig_rate\":\"2.00\",\"tax_table\":\"Latest BIR Tax Table\",\"allow_overtime\":\"1\",\"allow_night_diff\":\"1\",\"overtime_rate\":\"1.25\",\"night_diff_rate\":\"1.10\",\"late_worker_deduction\":\"0.00\",\"position_default_deductions\":null,\"ConfigurationID\":\"1\"},\"workers\":[{\"WorkerID\":29,\"First_Name\":\"Daniel\",\"Last_Name\":\"Brooks\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"1.67\",\"position\":\"Laborer\",\"Gross_Pay\":130.47,\"Total_Deductions\":6.52,\"Net_Pay\":123.95,\"regular_hours\":0,\"overtime_hours\":1.67,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3.91,\"pagibig\":2.61,\"tax\":0,\"total\":6.52,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":23,\"First_Name\":\"John\",\"Last_Name\":\"Doe\",\"role_on_site\":\"Operator\",\"RateType\":\"Hourly\",\"RateAmount\":\"81.25\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"1.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"1.67\",\"position\":\"Operator\",\"Gross_Pay\":250.86,\"Total_Deductions\":0,\"Net_Pay\":250.86,\"regular_hours\":1,\"overtime_hours\":1.67,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"tax\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":33,\"First_Name\":\"Andrew\",\"Last_Name\":\"Parker\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.99\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":61.88,\"Total_Deductions\":3.1,\"Net_Pay\":58.78,\"regular_hours\":0.99,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":1.86,\"pagibig\":1.24,\"tax\":0,\"total\":3.1,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":24,\"First_Name\":\"Carl\",\"Last_Name\":\"Walker\",\"role_on_site\":\"Skilled\",\"RateType\":\"Hourly\",\"RateAmount\":\"68.75\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Skilled\",\"Gross_Pay\":0,\"Total_Deductions\":0,\"Net_Pay\":0,\"regular_hours\":0,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"tax\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0}],\"attendance\":{\"23\":{\"2026-10-07\":{\"WorkerID\":23,\"Date\":\"2026-10-07\",\"Time_In\":\"20:00:30\",\"Time_Out\":\"21:00:19\",\"Hours_Worked\":\"1.00\",\"AttendanceStatus\":\"Present\"}},\"29\":{\"2026-10-07\":{\"WorkerID\":29,\"Date\":\"2026-10-07\",\"Time_In\":\"20:01:23\",\"Time_Out\":\"00:00:00\",\"Hours_Worked\":\"0.00\",\"AttendanceStatus\":\"Present\"}},\"33\":{\"2026-10-07\":{\"WorkerID\":33,\"Date\":\"2026-10-07\",\"Time_In\":\"20:01:43\",\"Time_Out\":\"21:01:10\",\"Hours_Worked\":\"0.99\",\"AttendanceStatus\":\"Present\"}},\"24\":{\"2026-10-07\":{\"WorkerID\":24,\"Date\":\"2026-10-07\",\"Time_In\":\"20:02:06\",\"Time_Out\":\"00:00:00\",\"Hours_Worked\":\"0.00\",\"AttendanceStatus\":\"Present\"}}}}', '2026-10-07 14:29:27'),
(5, '{\"version\":1,\"site_name\":\"Carmen Tunnel Road\",\"submitted_by_name\":\"Ysabelle Calam\",\"settings\":{\"id\":\"1\",\"pay_periods\":\"Semi-monthly (1-15, 16-end)\",\"sss_rate\":\"0.00\",\"philhealth_rate\":\"3.00\",\"pagibig_rate\":\"2.00\",\"tax_table\":\"Latest BIR Tax Table\",\"allow_overtime\":\"1\",\"allow_night_diff\":\"1\",\"overtime_rate\":\"1.00\",\"night_diff_rate\":\"1.10\",\"late_worker_deduction\":\"0.00\",\"position_default_deductions\":null,\"ConfigurationID\":\"1\"},\"workers\":[{\"WorkerID\":30,\"First_Name\":\"Christopher\",\"Last_Name\":\"Bennett\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.50\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":31.25,\"Total_Deductions\":0,\"Net_Pay\":31.25,\"regular_hours\":0.5,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":35,\"First_Name\":\"Miguel\",\"Last_Name\":\"Ilogo\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.50\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":31.25,\"Total_Deductions\":0,\"Net_Pay\":31.25,\"regular_hours\":0.5,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":32,\"First_Name\":\"David\",\"Last_Name\":\"Mitchell\",\"role_on_site\":\"Roofer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.49\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Roofer\",\"Gross_Pay\":30.63,\"Total_Deductions\":0,\"Net_Pay\":30.63,\"regular_hours\":0.49,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0}],\"attendance\":{\"35\":{\"2026-10-08\":{\"WorkerID\":35,\"Date\":\"2026-10-08\",\"Time_In\":\"04:20:24\",\"Time_Out\":\"04:50:20\",\"Hours_Worked\":\"0.50\",\"AttendanceStatus\":\"Present\"}},\"30\":{\"2026-10-08\":{\"WorkerID\":30,\"Date\":\"2026-10-08\",\"Time_In\":\"04:20:49\",\"Time_Out\":\"04:50:49\",\"Hours_Worked\":\"0.50\",\"AttendanceStatus\":\"Present\"}},\"32\":{\"2026-10-08\":{\"WorkerID\":32,\"Date\":\"2026-10-08\",\"Time_In\":\"04:21:08\",\"Time_Out\":\"04:50:33\",\"Hours_Worked\":\"0.49\",\"AttendanceStatus\":\"Present\"}}}}', '2026-10-07 20:54:05'),
(6, '{\"version\":1,\"site_name\":\"Bakery\",\"submitted_by_name\":\"Meejay Calunsag\",\"settings\":{\"id\":\"1\",\"pay_periods\":\"Semi-monthly (1-15, 16-end)\",\"sss_rate\":\"0.00\",\"philhealth_rate\":\"3.00\",\"pagibig_rate\":\"2.00\",\"tax_table\":\"Latest BIR Tax Table\",\"allow_overtime\":\"1\",\"allow_night_diff\":\"1\",\"overtime_rate\":\"1.00\",\"night_diff_rate\":\"1.10\",\"late_worker_deduction\":\"0.00\",\"position_default_deductions\":null,\"ConfigurationID\":\"1\"},\"workers\":[{\"WorkerID\":42,\"First_Name\":\"Gabriel\",\"Last_Name\":\"Aquino\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.25\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":15.63,\"Total_Deductions\":5,\"Net_Pay\":10.63,\"regular_hours\":0.25,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3,\"pagibig\":2,\"total\":5,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":36,\"First_Name\":\"Juan\",\"Last_Name\":\"De La Cruz\",\"role_on_site\":\"Skilled\",\"RateType\":\"Hourly\",\"RateAmount\":\"68.75\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.13\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Skilled\",\"Gross_Pay\":8.94,\"Total_Deductions\":5,\"Net_Pay\":3.94,\"regular_hours\":0.13,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3,\"pagibig\":2,\"total\":5,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":41,\"First_Name\":\"Andress\",\"Last_Name\":\"Flores\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":0,\"Total_Deductions\":0,\"Net_Pay\":0,\"regular_hours\":0,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":1,\"late_hours\":0},{\"WorkerID\":40,\"First_Name\":\"Carlo\",\"Last_Name\":\"Garcia\",\"role_on_site\":\"Driver\",\"RateType\":\"Hourly\",\"RateAmount\":\"81.25\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Driver\",\"Gross_Pay\":0,\"Total_Deductions\":5,\"Net_Pay\":-5,\"regular_hours\":0,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3,\"pagibig\":2,\"total\":5,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":1,\"late_hours\":0},{\"WorkerID\":38,\"First_Name\":\"Marcelito\",\"Last_Name\":\"Ramos\",\"role_on_site\":\"Watchman\",\"RateType\":\"Hourly\",\"RateAmount\":\"56.55\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.00\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Watchman\",\"Gross_Pay\":0,\"Total_Deductions\":5,\"Net_Pay\":-5,\"regular_hours\":0,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3,\"pagibig\":2,\"total\":5,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0}],\"attendance\":{\"42\":{\"2026-10-08\":{\"WorkerID\":42,\"Date\":\"2026-10-08\",\"Time_In\":\"10:55:27\",\"Time_Out\":\"11:10:33\",\"Hours_Worked\":\"0.25\",\"AttendanceStatus\":\"Present\"}},\"36\":{\"2026-10-08\":{\"WorkerID\":36,\"Date\":\"2026-10-08\",\"Time_In\":\"10:55:00\",\"Time_Out\":\"11:10:00\",\"Hours_Worked\":\"0.13\",\"AttendanceStatus\":\"Present\"}},\"41\":{\"2026-10-08\":{\"WorkerID\":41,\"Date\":\"2026-10-08\",\"Time_In\":\"00:00:00\",\"Time_Out\":\"00:00:00\",\"Hours_Worked\":\"0.00\",\"AttendanceStatus\":\"Absent\"}},\"40\":{\"2026-10-08\":{\"WorkerID\":40,\"Date\":\"2026-10-08\",\"Time_In\":\"00:00:00\",\"Time_Out\":\"00:00:00\",\"Hours_Worked\":\"0.00\",\"AttendanceStatus\":\"Absent\"}}}}', '2026-10-08 03:22:49'),
(7, '{\"version\":1,\"site_name\":\"Carmen Tunnel Road\",\"submitted_by_name\":\"Bjorn Rosal\",\"settings\":{\"id\":\"1\",\"pay_periods\":\"Semi-monthly (1-15, 16-end)\",\"sss_rate\":\"0.00\",\"philhealth_rate\":\"3.00\",\"pagibig_rate\":\"2.00\",\"tax_table\":\"Latest BIR Tax Table\",\"allow_overtime\":\"1\",\"allow_night_diff\":\"1\",\"overtime_rate\":\"1.00\",\"night_diff_rate\":\"1.10\",\"late_worker_deduction\":\"0.00\",\"position_default_deductions\":null,\"ConfigurationID\":\"1\"},\"workers\":[{\"WorkerID\":37,\"First_Name\":\"Roberto\",\"Last_Name\":\"Garcia\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.21\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":13.13,\"Total_Deductions\":0,\"Net_Pay\":13.13,\"regular_hours\":0.21,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":43,\"First_Name\":\"Mark Anthony\",\"Last_Name\":\"Reyes\",\"role_on_site\":\"Laborer\",\"RateType\":\"Hourly\",\"RateAmount\":\"62.50\",\"GovernmentDeductionStatus\":\"With Deductions\",\"total_hours\":\"0.17\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Laborer\",\"Gross_Pay\":10.63,\"Total_Deductions\":5,\"Net_Pay\":5.63,\"regular_hours\":0.17,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":3,\"pagibig\":2,\"total\":5,\"government_deduction_status\":\"With Deductions\",\"government_deduction_label\":\"With Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0},{\"WorkerID\":39,\"First_Name\":\"Miguel\",\"Last_Name\":\"Santos\",\"role_on_site\":\"Skilled\",\"RateType\":\"Hourly\",\"RateAmount\":\"68.75\",\"GovernmentDeductionStatus\":\"No Deductions\",\"total_hours\":\"0.15\",\"attendance_overtime_hours\":\"0.00\",\"late_days\":\"0\",\"approved_overtime_hours\":\"0.00\",\"position\":\"Skilled\",\"Gross_Pay\":10.31,\"Total_Deductions\":0,\"Net_Pay\":10.31,\"regular_hours\":0.15,\"overtime_hours\":0,\"deduction_breakdown\":{\"sss\":0,\"philhealth\":0,\"pagibig\":0,\"total\":0,\"government_deduction_status\":\"No Deductions\",\"government_deduction_label\":\"No Government Deductions\"},\"fixed_deductions\":{\"late\":0,\"position\":0,\"total\":0},\"absent_days\":0,\"late_hours\":0}],\"attendance\":{\"37\":{\"2026-10-08\":{\"WorkerID\":37,\"Date\":\"2026-10-08\",\"Time_In\":\"11:55:31\",\"Time_Out\":\"12:08:00\",\"Hours_Worked\":\"0.21\",\"AttendanceStatus\":\"Present\"}},\"43\":{\"2026-10-08\":{\"WorkerID\":43,\"Date\":\"2026-10-08\",\"Time_In\":\"11:55:00\",\"Time_Out\":\"12:10:00\",\"Hours_Worked\":\"0.17\",\"AttendanceStatus\":\"Present\"}},\"39\":{\"2026-10-08\":{\"WorkerID\":39,\"Date\":\"2026-10-08\",\"Time_In\":\"11:56:00\",\"Time_Out\":\"12:10:00\",\"Hours_Worked\":\"0.15\",\"AttendanceStatus\":\"Present\"}}}}', '2026-10-08 07:41:23');

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
  `Coordinates` varchar(100) DEFAULT NULL,
  `Geofence_Radius_M` decimal(10,2) DEFAULT NULL,
  `Geofence_Lat` decimal(10,7) DEFAULT NULL,
  `Geofence_Lng` decimal(10,7) DEFAULT NULL,
  `Project_Type` varchar(50) DEFAULT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date DEFAULT NULL,
  `LocationID` int(11) NOT NULL,
  `Required_Workers` int(11) DEFAULT NULL,
  `Site_Manager` varchar(100) DEFAULT NULL,
  `Timekeeper_UserID` int(11) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `ShiftStart` time DEFAULT NULL,
  `LunchStart` time DEFAULT NULL,
  `LunchEnd` time DEFAULT NULL,
  `ShiftEnd` time DEFAULT NULL,
  `Archived_At` datetime DEFAULT NULL,
  `Is_Priority` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projectsite`
--

INSERT INTO `projectsite` (`SiteID`, `Site_Name`, `Location`, `Coordinates`, `Geofence_Radius_M`, `Geofence_Lat`, `Geofence_Lng`, `Project_Type`, `Start_Date`, `End_Date`, `LocationID`, `Required_Workers`, `Site_Manager`, `Timekeeper_UserID`, `Status`, `ShiftStart`, `LunchStart`, `LunchEnd`, `ShiftEnd`, `Archived_At`, `Is_Priority`) VALUES
(13, 'Bakery', 'Lirio Street, Burgos, Barangay 13, Carmen, Cagayan de Oro, Northern Mindanao, 9000, Philippines', '8.483265, 124.635361', 50.00, 8.4832650, 124.6353610, NULL, '2026-10-08', NULL, 1, 5, 'Johnny Calunsag', 72, 'Active', '08:00:00', '12:00:00', '13:00:00', '17:00:00', NULL, 0),
(14, 'Carmen Tunnel Road', 'Max Suniel Street, Coop Village, Barangay 13, Carmen, Cagayan de Oro, Northern Mindanao, 9000, Philippines', '8.482525, 124.633950', 40.00, 8.4825250, 124.6339500, NULL, '2026-10-08', NULL, 1, 4, 'Micheal Afton', NULL, 'Archived', '11:55:00', '12:00:00', '12:05:00', '12:10:00', '2026-10-11 22:53:21', 0),
(15, 'Coc building', '7-Eleven, Max Suniel Street, Coop Village, Barangay 13, Carmen, Cagayan de Oro, Northern Mindanao, 9000, Philippines', '8.481746, 124.636240', 10.00, 8.4817460, 124.6362400, NULL, '2026-10-08', NULL, 1, 20, 'Johnny Calunsag', 88, 'Active', '15:30:00', '16:00:00', '17:00:00', '19:00:00', NULL, 0),
(16, 'Hotel', 'Camp Phillips Road, Purok 16, Camp Phillips, Damilag, Manolo Fortich, Bukidnon, Northern Mindanao, 8703, Philippines', '8.372164, 124.811202', 50.00, 8.3721640, 124.8112020, NULL, '2026-10-12', NULL, 1, 5, 'Johnny Calunsag', 73, 'Active', '07:00:00', '12:00:00', '13:00:00', '17:00:00', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `remember_login_tokens`
--

CREATE TABLE `remember_login_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_login_tokens`
--

INSERT INTO `remember_login_tokens` (`id`, `user_id`, `selector`, `token_hash`, `expires_at`, `created_at`) VALUES
(5, 1, '1afcb4ba2b8e9c7c3fbebc71', '4a6b02be321718bb360fecba3498076bfb24debb372d2373e77f206ce191f0c4', '2026-11-12 11:10:39', '2026-10-13 03:10:39');

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
  `enable_ip_restriction` tinyint(1) DEFAULT 0,
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`id`, `password_expiry_days`, `min_password_length`, `require_special_char`, `require_number`, `require_uppercase`, `max_login_attempts`, `session_timeout_minutes`, `enable_2fa`, `enable_ip_restriction`, `ConfigurationID`) VALUES
(1, 90, 8, 1, 1, 1, 5, 120, 0, 0, 1);

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

--
-- Dumping data for table `site_schedule`
--

INSERT INTO `site_schedule` (`Site_ScheduleID`, `SiteID`, `DayOfWeek`, `ShiftStart`, `ShiftEnd`, `BreakDuration`) VALUES
(13, 13, 'Monday', '10:55:00', '11:10:00', 5),
(14, 14, 'Monday', '11:55:00', '12:10:00', 5),
(15, 15, 'Monday', '15:30:00', '19:00:00', 60),
(16, 16, 'Monday', '07:00:00', '17:00:00', 60);

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
  `database_size` varchar(20) DEFAULT '125 MB',
  `last_backup_at` datetime DEFAULT NULL,
  `ConfigurationID` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `maintenance_mode`, `debug_mode`, `data_retention_days`, `backup_schedule`, `timezone`, `date_format`, `time_format`, `system_version`, `last_update`, `server_environment`, `database_size`, `last_backup_at`, `ConfigurationID`) VALUES
(1, 0, 0, 365, 'Daily', 'Asia/Manila (GMT+8)', 'MM/DD/YYYY', '12-hour (AM/PM)', '1.0.5', '2023-07-01', 'Production', '125 MB', '2026-10-07 18:22:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `timekeeper`
--

CREATE TABLE `timekeeper` (
  `Timekeeper_ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timekeeper`
--

INSERT INTO `timekeeper` (`Timekeeper_ID`, `UserID`) VALUES
(6, 72),
(7, 73),
(8, 88);

-- --------------------------------------------------------

--
-- Table structure for table `timekeeper_assignment`
--

CREATE TABLE `timekeeper_assignment` (
  `AssignmentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `AssignedDate` date NOT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timekeeper_assignment`
--

INSERT INTO `timekeeper_assignment` (`AssignmentID`, `UserID`, `SiteID`, `AssignedDate`, `Status`, `CreatedAt`) VALUES
(16, 72, 13, '2026-10-08', 'Active', '2026-10-08 02:48:40'),
(17, 73, 14, '2026-10-08', 'Inactive', '2026-10-08 03:53:31'),
(18, 88, 15, '2026-10-08', 'Active', '2026-10-08 07:18:16'),
(19, 73, 16, '2026-10-12', 'Active', '2026-10-11 22:54:31');

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
  `Delayed_Category` int(11) DEFAULT NULL,
  `TimekeeperID` int(11) NOT NULL DEFAULT 0,
  `SiteName` varchar(255) NOT NULL DEFAULT '',
  `ReportType` varchar(100) DEFAULT NULL,
  `Subject` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `PhotoPath` varchar(500) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `timekeeper_id` int(11) NOT NULL DEFAULT 0,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `site_name` varchar(255) NOT NULL DEFAULT '',
  `report_type` varchar(120) NOT NULL DEFAULT '',
  `report_date` date DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `delay_type` varchar(120) DEFAULT NULL,
  `DelayCategory` varchar(100) DEFAULT NULL,
  `HoursLost` decimal(5,2) DEFAULT NULL,
  `WorkersAffected` int(11) DEFAULT NULL,
  `CauseOfDelay` text DEFAULT NULL,
  `RecommendedAction` text DEFAULT NULL,
  `AttachmentPath` varchar(500) DEFAULT NULL,
  `AdminRemarks` text DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `Feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timekeeper_reports`
--

INSERT INTO `timekeeper_reports` (`TK_ReportsID`, `UserID`, `SiteID`, `ReportDate`, `DelayType`, `AdditionalNotes`, `Status`, `Delayed_Category`, `TimekeeperID`, `SiteName`, `ReportType`, `Subject`, `Description`, `PhotoPath`, `CreatedAt`, `timekeeper_id`, `site_id`, `site_name`, `report_type`, `report_date`, `photo_path`, `attachment_path`, `created_at`, `notes`, `delay_type`, `DelayCategory`, `HoursLost`, `WorkersAffected`, `CauseOfDelay`, `RecommendedAction`, `AttachmentPath`, `AdminRemarks`, `UpdatedAt`, `request_id`, `Feedback`) VALUES
(4, 72, 13, '2026-10-08', NULL, NULL, 'Pending', NULL, 72, 'Bakery', 'Daily Accomplishment Report', 'Daily Accomplishment Report - 10/08/2026', 'hello', NULL, '2026-10-08 11:50:51', 0, 0, '', '', NULL, NULL, NULL, '2026-10-08 03:50:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 73, 16, '2026-10-13', NULL, NULL, 'Pending', NULL, 73, 'Hotel', 'Daily Accomplishment Report', 'Daily Accomplishment Report - 10/13/2026', 'hello', NULL, '2026-10-13 00:44:14', 0, 0, '', '', NULL, NULL, NULL, '2026-10-12 16:44:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  `failed_login_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `account_locked_at` datetime DEFAULT NULL,
  `admin_login_cooldown_until` datetime DEFAULT NULL,
  `password_last_set_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`, `full_name`, `profile_photo`, `status`, `last_login`, `failed_login_attempts`, `account_locked_at`, `admin_login_cooldown_until`, `password_last_set_at`, `must_change_password`, `first_name`, `last_name`) VALUES
(1, 'iche.calunsag.coc@phinmaed.com', '$2y$10$J1xStTVLsZmaiw2I2v4t/OOc8LbsXYV26ho5bMXIIxa0.fAHJBcyW', '2026-09-07 00:07:09', 'Meejay Calunsag', 'uploads/profile_photos/user_1_1d57f4f9a1ba466c.png', 'Active', '2026-10-13 03:22:52', 0, NULL, NULL, '2026-10-07 02:22:06', 0, NULL, NULL),
(70, 'bjla.rosal.coc@phinmaed.com', '$2y$10$UBgVYhUDR6mlZYMZBSxGceGinVFqvumiGVuHlmUyY5AzzvZIcNNya', '2026-10-08 02:03:59', 'Bjorn Rosal', NULL, 'Active', '2026-10-08 07:39:29', 0, NULL, NULL, '2026-10-08 02:31:04', 0, 'Bjorn', 'Rosal'),
(71, 'shandypacana456@gmail.com', '$2y$10$X.STfKpcWpmcuEY4v4wIeupjKAHZLCH6cFUC7VwoSPEUmsqxbN6gu', '2026-10-08 02:04:07', 'Shandy Pacana', NULL, 'Active', '2026-10-12 08:50:40', 0, NULL, NULL, '2026-10-08 02:08:28', 0, 'Shandy', 'Pacana'),
(72, 'joca.deligero.coc@phinmaed.com', '$2y$10$KOnSXv1ejR/TYVkUwHej7.nwxFvIcKV3fHg01VAehljaoI6yNI.e6', '2026-10-08 02:05:33', 'Jodisa Deligero', NULL, 'Active', '2026-10-12 04:09:31', 0, NULL, NULL, '2026-10-08 02:50:07', 1, 'Jodisa', 'Deligero'),
(73, 'lacl.caduyac.coc@phinmaed.com', '$2y$10$Or7hGp75YXV7Lf4fIQrWPOZph5A7eDNs245L9AIHdqaGyCwB4/1te', '2026-10-08 02:06:23', 'Laurice Caduyac', NULL, 'Active', '2026-10-12 04:41:53', 0, NULL, NULL, '2026-10-08 03:53:28', 1, 'Laurice', 'Caduyac'),
(74, 'iche.calunsag12.coc@phinmaed.com', '$2y$10$E2DNKGTOw0xn18iuvAK7G.nTZU.qdT3lp/X2ckJN1gdGaCSCbKiNG', '2026-10-08 02:07:14', 'Johnny Calunsag', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:07:14', 1, 'Johnny', 'Calunsag'),
(75, 'xnightfurysks@gmail.com', '$2y$10$3dE9cBcz2AkRpxnqUVAcIOHCCjaWL7NHb35uW7Npyx6oK20h7vaNm', '2026-10-08 02:08:28', 'Juan De La Cruz', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:08:28', 1, 'Juan', 'De La Cruz'),
(76, 'jodisadeligero9@gmail.com', '$2y$10$wME7x0q9imF/eu9PHjLru.O4wYjBjXxd8rk4ehpg8TfTb0YqTRvQS', '2026-10-08 02:08:58', 'Mark Logan', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:08:58', 1, 'Mark', 'Logan'),
(77, 'lightfurysks@gmail.com', '$2y$10$pDpoCwkcVOlmPb7CQHbH/.Z74Kv5T.oqHpjtIyO4Sm/GlZ4jXa8ue', '2026-10-08 02:10:25', 'Roberto Garcia', NULL, 'Active', '2026-10-12 11:17:46', 0, NULL, NULL, '2026-10-11 22:44:18', 0, 'Roberto', 'Garcia'),
(78, 'trixdvmps@gmail.com', '$2y$10$R/wGytX/VJtwURXlv.gKAOhkhIlQCNlC2oKKoo92wTkPwbqmv3koW', '2026-10-08 02:12:13', 'Marcelito Ramos', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:12:13', 1, 'Marcelito', 'Ramos'),
(79, 'miguelsantos@gmail.com', '$2y$10$FOvvKxBWXqROYyjmqAD3Oewvci1VTCrVmc7skGUDqOG9dwUUlxGoi', '2026-10-08 02:15:24', 'Miguel Santos', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:15:24', 1, 'Miguel', 'Santos'),
(80, 'carlogarcia@gmail.com', '$2y$10$77FDb4zBn2i6BsfFjBT/UuDNFBepW2INNMuPcyEuBhk4N1SpiLmNK', '2026-10-08 02:17:40', 'Carlo Garcia', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:17:40', 1, 'Carlo', 'Garcia'),
(81, 'michael@gmail.com', '$2y$10$59XyHTEMjz93HJQISCfSwOxT5TAtuiDsY1ImDJPTGng2.BvUXQBXy', '2026-10-08 02:18:53', 'Micheal Afton', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:18:53', 1, 'Micheal', 'Afton'),
(82, 'andressflores@gmail.com', '$2y$10$wrCIAPofbP4WAj9iIpGQz.Ar/rLxv2Ryx7C0x6DcGqWLB9jybRgem', '2026-10-08 02:19:37', 'Andress Flores', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:19:37', 1, 'Andress', 'Flores'),
(83, 'gabrielaquino@gmail.com', '$2y$10$F9q0ZNVsxxul2vp8orO8SuM8LxilqWDZWZzlqfEHjLwPRaaVHYoh2', '2026-10-08 02:22:07', 'Gabriel Aquino', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:22:07', 1, 'Gabriel', 'Aquino'),
(84, 'makranthony@gmail.com', '$2y$10$bn9Fpy8ToEd2OIWYBh2fDukKlgCDhobc85wwc9OVHW/M3VqU5xGoq', '2026-10-08 02:26:08', 'Mark Anthony Reyes', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 02:26:08', 1, 'Mark Anthony', 'Reyes'),
(85, 'patricknavarrow@gmail.com', '$2y$10$lyWhQXKsTR8iVvB0SeXd3.n7L1TOz9p2O5p0nSgOyGgcNGscfQOW6', '2026-10-08 02:29:18', 'Patrick Navarro', NULL, 'Inactive', NULL, 0, NULL, NULL, '2026-10-08 02:29:18', 1, 'Patrick', 'Navarro'),
(86, 'go@gmail.com', '$2y$10$F.j3o9JzCH645ngJPkmM2O7wDlCDX7eKezjnxec7l8FOfgBMiB1py', '2026-10-08 05:15:24', 'James Go', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 05:15:24', 1, 'James', 'Go'),
(87, 'smith123@gmail.com', '$2y$10$R07XVjTnSGQszPtBTmk2NuXqIQ.68AIkl/YUX23x15jJx0yQ3evgW', '2026-10-08 05:21:30', 'Sam Smith', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 05:21:30', 1, 'Sam', 'Smith'),
(88, 'bautista@gmail.com', '$2y$10$CHGm1GbOO2GkcSQxWwoxieDBUyGYYlES4F7ey7WDwSPrLYJumLTfy', '2026-10-08 05:22:38', 'Alicia Bautista', NULL, 'Active', '2026-10-08 07:20:14', 0, NULL, NULL, '2026-10-08 05:23:24', 1, 'Alicia', 'Bautista'),
(89, 'ralfie@gmail.com', '$2y$10$gDRNkZK8E4LvoN4NA4zsqOxlQ/2RpEUIMHhZJEshUD7fZneN7CeMi', '2026-10-08 07:17:14', 'Ralfie Calunsag', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-08 07:17:14', 1, 'Ralfie', 'Calunsag'),
(90, 'ythann@gmail.com', '$2y$10$WHDrrHAPsPQ4uHUfJJq9IuKFww4tflQRQVQG4TOdZDvagGzKYqQve', '2026-10-11 22:49:42', 'Ythann Kei', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-11 22:49:42', 1, 'Ythann', 'Kei'),
(91, 'kirishirorize@gmail.com', '$2y$10$6vxB.OGUIOit8eBFzHIHeOy7D7Q.Bfz58UZQFxKnmRZ2CmQCCnxVy', '2026-10-11 22:51:18', 'Arian Labis', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-11 22:51:18', 1, 'Arian', 'Labis'),
(92, 'mata@gmail.com', '$2y$10$evoWkZsos/ylYpRSW.4VC.s/hRR01MV4bwJPqQ75zxWzEigNjz7iK', '2026-10-11 22:52:46', 'Bernard Mata', NULL, 'Active', NULL, 0, NULL, NULL, '2026-10-11 22:52:46', 1, 'Bernard', 'Mata');

-- --------------------------------------------------------

--
-- Table structure for table `user_site_priorities`
--

CREATE TABLE `user_site_priorities` (
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_site_priorities`
--

INSERT INTO `user_site_priorities` (`UserID`, `SiteID`, `Created_At`) VALUES
(1, 1, '2026-10-01 08:41:56'),
(1, 2, '2026-09-30 05:17:10'),
(22, 4, '2026-09-30 08:26:31'),
(57, 12, '2026-10-07 22:04:56');

-- --------------------------------------------------------

--
-- Table structure for table `worker`
--

CREATE TABLE `worker` (
  `WorkerID` int(11) NOT NULL,
  `First_Name` varchar(100) NOT NULL,
  `Last_Name` varchar(100) NOT NULL,
  `Position` varchar(100) DEFAULT NULL,
  `RateType` enum('Hourly','Salary') NOT NULL,
  `RateAmount` decimal(10,2) NOT NULL,
  `GovernmentDeductionStatus` enum('With Deductions','No Deductions') NOT NULL DEFAULT 'With Deductions',
  `Phone` varchar(20) DEFAULT NULL,
  `DateHired` date NOT NULL,
  `WorkerStatusID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `GovernmentDeductionTypes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker`
--

INSERT INTO `worker` (`WorkerID`, `First_Name`, `Last_Name`, `Position`, `RateType`, `RateAmount`, `GovernmentDeductionStatus`, `Phone`, `DateHired`, `WorkerStatusID`, `UserID`, `photo_path`, `qr_code_path`, `GovernmentDeductionTypes`) VALUES
(36, 'Juan', 'De La Cruz', 'Skilled', 'Hourly', 68.75, 'With Deductions', '09261717255', '2026-10-08', 1, 75, 'uploads/employees/employee_36_44e5a6fdb0e4.jpg', 'uploads/qrcodes/employee_36_qr_6e3651ea.png', '[\"sss\"]'),
(37, 'Roberto', 'Garcia', 'Laborer', 'Hourly', 62.50, 'No Deductions', '09876652342', '2026-10-08', 1, 77, 'uploads/employees/employee_37_d53566cf8c41.jpg', 'uploads/qrcodes/employee_37_qr_a6519b58.png', NULL),
(38, 'Marcelito', 'Ramos', 'Watchman', 'Hourly', 56.55, 'With Deductions', '09665342612', '2026-10-08', 1, 78, 'uploads/employees/employee_38_75b24cc2dd2c.jpg', 'uploads/qrcodes/employee_38_qr_618b726d.png', '[\"pagibig\"]'),
(39, 'Miguel', 'Santos', 'Skilled', 'Hourly', 68.75, 'No Deductions', '09232312321', '2026-10-08', 1, 79, 'uploads/employees/employee_39_1b6673ac9196.png', 'uploads/qrcodes/employee_39_qr_ca28fa1f.png', NULL),
(40, 'Carlo', 'Garcia', 'Driver', 'Hourly', 81.25, 'With Deductions', '09768434343', '2026-10-08', 1, 80, 'uploads/employees/employee_40_57685da7646d.png', 'uploads/qrcodes/employee_40_qr_910f7741.png', '[\"sss\",\"philhealth\",\"pagibig\"]'),
(41, 'Andress', 'Flores', 'Laborer', 'Hourly', 62.50, 'No Deductions', '09745932482', '2026-10-08', 1, 82, 'uploads/employees/employee_41_e2a42e663eac.png', 'uploads/qrcodes/employee_41_qr_1f67f007.png', NULL),
(42, 'Gabriel', 'Aquino', 'Laborer', 'Hourly', 62.50, 'With Deductions', '09790123787', '2026-10-08', 1, 83, 'uploads/employees/employee_42_ddd4521b5d7e.png', 'uploads/qrcodes/employee_42_qr_033e774f.png', '[\"philhealth\"]'),
(43, 'Mark Anthony', 'Reyes', 'Laborer', 'Hourly', 62.50, 'With Deductions', '09889826378', '2026-10-08', 1, 84, 'uploads/employees/employee_43_65f683d1d877.png', 'uploads/qrcodes/employee_43_qr_2b235dcb.png', '[\"sss\",\"philhealth\",\"pagibig\"]'),
(44, 'Patrick', 'Navarro', 'Operator', 'Hourly', 81.25, 'With Deductions', '09875428345', '2026-10-08', 3, 85, 'uploads/employees/employee_44_ea0c75042a9e.png', 'uploads/qrcodes/employee_44_qr_1e881b64.png', '[\"sss\",\"philhealth\"]'),
(45, 'James', 'Go', 'Laborer', 'Hourly', 62.50, 'No Deductions', '09774324111', '2026-10-08', 1, 86, 'uploads/employees/employee_45_78fc8621f015.jpg', 'uploads/qrcodes/employee_45_qr_2666517c.png', NULL),
(46, 'Sam', 'Smith', 'Operator', 'Hourly', 81.25, 'With Deductions', '09887365263', '2026-10-08', 1, 87, 'uploads/employees/employee_46_422f934c1038.jpg', 'uploads/qrcodes/employee_46_qr_5405ab0d.png', '[\"sss\",\"philhealth\",\"pagibig\"]'),
(47, 'Ralfie', 'Calunsag', 'Lead Man', 'Hourly', 87.50, 'No Deductions', '09435353553', '2026-10-08', 1, 89, 'uploads/employees/employee_47_8e425776aed7.jpg', 'uploads/qrcodes/employee_47_qr_6f0dfc2c.png', NULL),
(48, 'Ythann', 'Kei', 'Operator', 'Hourly', 81.25, 'With Deductions', '09727635142', '2026-10-12', 1, 90, 'uploads/employees/employee_48_274ba99d457d.jpg', 'uploads/qrcodes/employee_48_qr_5614ea0e.png', '[\"philhealth\"]'),
(49, 'Arian', 'Labis', 'Laborer', 'Hourly', 62.50, 'No Deductions', '09764534261', '2026-10-12', 1, 91, 'uploads/employees/employee_49_20a0146437a3.jpg', 'uploads/qrcodes/employee_49_qr_1a14bfd0.png', NULL),
(50, 'Bernard', 'Mata', 'Laborer', 'Hourly', 62.50, 'No Deductions', '09763746255', '2026-10-12', 1, 92, 'uploads/employees/employee_50_ea48f550bd16.png', 'uploads/qrcodes/employee_50_qr_eaa21295.png', NULL);

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

--
-- Dumping data for table `workerassignment`
--

INSERT INTO `workerassignment` (`AssignmentID`, `WorkerID`, `SiteID`, `Assigned_Date`, `Role_On_Site`) VALUES
(64, 41, 13, '2026-10-08', 'Laborer'),
(65, 40, 13, '2026-10-08', 'Driver'),
(66, 42, 13, '2026-10-08', 'Laborer'),
(67, 38, 13, '2026-10-08', 'Watchman'),
(68, 36, 13, '2026-10-08', 'Skilled'),
(72, 46, 15, '2026-10-08', 'Operator'),
(73, 47, 15, '2026-10-08', 'Lead Man'),
(74, 45, 15, '2026-10-08', 'Laborer'),
(75, 49, 16, '2026-10-11', 'Laborer'),
(76, 50, 16, '2026-10-11', 'Laborer'),
(77, 48, 16, '2026-10-11', 'Operator'),
(78, 37, 16, '2026-10-11', 'Laborer'),
(79, 39, 16, '2026-10-11', 'Skilled');

-- --------------------------------------------------------

--
-- Table structure for table `workerstatus`
--

CREATE TABLE `workerstatus` (
  `WorkerStatusID` int(11) NOT NULL,
  `Status` enum('Active','OnLeave','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workerstatus`
--

INSERT INTO `workerstatus` (`WorkerStatusID`, `Status`) VALUES
(1, 'Active'),
(2, 'OnLeave'),
(3, 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `worker_profile`
--

CREATE TABLE `worker_profile` (
  `WorkerID` int(11) NOT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `StreetAddress` varchar(255) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `StateProvince` varchar(100) DEFAULT NULL,
  `PostalCode` varchar(20) DEFAULT NULL,
  `Country` varchar(100) DEFAULT NULL,
  `EmergencyContactName` varchar(150) DEFAULT NULL,
  `EmergencyContactPhone` varchar(30) DEFAULT NULL,
  `EmergencyContactRelationship` varchar(80) DEFAULT NULL,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_profile`
--

INSERT INTO `worker_profile` (`WorkerID`, `Email`, `DateOfBirth`, `StreetAddress`, `City`, `StateProvince`, `PostalCode`, `Country`, `EmergencyContactName`, `EmergencyContactPhone`, `EmergencyContactRelationship`, `UpdatedAt`) VALUES
(36, 'xnightfurysks@gmail.com', '2004-02-08', 'Purok 13', 'Manolo Fortich', 'Bukidnon', '8703', 'Philippines', 'Julia De La Cruz', '09777625344', 'Mother', '2026-10-08 02:08:28'),
(37, 'lightfurysks@gmail.com', '2000-10-16', 'Purok 18', 'Manolo Fortich', 'Bukidnon', '8703', 'Philippines', 'Juanita Garcia', '09887666543', 'Relative', '2026-10-08 02:10:25'),
(38, 'trixdvmps@gmail.com', '2002-06-08', 'Aries Street', 'Manolo Fortich', 'Bukidnon', '8703', 'Philippines', 'Maria Ramos', '09888763142', 'Sibling', '2026-10-08 02:12:13'),
(39, 'miguelsantos@gmail.com', '1993-06-23', '24 Velez Street, Barangay 24', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'noel miel', '09231231938', 'Friend', '2026-10-08 02:15:24'),
(40, 'carlogarcia@gmail.com', '1997-06-23', '57 Kauswagan Avenue, Barangay Kauswagan,', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Christine Mae Anderson', '09232863472', 'Guardian', '2026-10-08 02:17:40'),
(41, 'andressflores@gmail.com', '1996-01-09', '29 Lapasan Avenue, Barangay Lapasan,', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Jenelyn Carter', '09861263985', 'Guardian', '2026-10-08 02:19:37'),
(42, 'gabrielaquino@gmail.com', '1995-08-12', 'Camaman-an Road, Barangay Camaman-an', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Shandy Pacana', '09273484628', 'Guardian', '2026-10-08 02:22:07'),
(43, 'makranthony@gmail.com', '2000-03-08', 'Carmen Road, Barangay Carmen', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'shangy paxana', '09876482764', 'Friend', '2026-10-08 02:26:08'),
(44, 'patricknavarrow@gmail.com', '1990-04-28', 'umbia Road, Barangay Lumbia', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Jong Navarro', '09128939128', 'Father', '2026-10-08 02:29:18'),
(45, 'go@gmail.com', '2005-01-08', 'Zone 8', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Trixie Mifrano', '09834561123', 'Sibling', '2026-10-08 05:15:24'),
(46, 'smith123@gmail.com', '2002-01-08', 'Aries Street', 'Cagayan De Oro City', 'Misamis Oriental', '9000', 'Philippines', 'Trixie Garcia', '09887635452', 'Relative', '2026-10-08 05:21:30'),
(47, 'ralfie@gmail.com', '2008-10-01', 'Zamora street', 'Wao lanao del sur', 'Lanao del sur', '9716', 'Philippines', 'Macque Calunsag', '09664575676', 'Father', '2026-10-08 07:17:14'),
(48, 'ythann@gmail.com', '2005-02-12', 'misamis oriental', 'Manolo Fortich', 'Misamis Oriental', '9000', 'Philippines', 'Trixie Mifrano', '09066432517', 'Friend', '2026-10-11 22:49:42'),
(49, 'kirishirorize@gmail.com', '2003-06-12', 'Aries Street', 'Manolo Fortich', 'Bukidnon', '8703', 'Philippines', 'Trixie Mifrano', '09877353442', 'Mother', '2026-10-11 22:51:18'),
(50, 'mata@gmail.com', '2002-02-12', 'Purok 13', 'Cagayan De Oro City', 'Bukidnon', '8703', 'Philippines', 'Trixie Mifrano', '09237362154', 'Child', '2026-10-11 22:52:46');

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
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `idx_admin_notifications_read` (`IsRead`),
  ADD KEY `idx_admin_notifications_type` (`NotificationType`),
  ADD KEY `idx_admin_notifications_ref` (`ReferenceID`),
  ADD KEY `idx_admin_notifications_recipient` (`RecipientUserID`);

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
-- Indexes for table `assistant_notification_settings`
--
ALTER TABLE `assistant_notification_settings`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `unique_worker_date` (`WorkerID`,`Date`),
  ADD KEY `idx_worker_date` (`WorkerID`,`Date`),
  ADD KEY `idx_site_date` (`SiteID`,`Date`);

--
-- Indexes for table `attendance_photo_logs`
--
ALTER TABLE `attendance_photo_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `fk_attendance_photo_attendance` (`AttendanceID`),
  ADD KEY `fk_attendance_photo_worker` (`WorkerID`),
  ADD KEY `fk_attendance_photo_site` (`SiteID`),
  ADD KEY `fk_attendance_photo_timekeeper_user` (`TimekeeperID`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_settings_configuration` (`ConfigurationID`);

--
-- Indexes for table `configuration_registry`
--
ALTER TABLE `configuration_registry`
  ADD PRIMARY KEY (`ConfigurationID`),
  ADD UNIQUE KEY `uq_configuration_registry_name` (`ConfigurationName`);

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
-- Indexes for table `employee_position_catalog`
--
ALTER TABLE `employee_position_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employee_position_name` (`position_name`);

--
-- Indexes for table `hr`
--
ALTER TABLE `hr`
  ADD PRIMARY KEY (`HR_ID`),
  ADD UNIQUE KEY `uq_hr_user` (`UserID`);

--
-- Indexes for table `locationstatus`
--
ALTER TABLE `locationstatus`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`ManagerID`),
  ADD UNIQUE KEY `uq_managers_user` (`UserID`);

--
-- Indexes for table `notification_read_cutoffs`
--
ALTER TABLE `notification_read_cutoffs`
  ADD PRIMARY KEY (`RecipientUserID`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_settings_configuration` (`ConfigurationID`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`OvertimeID`),
  ADD KEY `idx_overtime_worker` (`WorkerID`),
  ADD KEY `idx_overtime_site` (`SiteID`),
  ADD KEY `idx_overtime_status` (`Status`),
  ADD KEY `idx_overtime_request_date` (`RequestDate`),
  ADD KEY `idx_overtime_submitted_by` (`SubmittedBy`),
  ADD KEY `fk_overtime_approved_by` (`ApprovedBy`);

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
  ADD UNIQUE KEY `uq_payrollstaffassignment_site` (`SiteID`),
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
  ADD KEY `SiteID` (`SiteID`),
  ADD KEY `fk_payroll_records_submitted_by` (`submitted_by`),
  ADD KEY `fk_payroll_records_approved_by` (`approved_by`),
  ADD KEY `fk_payroll_records_rejected_by` (`rejected_by`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_settings_configuration` (`ConfigurationID`);

--
-- Indexes for table `payroll_submission_snapshots`
--
ALTER TABLE `payroll_submission_snapshots`
  ADD PRIMARY KEY (`Payroll_RecordsID`);

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
  ADD KEY `idx_site_location` (`LocationID`),
  ADD KEY `idx_projectsite_timekeeper_user` (`Timekeeper_UserID`);

--
-- Indexes for table `remember_login_tokens`
--
ALTER TABLE `remember_login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_remember_selector` (`selector`),
  ADD KEY `idx_remember_user` (`user_id`),
  ADD KEY `idx_remember_expiry` (`expires_at`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_settings_configuration` (`ConfigurationID`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_system_settings_configuration` (`ConfigurationID`);

--
-- Indexes for table `timekeeper`
--
ALTER TABLE `timekeeper`
  ADD PRIMARY KEY (`Timekeeper_ID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `timekeeper_assignment`
--
ALTER TABLE `timekeeper_assignment`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `idx_tk_assign_user` (`UserID`),
  ADD KEY `idx_tk_assign_site` (`SiteID`),
  ADD KEY `idx_tk_assign_status` (`Status`);

--
-- Indexes for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  ADD PRIMARY KEY (`TK_ReportsID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `SiteID` (`SiteID`),
  ADD KEY `Delayed_Category` (`Delayed_Category`),
  ADD KEY `fk_timekeeper_reports_timekeeper_user` (`TimekeeperID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_site_priorities`
--
ALTER TABLE `user_site_priorities`
  ADD PRIMARY KEY (`UserID`,`SiteID`),
  ADD KEY `idx_user_site_priority_site` (`SiteID`);

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
-- Indexes for table `worker_profile`
--
ALTER TABLE `worker_profile`
  ADD PRIMARY KEY (`WorkerID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1039;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `assistantmanager`
--
ALTER TABLE `assistantmanager`
  MODIFY `AssistantManager_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `attendance_photo_logs`
--
ALTER TABLE `attendance_photo_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `Audit_logsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=583;

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
-- AUTO_INCREMENT for table `employee_position_catalog`
--
ALTER TABLE `employee_position_catalog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5122;

--
-- AUTO_INCREMENT for table `hr`
--
ALTER TABLE `hr`
  MODIFY `HR_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `locationstatus`
--
ALTER TABLE `locationstatus`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `managers`
--
ALTER TABLE `managers`
  MODIFY `ManagerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `OvertimeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `PayrollID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
  MODIFY `PayrollStaff_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payrollstaffassignment`
--
ALTER TABLE `payrollstaffassignment`
  MODIFY `staffAssignID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payroll_deduction`
--
ALTER TABLE `payroll_deduction`
  MODIFY `PayrollDeductionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `Payroll_RecordsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `SiteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `remember_login_tokens`
--
ALTER TABLE `remember_login_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `Site_ScheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timekeeper`
--
ALTER TABLE `timekeeper`
  MODIFY `Timekeeper_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `timekeeper_assignment`
--
ALTER TABLE `timekeeper_assignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  MODIFY `TK_ReportsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `worker`
--
ALTER TABLE `worker`
  MODIFY `WorkerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `workerassignment`
--
ALTER TABLE `workerassignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `workerstatus`
--
ALTER TABLE `workerstatus`
  MODIFY `WorkerStatusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `fk_admin_notifications_recipient` FOREIGN KEY (`RecipientUserID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
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
-- Constraints for table `attendance_photo_logs`
--
ALTER TABLE `attendance_photo_logs`
  ADD CONSTRAINT `fk_attendance_photo_attendance` FOREIGN KEY (`AttendanceID`) REFERENCES `attendance` (`AttendanceID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_photo_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_photo_timekeeper_user` FOREIGN KEY (`TimekeeperID`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_photo_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`) ON UPDATE CASCADE;

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
  ADD CONSTRAINT `hr_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

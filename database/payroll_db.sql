-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2026 at 03:10 PM
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
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `NotificationID` int(11) NOT NULL,
  `NotificationType` varchar(50) NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `assistantmanager`
--

INSERT INTO `assistantmanager` (`AssistantManager_ID`, `UserID`) VALUES
(1, 13);

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
  `TimeOutPhoto` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `WorkerID`, `SiteID`, `Date`, `Hours_Worked`, `Overtime_Hours`, `Time_In`, `Lunch_Out`, `Lunch_In`, `Time_Out`, `AttendanceStatus`, `PhotoPath`, `Latitude`, `Longitude`, `DistanceFromSite`, `AMTimeInPhoto`, `PMTimeInPhoto`, `TimeOutPhoto`) VALUES
(1, 1, 1, '2026-03-18', 8.17, 0.17, '07:55:00', NULL, NULL, '17:05:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 1, '2026-03-18', 7.47, 0.00, '08:32:00', NULL, NULL, '17:00:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 1, '2026-03-18', 0.00, 0.00, '00:00:00', NULL, NULL, '00:00:00', 'Absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 1, '2026-03-17', 8.00, 0.00, '08:00:00', NULL, NULL, '17:00:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 2, 1, '2026-03-17', 9.75, 1.75, '07:45:00', NULL, NULL, '18:30:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 1, '2024-10-01', 7.58, 0.00, '08:10:00', NULL, NULL, '16:45:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 3, 1, '2024-10-01', 8.25, 0.25, '08:00:00', NULL, NULL, '17:15:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 8, 8, '2026-06-02', 0.00, 0.00, '15:35:44', NULL, NULL, '00:00:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 16, 9, '2026-05-23', 0.00, 0.00, '15:44:30', NULL, NULL, '00:00:00', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 13, 9, '2026-05-23', 0.00, 0.00, '17:10:54', NULL, NULL, '17:11:10', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 14, 9, '2026-05-23', 0.01, 0.00, '17:17:10', NULL, NULL, '17:17:35', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 26, 9, '2026-06-09', 0.00, 0.00, '16:00:00', NULL, NULL, '00:00:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 24, 9, '2026-06-09', 0.00, 0.00, '16:14:17', NULL, NULL, '00:00:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 24, 9, '2026-06-10', 0.00, 0.00, '12:10:45', NULL, NULL, '06:16:03', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 28, 11, '2026-06-10', 0.53, 0.00, '12:48:07', NULL, NULL, '13:19:58', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 27, 11, '2026-06-10', 2.18, 0.00, '12:49:39', '15:00:00', '16:00:00', '07:41:22', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 29, 11, '2026-06-10', 2.12, 0.00, '12:53:30', '15:00:00', '16:00:00', '07:41:15', 'Present', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 25, 11, '2026-06-10', 1.65, 0.00, '13:21:16', '15:00:00', '16:00:00', '07:41:38', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 30, 12, '2026-06-10', 0.00, 0.00, '14:20:49', NULL, NULL, '00:00:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 31, 12, '2026-06-10', 0.00, 0.00, '14:21:13', NULL, NULL, '00:00:00', 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
(10, 1, 'Site Created', 'Created site: Downtown road', '2026-02-09 19:05:08'),
(11, 1, 'Worker Added', 'Added worker: Bjorn Latrell Rosal (ID: 4)', '2026-03-18 14:59:35'),
(12, 1, 'Site Assignment Removed', 'Removed site \'Haynako road\' from staff \'Staff\'', '2026-03-18 15:01:35'),
(13, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'Staff\' (Assignment ID: 4)', '2026-03-18 15:01:51'),
(14, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'Staff\' (Assignment ID: 8)', '2026-03-18 15:19:56'),
(15, 1, 'Site Assignment Removed', 'Removed site \'Downtown road\' from staff \'Staff\'', '2026-03-18 15:20:12'),
(16, 1, 'Site Assigned to Staff', 'Assigned site \'gwen school building\' to staff \'Staff\' (Assignment ID: 9)', '2026-03-18 21:08:01'),
(17, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'Staff\' (Assignment ID: 10)', '2026-03-18 21:12:31'),
(18, 1, 'Site Assigned to Staff', 'Assigned site \'Haynako road\' to staff \'Staff\' (Assignment ID: 11)', '2026-03-18 21:12:43'),
(19, 1, 'Site Assignment Removed', 'Removed site \'Downtown road\' from staff \'Staff\'', '2026-03-18 21:12:53'),
(20, 1, 'Site Assignment Removed', 'Removed site \'gwen school building\' from staff \'Staff\'', '2026-03-18 21:12:59'),
(21, 1, 'Site Assignment Removed', 'Removed site \'Haynako road\' from staff \'Staff\'', '2026-03-18 21:19:31'),
(22, 1, 'Site Assignment Removed', 'Removed site \'gwen school building\' from staff \'Staff\'', '2026-03-18 21:31:29'),
(23, 1, 'Site Assignment Removed', 'Removed site \'Downtown road\' from staff \'Staff\'', '2026-03-18 21:32:15'),
(24, 1, 'Site Assignment Removed', 'Removed site \'Downtown road\' from staff \'Staff\'', '2026-03-18 21:32:20'),
(25, 1, 'Site Assignment Removed', 'Removed site \'Test Site\' from staff \'Staff\'', '2026-03-18 21:32:48'),
(26, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'Staff\' (Assignment ID: 12)', '2026-03-18 21:45:18'),
(27, 1, 'Site Assignment Removed', 'Removed site \'Test Site\' from worker \'John Doe\'', '2026-03-18 22:20:09'),
(28, 1, 'Site Assignment Removed', 'Removed site \'Downtown road\' from staff \'Staff\'', '2026-03-18 22:38:08'),
(29, 1, 'Worker Added', 'Added worker: shandy pacana (ID: 5)', '2026-03-19 13:43:09'),
(30, 1, 'Site Created', 'Created site: shandi pacana road', '2026-03-19 13:45:27'),
(31, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'ddfs\' (Assignment ID: 13)', '2026-03-19 13:46:20'),
(32, 1, 'Site Created', 'Created site: trixie road', '2026-03-19 14:36:20'),
(33, 1, 'Worker Added', 'Added worker: kenji aghdsaj (ID: 8)', '2026-03-19 14:37:48'),
(34, 9, 'Site Assigned to Staff', 'Assigned site \'trixie road\' to staff \'laurice ahsdjka\' (Assignment ID: 14)', '2026-03-19 14:39:28'),
(35, 1, 'Site Assignment Removed', 'Removed site \'Test Site\' from staff \'Admin\'', '2026-04-23 13:46:10'),
(36, 1, 'Site Assigned to Staff', 'Assigned site \'Haynako road\' to staff \'Admin\' (Assignment ID: 15)', '2026-04-23 13:46:15'),
(37, 1, 'Site Assignment Removed', 'Removed site \'Haynako road\' from staff \'Staff\'', '2026-04-23 13:46:32'),
(38, 1, 'Site Assigned to Staff', 'Assigned site \'gwen school building\' to staff \'Staff\' (Assignment ID: 16)', '2026-04-23 13:46:35'),
(39, 1, 'Worker Added', 'Added worker: mich calunsag (ID: 9)', '2026-04-24 19:50:37'),
(40, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-24 20:21:37'),
(41, 1, 'User Login', 'Successful login as Admin', '2026-04-24 20:22:16'),
(42, 12, 'User Login', 'Successful login as HR', '2026-04-26 13:36:40'),
(43, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-26 13:46:02'),
(44, 1, 'User Login', 'Successful login as Admin', '2026-04-26 13:51:06'),
(45, 12, 'User Login', 'Successful login as HR', '2026-04-26 13:52:04'),
(46, 1, 'User Login', 'Successful login as Admin', '2026-04-26 13:53:03'),
(47, 13, 'User Login', 'Successful login as User', '2026-04-26 13:54:42'),
(48, 1, 'User Login', 'Successful login as Admin', '2026-04-26 13:55:26'),
(49, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-26 13:56:44'),
(50, 1, 'User Login', 'Successful login as Admin', '2026-04-26 22:54:04'),
(51, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-26 23:07:24'),
(52, 12, 'User Login', 'Successful login as HR', '2026-04-26 23:07:49'),
(53, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 19:15:11'),
(54, 12, 'User Login', 'Successful login as HR', '2026-04-27 19:17:09'),
(55, 1, 'User Login', 'Successful login as Admin', '2026-04-27 19:18:07'),
(56, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:18:49'),
(57, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 19:20:50'),
(58, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:22:07'),
(59, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 19:24:15'),
(60, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:24:43'),
(61, 12, 'User Login', 'Successful login as HR', '2026-04-27 19:25:10'),
(62, 1, 'User Login', 'Successful login as Admin', '2026-04-27 19:26:32'),
(63, 12, 'User Login', 'Successful login as HR', '2026-04-27 19:31:17'),
(64, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:31:34'),
(65, 1, 'User Login', 'Successful login as Admin', '2026-04-27 19:39:55'),
(66, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'Laurice\' (Assignment ID: 17)', '2026-04-27 19:40:08'),
(67, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:40:19'),
(68, 1, 'User Login', 'Successful login as Admin', '2026-04-27 19:45:50'),
(69, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 19:46:29'),
(70, 1, 'User Login', 'Successful login as Admin', '2026-04-27 19:49:09'),
(71, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 20:04:50'),
(72, 1, 'User Login', 'Successful login as Admin', '2026-04-27 20:06:42'),
(73, 12, 'User Login', 'Successful login as HR', '2026-04-27 20:07:30'),
(74, 1, 'User Login', 'Successful login as Admin', '2026-04-27 20:12:38'),
(75, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 20:13:23'),
(76, 12, 'User Login', 'Successful login as HR', '2026-04-27 20:14:06'),
(77, 1, 'User Login', 'Successful login as Admin', '2026-04-27 20:23:29'),
(78, 1, 'User Login', 'Successful login as Admin', '2026-04-27 20:24:59'),
(79, 1, 'User Login', 'Successful login as Admin', '2026-04-27 21:42:03'),
(80, 12, 'User Login', 'Successful login as HR', '2026-04-27 21:43:28'),
(81, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 21:46:21'),
(82, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 21:49:04'),
(83, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 21:49:21'),
(84, 1, 'User Login', 'Successful login as Admin', '2026-04-27 21:49:43'),
(85, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 21:52:14'),
(86, 1, 'User Login', 'Successful login as Admin', '2026-04-27 21:52:28'),
(87, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 22:40:05'),
(88, 12, 'User Login', 'Successful login as HR', '2026-04-27 22:40:17'),
(89, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 22:42:28'),
(90, 12, 'User Login', 'Successful login as HR', '2026-04-27 22:43:08'),
(91, 1, 'User Login', 'Successful login as Admin', '2026-04-27 23:04:52'),
(92, 12, 'User Login', 'Successful login as HR', '2026-04-27 23:05:17'),
(93, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 23:15:39'),
(94, 1, 'User Login', 'Successful login as Admin', '2026-04-27 23:16:06'),
(95, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 23:16:28'),
(96, 1, 'User Login', 'Successful login as Admin', '2026-04-27 23:51:22'),
(97, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-27 23:51:48'),
(98, 12, 'User Login', 'Successful login as HR', '2026-04-27 23:51:59'),
(99, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-27 23:52:27'),
(100, 12, 'User Login', 'Successful login as HR', '2026-04-28 04:19:51'),
(101, 1, 'User Login', 'Successful login as Admin', '2026-04-28 04:20:10'),
(102, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 04:21:03'),
(103, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 04:21:39'),
(104, 12, 'User Login', 'Successful login as HR', '2026-04-28 04:21:45'),
(105, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-28 04:21:58'),
(106, 12, 'User Login', 'Successful login as HR', '2026-04-28 08:15:41'),
(107, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 08:16:14'),
(108, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 09:48:08'),
(109, 12, 'User Login', 'Successful login as HR', '2026-04-28 09:48:17'),
(110, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-28 09:49:54'),
(111, 13, 'User Login', 'Successful login as Assistant Admin', '2026-04-28 09:51:56'),
(112, 12, 'User Login', 'Successful login as HR', '2026-04-28 10:02:32'),
(113, 1, 'User Login', 'Successful login as Admin', '2026-04-28 10:05:14'),
(114, 1, 'User Login', 'Successful login as Admin', '2026-04-28 10:17:47'),
(115, 14, 'User Login', 'Successful login as User', '2026-04-28 10:18:42'),
(116, 1, 'User Login', 'Successful login as Admin', '2026-04-28 10:19:01'),
(117, 14, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 10:24:05'),
(118, 1, 'User Login', 'Successful login as Admin', '2026-04-28 10:24:30'),
(119, 1, 'Site Assigned to Staff', 'Assigned site \'school\' to staff \'Shangkoy\' (Assignment ID: 18)', '2026-04-28 10:26:11'),
(120, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 10:26:26'),
(121, 12, 'User Login', 'Successful login as HR', '2026-04-28 10:28:14'),
(122, 12, 'User Login', 'Successful login as HR', '2026-04-28 10:28:37'),
(123, 1, 'User Login', 'Successful login as Admin', '2026-04-28 11:05:58'),
(124, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 11:06:36'),
(125, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 12:36:06'),
(126, 1, 'User Login', 'Successful login as Admin', '2026-04-28 12:36:12'),
(127, 15, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 12:37:47'),
(128, 1, 'User Login', 'Successful login as Admin', '2026-04-28 12:38:04'),
(129, 1, 'Site Assigned to Staff', 'Assigned site \'Downtown road\' to staff \'trixie gjjfff\' (Assignment ID: 19)', '2026-04-28 12:39:18'),
(130, 15, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 12:39:45'),
(131, 1, 'User Login', 'Successful login as Admin', '2026-04-28 12:53:38'),
(132, 15, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 12:54:50'),
(133, 11, 'User Login', 'Successful login as Payroll Staff', '2026-04-28 12:57:18'),
(134, 12, 'User Login', 'Successful login as HR', '2026-04-28 12:58:50'),
(135, 1, 'User Login', 'Successful login as Admin', '2026-04-28 13:37:28'),
(136, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 14:15:55'),
(137, 1, 'User Login', 'Successful login as Admin', '2026-05-05 14:18:34'),
(138, 13, 'User Login', 'Successful login as Assistant Admin', '2026-05-05 15:55:10'),
(139, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 15:59:02'),
(140, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 16:13:22'),
(141, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 16:14:49'),
(142, 1, 'User Login', 'Successful login as Admin', '2026-05-05 16:15:55'),
(143, 1, 'User Login', 'Successful login as Admin', '2026-05-05 16:16:51'),
(144, 13, 'User Login', 'Successful login as Assistant Admin', '2026-05-05 16:17:00'),
(145, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 16:17:21'),
(146, 1, 'User Login', 'Successful login as Admin', '2026-05-05 16:18:22'),
(147, 1, 'User Login', 'Successful login as Admin', '2026-05-05 16:35:27'),
(148, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 16:36:11'),
(149, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-05 22:55:08'),
(150, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-08 00:26:39'),
(151, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-08 07:36:59'),
(152, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-08 07:37:00'),
(153, 11, 'Site Created', 'Created site: Minecraft', '2026-05-08 11:57:36'),
(154, 1, 'User Login', 'Successful login as Admin', '2026-05-08 12:12:05'),
(155, 11, 'User Login', 'Successful login as Payroll Staff', '2026-05-08 12:12:30'),
(156, 1, 'User Login', 'Successful login as Admin', '2026-05-15 14:55:37'),
(157, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-05-15 14:56:39'),
(158, 1, 'Worker Added', 'Added worker: Trixie Kris (ID: 10)', '2026-05-15 14:56:39'),
(159, 1, 'User Login', 'Successful login as Admin', '2026-05-15 14:58:04'),
(160, 1, 'Employee Deleted', 'Deleted employee: Trixie Kris (ID: 10)', '2026-05-15 14:58:14'),
(161, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-05-15 15:00:40'),
(162, 1, 'Worker Added', 'Added worker: Kayleigha Calunsag (ID: 11)', '2026-05-15 15:00:40'),
(163, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Carpenter', '2026-05-15 17:33:42'),
(164, 1, 'Worker Added', 'Added worker: Shandy Pacana (ID: 12)', '2026-05-15 17:33:42'),
(165, 1, 'User Login', 'Successful login as Admin', '2026-05-23 13:09:30'),
(166, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-05-23 13:23:34'),
(167, 1, 'Worker Added', 'Added worker: Ysabelle Kris (ID: 13)', '2026-05-23 13:23:34'),
(168, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Carpenter', '2026-05-23 14:00:27'),
(169, 1, 'Worker Added', 'Added worker: Krisna Ky (ID: 14)', '2026-05-23 14:00:27'),
(170, 1, 'Site Created', 'Created site: Bakery Shop', '2026-05-23 14:01:33'),
(171, 1, 'Assign Worker to Site', 'Admin assigned Krisna Ky to Bakery Shop', '2026-05-23 14:07:57'),
(172, 1, 'Assign Worker to Site', 'Admin assigned Ysabelle Kris to Bakery Shop', '2026-05-23 14:07:57'),
(173, 1, 'Assign Worker to Site', 'Admin assigned Kayleigha Calunsag to Bakery Shop', '2026-05-23 14:07:57'),
(174, 1, 'Assign Worker to Site', 'Admin assigned Shandy Pacana to Bakery Shop', '2026-05-23 14:07:57'),
(175, 1, 'Update Worker Site Role', 'Admin set role to Electrician for worker 11 at site 10', '2026-05-23 14:08:24'),
(176, 1, 'Update Worker Site Role', 'Admin set role to Laborer for worker 12 at site 10', '2026-05-23 14:08:24'),
(177, 1, 'Update Worker Site Role', 'Admin set role to Electrician for worker 14 at site 10', '2026-05-23 14:18:27'),
(178, 1, 'Site Assignment Removed', 'Removed site \'Bakery Shop\' from worker \'Kayleigha Calunsag\' (Worker ID: 11)', '2026-05-23 14:18:27'),
(179, 1, 'Site Assignment Removed', 'Removed site \'Bakery Shop\' from worker \'Ysabelle Kris\' (Worker ID: 13)', '2026-05-23 14:18:27'),
(180, 1, 'Site Assignment Removed', 'Removed site \'Bakery Shop\' from worker \'Shandy Pacana\' (Worker ID: 12)', '2026-05-23 14:18:27'),
(181, 1, 'Assign Worker to Site', 'Admin assigned Ysabelle Kris to Bakery Shop', '2026-05-23 14:28:33'),
(182, 1, 'Update Worker Site Role', 'Admin updated role to Site Timekeeper for Ysabelle Kris at Bakery Shop', '2026-05-23 14:29:07'),
(183, 1, 'Assign Worker to Site', 'Admin assigned Shandy Pacana to Bakery Shop', '2026-05-23 14:29:51'),
(184, 1, 'Update Worker Site Role', 'Admin updated role to Plumber for Shandy Pacana at Bakery Shop', '2026-05-23 14:30:06'),
(185, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-05-23 14:34:58'),
(186, 1, 'Worker Added', 'Added worker: Jodisa Calunsag (ID: 15)', '2026-05-23 14:34:58'),
(187, 1, 'Assign Worker to Site', 'Admin assigned Jodisa Calunsag to Bakery Shop', '2026-05-23 14:35:33'),
(188, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-05-23 15:43:45'),
(189, 1, 'Worker Added', 'Added worker: Bjorn Pacana (ID: 16)', '2026-05-23 15:43:45'),
(190, 1, 'User Login', 'Successful login as Admin', '2026-06-01 18:43:05'),
(191, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-06-01 19:21:22'),
(192, 1, 'Worker Added', 'Added worker: Kayleigha Sy (ID: 19)', '2026-06-01 19:21:22'),
(193, 1, 'Employee Position Note', 'Preferred position captured during employee creation: Laborer', '2026-06-01 19:27:00'),
(194, 1, 'Worker Added', 'Added worker: Miya Ty (ID: 24)', '2026-06-01 19:27:00'),
(195, 1, 'Worker Added', 'Added worker: Kenji Sy (ID: 25)', '2026-06-02 21:34:23'),
(196, 1, 'Assign Worker to Site', 'Admin assigned Kenji Sy to Bakery Shop', '2026-06-02 21:35:02'),
(197, 1, 'Employee Updated', 'Admin updated employee: Kenji Sy (ID: 25)', '2026-06-02 21:35:02'),
(198, 1, 'Clock In', 'Worker 8 clocked in at site 8', '2026-06-02 21:35:44'),
(199, 1, 'Site Assignment Removed', 'Admin removed site \'Bakery Shop\' from worker \'Ysabelle Kris\'', '2026-06-03 00:00:22'),
(200, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Odette Ty to Bakery Shop', '2026-06-03 00:21:01'),
(201, 1, 'User Login', 'Successful login as Admin', '2026-06-04 12:22:23'),
(202, 1, 'User Login', 'Successful login as Admin', '2026-06-04 12:43:49'),
(203, 1, 'Site Archived', 'Admin User archived site: Bakery Shop', '2026-06-04 13:09:41'),
(204, 1, 'Site Restored', 'Admin User restored site: Bakery Shop', '2026-06-04 13:10:40'),
(205, 1, 'User Login', 'Successful login as Admin', '2026-06-08 18:37:11'),
(206, 1, 'Assign Worker to Site', 'Admin assigned Bjorn Pacana to Minecraft', '2026-06-08 18:38:46'),
(207, 1, 'Assign Worker to Site', 'Admin assigned Kayleigha Calunsag to Minecraft', '2026-06-08 18:38:46'),
(208, 1, 'Assign Worker to Site', 'Admin assigned Kayleigha Sy to Minecraft', '2026-06-08 18:38:46'),
(209, 1, 'Assign Worker to Site', 'Admin assigned Miya Ty to Minecraft', '2026-06-08 18:38:46'),
(210, 1, 'Assign Worker to Site', 'Admin assigned Ysabelle Kris to Minecraft', '2026-06-08 18:38:46'),
(211, 1, 'Employee Updated', 'Admin updated employee: Bjorn Pacana (ID: 16)', '2026-06-08 18:38:46'),
(212, 1, 'Employee Updated', 'Admin updated employee: Kayleigha Calunsag (ID: 11)', '2026-06-08 18:38:46'),
(213, 1, 'Employee Updated', 'Admin updated employee: Kayleigha Sy (ID: 19)', '2026-06-08 18:38:46'),
(214, 1, 'Employee Updated', 'Admin updated employee: Miya Ty (ID: 24)', '2026-06-08 18:38:46'),
(215, 1, 'Employee Updated', 'Admin updated employee: Ysabelle Kris (ID: 13)', '2026-06-08 18:38:46'),
(216, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper bjorn latrell rosal to Minecraft', '2026-06-08 18:39:06'),
(217, 1, 'User Login', 'Successful login as Admin', '2026-06-09 12:39:21'),
(218, 1, 'Worker Added', 'Added worker: Loven Ky (ID: 26)', '2026-06-09 14:48:18'),
(219, 1, 'Assign Worker to Site', 'Admin assigned Loven Ky to Minecraft', '2026-06-09 14:48:49'),
(220, 1, 'Employee Updated', 'Admin updated employee: Loven Ky (ID: 26)', '2026-06-09 14:48:49'),
(221, 1, 'Site Assignment Removed', 'Admin removed site \'Bakery Shop\' from worker \'Krisna Ky\'', '2026-06-09 15:00:28'),
(222, 1, 'Assign Worker to Site', 'Admin assigned Krisna Ky to Minecraft', '2026-06-09 15:00:38'),
(223, 1, 'Employee Updated', 'Admin updated employee: Krisna Ky (ID: 14)', '2026-06-09 15:00:38'),
(224, 1, 'User Login', 'Successful login as Admin', '2026-06-10 11:02:52'),
(225, 1, 'Clock Out', 'Worker 24 clocked out at site 9, 0hrs', '2026-06-10 12:16:03'),
(226, 1, 'Overtime Request Approved', 'Admin Admin approved Overtime Request #1', '2026-06-10 12:32:45'),
(227, 1, 'Worker Added', 'Added worker: Alica Keys (ID: 27)', '2026-06-10 12:41:14'),
(228, 1, 'Worker Added', 'Added worker: Taylor Swift (ID: 28)', '2026-06-10 12:41:40'),
(229, 1, 'Worker Added', 'Added worker: John Bern (ID: 29)', '2026-06-10 12:42:09'),
(230, 1, 'Site Created', 'Created site: Bookstore', '2026-06-10 12:45:07'),
(231, 1, 'Assign Worker to Site', 'Admin assigned Alica Keys to Bookstore', '2026-06-10 12:45:30'),
(232, 1, 'Assign Worker to Site', 'Admin assigned Taylor Swift to Bookstore', '2026-06-10 12:45:30'),
(233, 1, 'Assign Worker to Site', 'Admin assigned John Bern to Bookstore', '2026-06-10 12:45:30'),
(234, 1, 'Employee Updated', 'Admin updated employee: Alica Keys (ID: 27)', '2026-06-10 12:45:30'),
(235, 1, 'Employee Updated', 'Admin updated employee: Taylor Swift (ID: 28)', '2026-06-10 12:45:30'),
(236, 1, 'Employee Updated', 'Admin updated employee: John Bern (ID: 29)', '2026-06-10 12:45:30'),
(237, 1, 'User Password Changed', 'Changed password for Odette Ty (UserID 17)', '2026-06-10 12:46:01'),
(238, 1, 'User Password Changed', 'Changed password for Ysabelle Calam (UserID 18)', '2026-06-10 12:46:16'),
(239, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Odette Ty to Bookstore', '2026-06-10 12:47:00'),
(240, 1, 'Site Assignment Removed', 'Admin removed site \'Bakery Shop\' from worker \'Kenji Sy\'', '2026-06-10 12:58:56'),
(241, 1, 'Assign Worker to Site', 'Admin assigned Kenji Sy to Bookstore', '2026-06-10 12:59:16'),
(242, 1, 'Employee Updated', 'Admin updated employee: Kenji Sy (ID: 25)', '2026-06-10 12:59:16'),
(243, 1, 'Overtime Request Approved', 'Admin Admin approved Overtime Request #2', '2026-06-10 13:15:16'),
(244, 1, 'Clock Out', 'Worker 29 clocked out at site 11, 2.12hrs', '2026-06-10 13:41:15'),
(245, 1, 'Clock Out', 'Worker 27 clocked out at site 11, 2.18hrs', '2026-06-10 13:41:22'),
(246, 1, 'Clock Out', 'Worker 25 clocked out at site 11, 1.65hrs', '2026-06-10 13:41:38'),
(247, 1, 'Site Created', 'Created site: Trixie School', '2026-06-10 14:14:25'),
(248, 1, 'Worker Added', 'Added worker: Meejay Calunsag (ID: 30)', '2026-06-10 14:16:33'),
(249, 1, 'Worker Added', 'Added worker: laurice caduyac (ID: 31)', '2026-06-10 14:18:02'),
(250, 1, 'Assign Worker to Site', 'Admin assigned laurice caduyac to Trixie School', '2026-06-10 14:18:31'),
(251, 1, 'Assign Worker to Site', 'Admin assigned Meejay Calunsag to Trixie School', '2026-06-10 14:18:31'),
(252, 1, 'Employee Updated', 'Admin updated employee: laurice caduyac (ID: 31)', '2026-06-10 14:18:31'),
(253, 1, 'Employee Updated', 'Admin updated employee: Meejay Calunsag (ID: 30)', '2026-06-10 14:18:31'),
(254, 1, 'Site Timekeeper Assigned', 'Admin assigned Timekeeper Ysabelle Calam to Trixie School', '2026-06-10 14:19:14'),
(255, 1, 'Payroll Approved', 'Admin Admin approved payroll record #1 for site #6', '2026-06-10 21:49:51'),
(256, 1, 'User Login', 'Successful login as Admin', '2026-06-10 22:07:12'),
(257, 1, 'Payroll Approved', 'Admin Admin approved payroll record #2 for site #11', '2026-06-10 22:07:37'),
(258, 1, 'Payroll Approved', 'Admin Admin approved payroll record #3 for site #10', '2026-06-10 22:32:38'),
(259, 1, 'User Login', 'Successful login as Admin', '2026-07-09 20:29:55');

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
(1, 'Philippians CDO Construction Company', '123-45-6789', '(555) 123-4567', 'info@philippianscdo.com', '123 Main Street, CDO City', 'uploads/company_logo_1776922991.png');

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
(1, 26, 9, '2026-06-10', 'Regular Overtime', '17:00:00', '18:00:00', 1.00, 'Submitted via mobile app', 19, 'Approved', 1, '2026-06-10 12:32:45', '2026-06-10 12:25:47'),
(2, 28, 11, '2026-06-10', 'Regular Overtime', '20:00:00', '22:00:00', 2.00, 'Submitted via mobile app', 17, 'Approved', 1, '2026-06-10 13:15:16', '2026-06-10 13:15:04');

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
(1, 3, '2026-04-16', '2026-04-30', 0.00, 0.00, 0.00, '2026-04-28'),
(2, 9, '2026-04-16', '2026-04-30', 0.00, 0.00, 0.00, '2026-04-28'),
(3, 4, '2026-04-16', '2026-04-30', 0.00, 0.00, 0.00, '2026-04-28'),
(4, 2, '2026-04-16', '2026-04-30', 0.00, 0.00, 0.00, '2026-04-28'),
(5, 29, '2026-06-01', '2026-06-15', 318.00, 15.90, 302.10, '2026-06-10'),
(6, 27, '2026-06-01', '2026-06-15', 327.00, 16.35, 310.65, '2026-06-10'),
(7, 28, '2026-06-01', '2026-06-15', 454.50, 22.73, 431.77, '2026-06-10'),
(8, 25, '2026-06-01', '2026-06-15', 825.00, 41.25, 783.75, '2026-06-10'),
(9, 15, '2026-06-01', '2026-06-15', 0.00, 0.00, 0.00, '2026-06-10'),
(10, 12, '2026-06-01', '2026-06-15', 0.00, 0.00, 0.00, '2026-06-10');

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
(1, 1),
(2, 2),
(4, 8),
(5, 10),
(6, 11),
(7, 14),
(8, 15);

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
(13, 4, 6, '2026-03-19 13:46:20', NULL),
(14, 5, 8, '2026-03-19 14:39:28', NULL),
(15, 1, 4, '2026-04-23 13:46:15', NULL),
(16, 2, 3, '2026-04-23 13:46:35', NULL),
(17, 6, 6, '2026-04-27 19:40:08', NULL),
(18, 7, 2, '2026-04-28 10:26:11', NULL),
(19, 8, 6, '2026-04-28 12:39:18', NULL),
(20, 1, 1, '2026-05-15 13:53:28', NULL),
(21, 1, 4, '2026-05-15 13:53:28', NULL),
(22, 2, 6, '2026-05-15 13:53:28', NULL),
(23, 1, 1, '2026-06-01 18:42:03', NULL),
(24, 1, 4, '2026-06-01 18:42:03', NULL),
(25, 2, 6, '2026-06-01 18:42:03', NULL),
(26, 1, 1, '2026-06-04 12:20:53', NULL),
(27, 1, 4, '2026-06-04 12:20:53', NULL),
(28, 2, 6, '2026-06-04 12:20:53', NULL),
(29, 1, 1, '2026-06-08 16:47:03', NULL),
(30, 1, 4, '2026-06-08 16:47:03', NULL),
(31, 2, 6, '2026-06-08 16:47:03', NULL);

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
(1, 1, 6, '2026-04-16', '2026-04-30', 0.00, 0.00, 0.00, 'Approved', NULL, NULL, 1, '2026-06-10 21:49:51', NULL, NULL, NULL, 0, 0.00, 0.00),
(2, 5, 11, '2026-06-01', '2026-06-15', 1924.50, 96.23, 1828.27, 'Approved', 1, '2026-06-10 22:07:18', 1, '2026-06-10 22:07:37', NULL, NULL, NULL, 4, 6.48, 2.00),
(3, 9, 10, '2026-06-01', '2026-06-15', 0.00, 0.00, 0.00, 'Approved', 1, '2026-06-10 22:32:23', 1, '2026-06-10 22:32:38', NULL, NULL, NULL, 2, 0.00, 0.00);

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
(1, 'Semi-monthly (1-15, 16-end)', 0.00, 3.00, 2.00, 'Latest BIR Tax Table', 1, 1, 1.25, 1.10);

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
  `Is_Priority` tinyint(1) NOT NULL DEFAULT 0,
  `ShiftStart` time DEFAULT NULL,
  `LunchStart` time DEFAULT NULL,
  `LunchEnd` time DEFAULT NULL,
  `ShiftEnd` time DEFAULT NULL,
  `Archived_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projectsite`
--

INSERT INTO `projectsite` (`SiteID`, `Site_Name`, `Location`, `Coordinates`, `Geofence_Radius_M`, `Geofence_Lat`, `Geofence_Lng`, `Project_Type`, `Start_Date`, `End_Date`, `LocationID`, `Required_Workers`, `Site_Manager`, `Timekeeper_UserID`, `Status`, `ShiftStart`, `LunchStart`, `LunchEnd`, `ShiftEnd`, `Archived_At`) VALUES
(1, 'Test Site', 'Test Location', NULL, NULL, NULL, NULL, NULL, '2023-10-01', NULL, 1, 10, 'John Doe', NULL, 'Active', NULL, NULL, NULL, NULL, NULL),
(2, 'school', 'tertre', NULL, NULL, NULL, NULL, NULL, '2026-02-08', NULL, 1, 10, 'rrfreweqrw', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(3, 'gwen school building', 'carmen', NULL, NULL, NULL, NULL, NULL, '2026-02-08', NULL, 1, 30, 'John bwesit', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(4, 'Haynako road', 'Bulua', NULL, NULL, NULL, NULL, NULL, '2026-02-08', NULL, 1, 20, 'mj', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(5, 'Haynako road', 'tertre', NULL, NULL, NULL, NULL, NULL, '2026-02-09', NULL, 1, 15, 'mj', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(6, 'Downtown road', 'Bulua', NULL, NULL, NULL, NULL, NULL, '2026-02-09', NULL, 1, 12, 'mj', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(7, 'shandi pacana road', 'carmen', NULL, NULL, NULL, NULL, NULL, '2026-03-19', NULL, 1, 50, 'bjorn rosal', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(8, 'trixie road', 'bulua', NULL, NULL, NULL, NULL, NULL, '2026-03-19', NULL, 1, 10, 'shandi pacana', NULL, 'active', NULL, NULL, NULL, NULL, NULL),
(9, 'Minecraft', 'carmen', NULL, NULL, NULL, NULL, NULL, '2026-05-08', NULL, 1, 10, 'james emano', 19, 'active', NULL, NULL, NULL, NULL, NULL),
(10, 'Bakery Shop', 'Bukidnon', '', NULL, NULL, NULL, NULL, '2026-05-23', NULL, 1, 5, 'Shandy Pacana', 17, 'Active', '07:00:00', '12:00:00', '13:00:00', '17:00:00', NULL),
(11, 'Bookstore', 'Damilag', '8.346656, 124.813464', NULL, NULL, NULL, NULL, '2026-06-10', NULL, 1, 5, 'Tao Kris', 17, 'active', '12:50:00', '15:00:00', '16:00:00', '20:00:00', NULL),
(12, 'Trixie School', 'patag cagayan de oro', '8.488546, 124.628005', NULL, NULL, NULL, NULL, '2026-06-10', NULL, 1, 50, 'Angeline', 18, 'active', '07:00:00', '12:00:00', '13:00:00', '17:00:00', NULL);

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

--
-- Dumping data for table `siteassignmenthistory`
--

INSERT INTO `siteassignmenthistory` (`Site_HistoryID`, `WorkerID`, `SiteID`, `StartDate`, `EndDate`) VALUES
(1, 11, 10, '2026-05-23', '2026-05-23'),
(2, 13, 10, '2026-05-23', '2026-05-23'),
(3, 12, 10, '2026-05-23', '2026-05-23');

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
(1, 10, 'Monday', '07:00:00', '17:00:00', 0),
(2, 11, 'Monday', '12:50:00', '20:00:00', 60),
(3, 12, 'Monday', '07:00:00', '17:00:00', 60);

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
  `last_backup_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `maintenance_mode`, `debug_mode`, `data_retention_days`, `backup_schedule`, `timezone`, `date_format`, `time_format`, `system_version`, `last_update`, `server_environment`, `database_size`, `last_backup_at`) VALUES
(1, 0, 0, 365, 'Daily', 'Asia/Manila (GMT+8)', 'MM/DD/YYYY', '12-hour (AM/PM)', '1.0.5', '2023-07-01', 'Production', '125 MB', NULL);

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
(2, 17),
(3, 18),
(4, 19);

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
(1, 19, 9, '2026-06-08', 'Active', '2026-06-08 18:39:06'),
(2, 17, 11, '2026-06-10', 'Active', '2026-06-10 12:47:00'),
(3, 18, 12, '2026-06-10', 'Active', '2026-06-10 14:19:14');

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
  `UpdatedAt` datetime DEFAULT NULL
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
(1, 'admin@example.com', '$2y$10$IAhdjAkf0L8BqSJjKhF0n.55P6AbXxgpRnIy0Pj.YDSx3jjyrO1Ie', '2026-01-21 16:23:48', 'Admin', 'Active', '2026-07-09 20:29:55'),
(2, 'calunsagm66@gmail.com', '$2y$10$B1Za9IAJTT65Yx6JYwP0aOMl2PrpeRM1knnlyu8P4F6VOYrPfpB46', '2026-01-21 17:06:34', NULL, 'Active', NULL),
(7, 'worker@example.com', '$2y$10$VsOk0T6lVTmZlvMqAtOvGOi2a/6kdjjaTDkFHiST9Eo0fc3QKy4hO', '2026-02-11 03:13:55', 'worker', 'Active', NULL),
(8, 'bjornlatrellr@gmail.com', '$2y$10$QiKyzkoLgOdTKhS.vvYaS.wUH2zrMqqAsbTWk0tkNNcl33yW0F2f2', '2026-03-18 14:54:17', 'ddfs', 'Active', NULL),
(9, 'bjla.rosal.coc@phinmaed.com', '$2y$10$YC2pKFR9PT.8GeKSH3F4oeDx4829Ez6JZVEx64JklwQRvUnGEtikK', '2026-03-19 06:33:50', NULL, 'Inactive', NULL),
(10, 'laurice@gmail.con', '$2y$10$fcMlA1gs00l4upBctdP2LO.u77elSPS4ZSp/pHjZSqtQqSx8tXHGC', '2026-03-19 06:39:10', 'laurice ahsdjka', 'Active', NULL),
(11, 'laurice@gmail.com', '$2y$10$gpVgRlcT2cyRTdgUwtZ8ZeU43XT3QeeTts69eE2S5qbLbT6qFbx.6', '2026-04-23 06:32:16', 'Laurice', 'Active', '2026-05-08 12:12:30'),
(12, 'hr@gmail.com', '$2y$10$dFXDSojc5QO5NctP/8g/UeRYZrlX1.tLC0BxZ5ktWenJ59KMM.gfC', '2026-04-24 03:00:46', 'HR', 'Inactive', '2026-04-28 12:58:50'),
(13, 'assistant@gmail.com', '$2y$10$ay.oe1Suljokka4z4MELM.ZShkV/jcjiKMqVusRXs7LthiLbyrCym', '2026-04-26 05:54:11', 'Assistant Admin', 'Active', '2026-05-05 16:17:00'),
(14, 'fsjkfs@gmail.com', '$2y$10$/jhLifTTRxDwHpAZR0cHPu5dt0kf3Env9l0LLhzE6b/XITBGptDMm', '2026-04-28 02:17:04', 'Shangkoy', 'Active', '2026-04-28 10:24:05'),
(15, 'trixie@gmail.com', '$2y$10$JqZ032Wq35pAX1Z0Y9bkbOIOxwxHeOiU4spDhJRaM2RwFp9kmn02m', '2026-04-28 04:37:25', 'trixie gjjfff', 'Active', '2026-04-28 12:54:50'),
(17, 'odessa@gmail.com', '$2y$10$KnME6nLppy.onYAUWhA/6OIMnCbReOe09bGcdlRV8pDhctc60ma3W', '2026-06-02 15:33:10', 'Odette Ty', 'Active', '2026-06-10 12:47:25'),
(18, 'ysa@gmail.com', '$2y$10$.aa4dNt70U1nzqjeqZdZMOYNRizL5KpoD3Nftk0e7jZ3qMVeBgani', '2026-06-02 15:59:11', 'Ysabelle Calam', 'Active', '2026-06-10 14:19:59'),
(19, 'bjorn@gmail.com', '$2y$10$OmYbOVDL7F61t2YIz4WH0.XGW8zIorRLEo32DkdP6tYQRVGNCEkKS', '2026-06-08 10:38:11', 'bjorn latrell rosal', 'Active', '2026-06-10 21:21:26');

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

INSERT INTO `worker` (`WorkerID`, `First_Name`, `Last_Name`, `RateType`, `RateAmount`, `GovernmentDeductionStatus`, `Phone`, `DateHired`, `WorkerStatusID`, `UserID`, `photo_path`, `qr_code_path`, `GovernmentDeductionTypes`) VALUES
(1, 'John', 'Doe', 'Hourly', 150.00, 'With Deductions', '09123456789', '2024-01-01', 1, NULL, NULL, 'uploads/qrcodes/employee_1_qr_65f529f8.svg', NULL),
(2, 'Jane', 'Smith', 'Hourly', 150.00, 'With Deductions', '09123456790', '2024-01-01', 1, NULL, NULL, 'uploads/qrcodes/employee_2_qr_3a9618db.svg', NULL),
(3, 'Robert', 'Brown', 'Hourly', 150.00, 'With Deductions', '09123456791', '2024-01-01', 1, NULL, NULL, 'uploads/qrcodes/employee_3_qr_6fbfc5a6.svg', NULL),
(4, 'Bjorn Latrell', 'Rosal', '', 1000.00, 'With Deductions', '09559809298', '2026-03-18', 1, 1, NULL, 'uploads/qrcodes/employee_4_qr_50d8bdd4.svg', NULL),
(5, 'shandy', 'pacana', '', 1000.00, 'With Deductions', '09876438762', '2026-03-19', 1, 1, NULL, 'uploads/qrcodes/employee_5_qr_c213ca6f.svg', NULL),
(8, 'kenji', 'aghdsaj', '', 1200.00, 'With Deductions', '09066515876', '2026-03-19', 1, 1, NULL, 'uploads/qrcodes/employee_8_qr_43ada08b.svg', NULL),
(9, 'mich', 'calunsag', '', 59.00, 'With Deductions', '0978547434', '2026-04-24', 1, 1, NULL, 'uploads/qrcodes/employee_9_qr_910d9c9f.svg', NULL),
(11, 'Kayleigha', 'Calunsag', 'Hourly', 500.00, 'With Deductions', '09833221145', '2026-05-15', 1, 1, NULL, 'qrcodes/WRK-11.png', NULL),
(12, 'Shandy', 'Pacana', 'Hourly', 500.00, 'With Deductions', '09374823456', '2026-05-15', 1, 1, NULL, 'qrcodes/WRK-12.png', NULL),
(13, 'Ysabelle', 'Kris', 'Hourly', 300.00, 'With Deductions', '09887332154', '2026-05-23', 1, 1, 'uploads/employees/employee_13_0a74ada70473.jpg', 'qrcodes/WRK-13.png', NULL),
(14, 'Krisna', 'Ky', 'Hourly', 300.00, 'With Deductions', '09786534212', '2026-05-23', 1, 1, 'uploads/employees/employee_14_b1af15cf5963.jpg', 'qrcodes/WRK-14.png', NULL),
(15, 'Jodisa', 'Calunsag', 'Hourly', 500.00, 'With Deductions', '0933245672', '2026-05-23', 1, 1, 'uploads/employees/employee_15_1a7ebf7f85bf.jpg', 'qrcodes/WRK-15.png', NULL),
(16, 'Bjorn', 'Pacana', 'Hourly', 500.00, 'With Deductions', '09877654432', '2026-05-23', 1, 1, 'uploads/employees/employee_16_e37ced72010a.jpg', 'qrcodes/WRK-16.png', NULL),
(19, 'Kayleigha', 'Sy', 'Hourly', 499.99, 'With Deductions', '', '2026-06-01', 1, 1, 'uploads/employees/employee_19_92205eae10e7.jpg', 'uploads/qrcodes/employee_19_qr_ec3a2464.png', NULL),
(24, 'Miya', 'Ty', 'Hourly', 500.00, 'With Deductions', NULL, '2026-06-01', 1, 1, 'uploads/employees/employee_24_cf686ea7117c.jpg', 'uploads/qrcodes/employee_24_qr_fe324889.png', NULL),
(25, 'Kenji', 'Sy', 'Hourly', 500.00, 'With Deductions', NULL, '2026-06-02', 1, 1, 'uploads/employees/employee_25_eb64f5db64a2.jpg', 'uploads/qrcodes/employee_25_qr_026d52c5.png', NULL),
(26, 'Loven', 'Ky', 'Hourly', 500.00, 'With Deductions', '09877632371', '2026-06-09', 1, 1, NULL, 'uploads/qrcodes/employee_26_qr_e8b22d24.png', NULL),
(27, 'Alica', 'Keys', 'Hourly', 150.00, 'With Deductions', '09887355621', '2026-06-10', 1, 1, NULL, 'uploads/qrcodes/employee_27_qr_55aeb5b2.png', NULL),
(28, 'Taylor', 'Swift', 'Hourly', 150.00, 'With Deductions', '09887766543', '2026-06-10', 1, 1, NULL, 'uploads/qrcodes/employee_28_qr_4569394d.png', NULL),
(29, 'John', 'Bern', 'Hourly', 150.00, 'With Deductions', '09889976453', '2026-06-10', 1, 1, NULL, 'uploads/qrcodes/employee_29_qr_5df1134c.png', NULL),
(30, 'Meejay', 'Calunsag', 'Hourly', 150.00, 'With Deductions', '09767677676', '2026-06-10', 1, 1, NULL, 'uploads/qrcodes/employee_30_qr_55b6136d.png', NULL),
(31, 'laurice', 'caduyac', 'Hourly', 150.00, 'With Deductions', '09675464654', '2026-06-10', 1, 1, NULL, 'uploads/qrcodes/employee_31_qr_12fc95a4.png', NULL);

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
(1, 1, 1, '2024-10-01', 'Carpenter'),
(2, 2, 1, '2024-10-01', 'Electrician'),
(3, 3, 1, '2024-10-01', 'Laborer'),
(4, 4, 6, '0000-00-00', ''),
(5, 1, 5, '0000-00-00', ''),
(6, 5, 2, '0000-00-00', ''),
(7, 8, 8, '0000-00-00', ''),
(8, 2, 6, '0000-00-00', ''),
(9, 3, 6, '0000-00-00', ''),
(10, 9, 6, '0000-00-00', ''),
(16, 12, 10, '2026-05-23', 'Plumber'),
(17, 15, 10, '2026-05-23', 'Laborer'),
(19, 16, 9, '2026-06-08', 'Construction Worker'),
(20, 11, 9, '2026-06-08', 'Construction Worker'),
(21, 19, 9, '2026-06-08', 'Construction Worker'),
(22, 24, 9, '2026-06-08', 'Construction Worker'),
(23, 13, 9, '2026-06-08', 'Construction Worker'),
(24, 26, 9, '2026-06-09', 'Construction Worker'),
(25, 14, 9, '2026-06-09', 'Construction Worker'),
(26, 27, 11, '2026-06-10', 'Construction Worker'),
(27, 28, 11, '2026-06-10', 'Construction Worker'),
(28, 29, 11, '2026-06-10', 'Construction Worker'),
(29, 25, 11, '2026-06-10', 'Construction Worker'),
(30, 31, 12, '2026-06-10', 'Construction Worker'),
(31, 30, 12, '2026-06-10', 'Construction Worker');

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
(2, 'OnLeave');

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
(19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-01 11:21:22'),
(24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-01 11:26:59'),
(25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-02 13:34:22'),
(26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 06:48:17'),
(27, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 04:41:13'),
(28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 04:41:40'),
(29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 04:42:09'),
(30, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 06:16:33'),
(31, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 06:18:02');

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
  ADD KEY `idx_admin_notifications_ref` (`ReferenceID`);

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
-- Indexes for table `attendance_photo_logs`
--
ALTER TABLE `attendance_photo_logs`
  ADD PRIMARY KEY (`LogID`);

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
  ADD KEY `idx_site_location` (`LocationID`),
  ADD KEY `idx_projectsite_timekeeper_user` (`Timekeeper_UserID`);

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
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assistantmanager`
--
ALTER TABLE `assistantmanager`
  MODIFY `AssistantManager_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `attendance_photo_logs`
--
ALTER TABLE `attendance_photo_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `Audit_logsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260;

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
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `OvertimeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `PayrollID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `PayrollStaff_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payrollstaffassignment`
--
ALTER TABLE `payrollstaffassignment`
  MODIFY `staffAssignID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payroll_deduction`
--
ALTER TABLE `payroll_deduction`
  MODIFY `PayrollDeductionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `Payroll_RecordsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `SiteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `security_settings`
--
ALTER TABLE `security_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siteassignmenthistory`
--
ALTER TABLE `siteassignmenthistory`
  MODIFY `Site_HistoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `site_schedule`
--
ALTER TABLE `site_schedule`
  MODIFY `Site_ScheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timekeeper`
--
ALTER TABLE `timekeeper`
  MODIFY `Timekeeper_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timekeeper_assignment`
--
ALTER TABLE `timekeeper_assignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timekeeper_reports`
--
ALTER TABLE `timekeeper_reports`
  MODIFY `TK_ReportsID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `worker`
--
ALTER TABLE `worker`
  MODIFY `WorkerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `workerassignment`
--
ALTER TABLE `workerassignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `workerstatus`
--
ALTER TABLE `workerstatus`
  MODIFY `WorkerStatusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `fk_overtime_approved_by` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_overtime_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  ADD CONSTRAINT `fk_overtime_submitted_by` FOREIGN KEY (`SubmittedBy`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_overtime_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`);

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

--
-- Constraints for table `worker_profile`
--
ALTER TABLE `worker_profile`
  ADD CONSTRAINT `fk_worker_profile_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: payroll_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`Admin_ID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,1);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `RecipientUserID` int(11) DEFAULT NULL,
  `NotificationType` varchar(50) NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `idx_admin_notifications_read` (`IsRead`),
  KEY `idx_admin_notifications_type` (`NotificationType`),
  KEY `idx_admin_notifications_ref` (`ReferenceID`),
  KEY `idx_admin_notifications_recipient` (`RecipientUserID`),
  CONSTRAINT `fk_admin_notifications_recipient` FOREIGN KEY (`RecipientUserID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
INSERT INTO `admin_notifications` VALUES (2,4,'Site Assignment',1,'New site assignment','You have been assigned to Mj House.',0,'2026-09-07 22:55:41'),(3,4,'Site Assignment',2,'New site assignment','You have been assigned to Mj House.',0,'2026-09-11 14:54:58'),(6,4,'Site Assignment',3,'New site assignment','You have been assigned to Mj House.',0,'2026-09-15 23:03:36'),(59,1,'User Activity',43,'Shandy Pacana · Assistant Admin','Site Created: Created site: Sm Middle Town',0,'2026-09-07 23:32:38'),(60,15,'Site Assignment',4,'New site assignment','You have been assigned to Haynako road.',0,'2026-09-18 11:25:33'),(61,4,'Site Assignment',5,'New site assignment','You have been assigned to Haynako road.',0,'2026-09-18 11:25:52');
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approvals` (
  `ApprovalID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkerID` int(11) NOT NULL,
  `Action_Type` varchar(50) NOT NULL,
  `Approval_By` int(11) NOT NULL,
  `Approval_Status` varchar(50) NOT NULL,
  `Date` datetime NOT NULL,
  PRIMARY KEY (`ApprovalID`),
  KEY `WorkerID` (`WorkerID`),
  KEY `approvals` (`Approval_By`),
  CONSTRAINT `approvals` FOREIGN KEY (`Approval_By`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`Approval_By`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
INSERT INTO `approvals` VALUES (1,1,'Employee Creation',1,'Approved','2026-09-07 22:33:13'),(2,2,'Employee Creation',1,'Approved','2026-09-07 22:41:30'),(3,3,'Employee Creation',1,'Approved','2026-09-07 22:51:33'),(4,4,'Employee Creation',1,'Approved','2026-09-18 11:21:41'),(5,5,'Employee Creation',1,'Approved','2026-09-18 11:23:01'),(6,6,'Employee Creation',1,'Approved','2026-09-18 11:24:21');
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assistantmanager`
--

DROP TABLE IF EXISTS `assistantmanager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assistantmanager` (
  `AssistantManager_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`AssistantManager_ID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `assistantmanager_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assistantmanager`
--

LOCK TABLES `assistantmanager` WRITE;
/*!40000 ALTER TABLE `assistantmanager` DISABLE KEYS */;
INSERT INTO `assistantmanager` VALUES (1,2),(2,9);
/*!40000 ALTER TABLE `assistantmanager` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsLate` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`AttendanceID`),
  UNIQUE KEY `unique_worker_date` (`WorkerID`,`Date`),
  KEY `idx_worker_date` (`WorkerID`,`Date`),
  KEY `idx_site_date` (`SiteID`,`Date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,2,1,'2026-09-15',0.00,0.00,'23:33:18','23:33:22','23:33:24','23:33:27','Present',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(2,2,1,'2026-09-16',0.02,0.00,'00:28:56','00:29:04','00:29:06','00:29:08','Present',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(3,1,1,'2026-09-18',0.11,0.00,'11:32:47','12:00:00','11:35:05','11:39:14','Present','uploads/attendance_photos/attendance_1_time_in_1789702371.jpg',8.4819613,124.6363406,0.00,'uploads/attendance_photos/attendance_1_time_in_1789702371.jpg','uploads/attendance_photos/attendance_1_lunch_in_1789702509.jpg','uploads/attendance_photos/attendance_1_time_out_1789702758.jpg',1),(4,3,1,'2026-09-18',0.00,0.00,'11:39:58','12:00:00','11:40:37','00:00:00','Present','uploads/attendance_photos/attendance_3_time_in_1789702802.jpg',8.4819891,124.6363549,0.00,'uploads/attendance_photos/attendance_3_time_in_1789702802.jpg',NULL,NULL,1),(5,2,1,'2026-09-18',0.03,0.00,'11:43:04','12:00:00','11:43:54','11:44:39','Present','uploads/attendance_photos/attendance_2_time_in_1789702987.jpg',8.4819705,124.6363432,0.00,'uploads/attendance_photos/attendance_2_time_in_1789702987.jpg','uploads/attendance_photos/attendance_2_lunch_in_1789703038.jpg','uploads/attendance_photos/attendance_2_time_out_1789703083.jpg',1),(6,2,1,'2026-09-14',0.00,0.00,'23:33:18','23:33:22','23:33:24','23:33:27','Present',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(7,2,1,'2026-09-13',2.00,0.00,'08:28:56','12:00:00','13:29:06','16:29:08','Present',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_photo_logs`
--

DROP TABLE IF EXISTS `attendance_photo_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_photo_logs` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
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
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`LogID`),
  KEY `fk_attendance_photo_attendance` (`AttendanceID`),
  KEY `fk_attendance_photo_worker` (`WorkerID`),
  KEY `fk_attendance_photo_site` (`SiteID`),
  KEY `fk_attendance_photo_timekeeper_user` (`TimekeeperID`),
  CONSTRAINT `fk_attendance_photo_attendance` FOREIGN KEY (`AttendanceID`) REFERENCES `attendance` (`AttendanceID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_photo_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_photo_timekeeper_user` FOREIGN KEY (`TimekeeperID`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_photo_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_photo_logs`
--

LOCK TABLES `attendance_photo_logs` WRITE;
/*!40000 ALTER TABLE `attendance_photo_logs` DISABLE KEYS */;
INSERT INTO `attendance_photo_logs` VALUES (1,3,1,1,8,'Time In','2026-09-18','11:32:47',8.4819613,124.6363406,NULL,'uploads/attendance_photos/attendance_1_time_in_1789702371.jpg','2026-09-18 11:32:51'),(2,3,1,1,8,'Lunch In','2026-09-18','11:35:05',8.4819028,124.6363332,NULL,'uploads/attendance_photos/attendance_1_lunch_in_1789702509.jpg','2026-09-18 11:35:09'),(3,3,1,1,8,'Time Out','2026-09-18','11:39:14',8.4819611,124.6363606,NULL,'uploads/attendance_photos/attendance_1_time_out_1789702758.jpg','2026-09-18 11:39:18'),(4,4,3,1,8,'Time In','2026-09-18','11:39:58',8.4819891,124.6363549,NULL,'uploads/attendance_photos/attendance_3_time_in_1789702802.jpg','2026-09-18 11:40:02'),(5,5,2,1,8,'Time In','2026-09-18','11:43:04',8.4819705,124.6363432,NULL,'uploads/attendance_photos/attendance_2_time_in_1789702987.jpg','2026-09-18 11:43:07'),(6,5,2,1,8,'Lunch In','2026-09-18','11:43:54',8.4819266,124.6363308,NULL,'uploads/attendance_photos/attendance_2_lunch_in_1789703038.jpg','2026-09-18 11:43:58'),(7,5,2,1,8,'Time Out','2026-09-18','11:44:39',8.4819159,124.6363435,NULL,'uploads/attendance_photos/attendance_2_time_out_1789703083.jpg','2026-09-18 11:44:43');
/*!40000 ALTER TABLE `attendance_photo_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `Audit_logsID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Details` text DEFAULT NULL,
  `Date` datetime NOT NULL,
  PRIMARY KEY (`Audit_logsID`),
  KEY `UserID` (`UserID`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'User Login','Successful login as Admin','2026-09-07 08:08:30'),(2,3,'User Login','Successful login as HR','2026-09-07 08:31:52'),(3,1,'User Login','Successful login as Admin','2026-09-07 08:35:21'),(4,1,'User Password Changed','Changed password for Meejay Calunsag','2026-09-07 08:35:44'),(5,1,'User Login','Successful login as Admin','2026-09-07 09:05:31'),(6,3,'User Login','Successful login as HR','2026-09-07 09:05:48'),(7,3,'User Login','Successful login as HR','2026-09-07 09:27:43'),(8,1,'User Login','Successful login as Admin','2026-09-07 22:11:46'),(9,1,'Employee Position Note','Preferred position captured during employee creation: Construction Worker','2026-09-07 22:33:13'),(10,1,'Worker Added','Admin added worker: John Garcia (ID: 1) with Approved approval status','2026-09-07 22:33:13'),(11,1,'User Login','Successful login as Admin','2026-09-07 22:34:58'),(12,1,'User Login','Successful login as Admin','2026-09-07 22:40:09'),(13,1,'Employee Position Note','Preferred position captured during employee creation: Laborer','2026-09-07 22:41:30'),(14,1,'Worker Added','Admin added worker: Kirk Doho (ID: 2) with Approved approval status','2026-09-07 22:41:30'),(15,1,'User Login','Successful login as Admin','2026-09-07 22:44:15'),(16,1,'Site Created','Created site: Mj House','2026-09-07 22:46:26'),(17,1,'Site Updated','Admin updated site: Mj House (ID: 1)','2026-09-07 22:47:02'),(18,1,'Assign Worker to Site','Admin assigned John Garcia to Mj House','2026-09-07 22:48:29'),(19,1,'Assign Worker to Site','Admin assigned Kirk Doho to Mj House','2026-09-07 22:48:30'),(20,1,'Update Worker Site Role','Admin updated role to Construction Worker for John Garcia at Mj House','2026-09-07 22:48:30'),(21,1,'Update Worker Site Role','Admin updated role to Laborer for Kirk Doho at Mj House','2026-09-07 22:48:30'),(22,1,'Employee Position Note','Preferred position captured during employee creation: Carpenter','2026-09-07 22:51:33'),(23,1,'Worker Added','Admin added worker: John Wick (ID: 3) with Approved approval status','2026-09-07 22:51:33'),(24,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:53:15'),(25,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:54:09'),(26,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:54:44'),(27,1,'Site Timekeeper Assigned','Admin assigned Timekeeper Shanny Pacana to Mj House','2026-09-07 22:54:55'),(28,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:55:15'),(29,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:55:22'),(30,1,'Site Assigned to Staff','Assigned site \'Mj House\' to staff \'Shandy Pacana\'','2026-09-07 22:55:41'),(31,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 22:56:11'),(32,1,'Site Created','Created site: Macasandig','2026-09-07 22:59:54'),(33,4,'User Login','Successful login as Payroll Staff','2026-09-07 23:02:06'),(34,1,'User Login','Successful login as Admin','2026-09-07 23:11:36'),(35,1,'User Login','Successful login as Admin','2026-09-07 23:13:09'),(36,1,'User Login','Successful login as Admin','2026-09-07 23:13:18'),(37,1,'Employee Updated','Admin updated employee: John Wick (ID: 3)','2026-09-07 23:13:46'),(38,1,'Employee Updated','Admin updated employee: @^: bJohn Wick (ID: 3)','2026-09-07 23:13:58'),(39,1,'Employee Updated','Admin updated employee: HuuhJohn Wick (ID: 3)','2026-09-07 23:14:34'),(40,1,'Employee Updated','Admin updated employee: @!×=^John Wick (ID: 3)','2026-09-07 23:14:46'),(41,1,'User Login','Successful login as Admin','2026-09-07 23:14:46'),(42,9,'User Login','Successful login as Assistant Admin','2026-09-07 23:25:23'),(43,9,'Site Created','Created site: Sm Middle Town','2026-09-07 23:32:38'),(44,9,'User Login','Successful login as Assistant Admin','2026-09-07 23:42:00'),(45,6,'User Login','Successful login as Worker','2026-09-07 23:09:28'),(46,1,'User Login','Successful login as Admin','2026-09-07 23:48:03'),(47,1,'User Login','Successful login as Admin','2026-09-07 23:49:18'),(48,1,'User Login','Successful login as Admin','2026-09-07 23:52:56'),(49,5,'User Login','Successful login as Worker after first-time password setup','2026-09-07 23:55:43'),(50,1,'User Login','Successful login as Admin','2026-09-07 23:56:53'),(51,1,'User Login','Successful login as Admin','2026-09-08 15:33:08'),(52,1,'Site Created','Created site: <script>alert(\'XSS\');</script>','2026-09-08 15:34:04'),(53,1,'Site Updated','Admin updated site: <script>alert(\'XSS\');</script> (ID: 4)','2026-09-08 15:36:02'),(54,1,'Site Updated','Admin updated site: <body onload=alert(1)> (ID: 4)','2026-09-08 15:36:26'),(55,1,'Site Updated','Admin updated site: <audio onloadstart=\"alert(1)\"><source> (ID: 4)','2026-09-08 15:37:05'),(56,1,'Site Updated','Admin updated site: <body onscroll=alert(1)><div style=height:1000px></div><div id=x></div> (ID: 4)','2026-09-08 15:38:39'),(57,1,'User Login','Successful login as Admin','2026-09-08 15:44:31'),(58,1,'User Password Changed','Changed password for Shandy Pacana','2026-09-08 15:45:29'),(59,4,'User Login','Successful login as Payroll Staff','2026-09-08 15:46:03'),(60,1,'User Login','Successful login as Admin','2026-09-10 19:04:28'),(61,1,'User Login','Successful login as Admin','2026-09-11 07:35:38'),(62,1,'User Login','Successful login as Admin','2026-09-11 07:38:59'),(63,1,'User Login','Successful login as Admin','2026-09-11 07:39:00'),(64,1,'User Login','Successful login as Admin','2026-09-11 14:07:03'),(65,1,'User Login','Successful login as Admin','2026-09-11 14:12:51'),(66,1,'User Login','Successful login as Admin','2026-09-11 14:23:58'),(67,1,'User Login','Successful login as Admin','2026-09-11 14:30:42'),(68,1,'Site Assignment Removed','Removed site \'Mj House\' from staff \'Shandy Pacana\'','2026-09-11 14:54:55'),(69,1,'Site Assigned to Staff','Assigned site \'Mj House\' to staff \'Shandy Pacana\'','2026-09-11 14:54:58'),(70,1,'User Login','Successful login as Admin','2026-09-13 21:23:47'),(71,10,'User Login','Successful login as HR after first-time password setup','2026-09-13 21:44:12'),(72,1,'User Login','Successful login as Admin','2026-09-15 23:02:39'),(73,1,'Site Assignment Removed','Removed site \'Mj House\' from staff \'Shandy Pacana\'','2026-09-15 23:03:34'),(74,1,'Site Assigned to Staff','Assigned site \'Mj House\' to staff \'Shandy Pacana\'','2026-09-15 23:03:36'),(75,1,'User Login','Successful login as Admin','2026-09-15 23:30:10'),(76,1,'Clock In','Worker 2 clocked in at site 1','2026-09-15 23:33:18'),(77,1,'Lunch Out','Worker 2 lunch out at site 1','2026-09-15 23:33:22'),(78,1,'Lunch In','Worker 2 PM time in at site 1','2026-09-15 23:33:24'),(79,1,'Clock Out','Worker 2 clocked out at site 1, 0hrs','2026-09-15 23:33:27'),(80,1,'Clock In','Worker 2 clocked in at site 1','2026-09-16 00:28:56'),(81,1,'Lunch Out','Worker 2 lunch out at site 1','2026-09-16 00:29:04'),(82,1,'Lunch In','Worker 2 PM time in at site 1','2026-09-16 00:29:06'),(83,1,'Clock Out','Worker 2 clocked out at site 1, 0.02hrs','2026-09-16 00:29:08'),(84,1,'User Login','Successful login as Admin','2026-09-16 10:13:01'),(85,1,'User Login','Successful login as Admin','2026-09-16 11:42:53'),(86,1,'User Login','Successful login as Admin','2026-09-16 14:06:04'),(87,1,'User Login','Successful login as Admin','2026-09-16 20:38:08'),(88,1,'User Login','Successful login as Admin','2026-09-18 08:29:57'),(89,1,'User Login','Successful login as Admin','2026-09-18 10:32:22'),(90,10,'User Login','Successful login as HR','2026-09-18 10:34:46'),(91,10,'User Login','Successful login as HR','2026-09-18 10:39:22'),(92,1,'User Login','Successful login as Admin','2026-09-18 10:43:38'),(93,1,'Site Created','Created site: Haynako road','2026-09-18 10:57:04'),(94,1,'User Login','Successful login as Admin','2026-09-18 10:59:45'),(95,1,'User Login','Successful login as Admin','2026-09-18 11:01:34'),(96,1,'User Account Created','Created Timekeeper account for Michaella Calunsag (iche.calunsag.coc1@phinmaed.com).','2026-09-18 11:04:23'),(97,1,'User Account Created','Created Timekeeper account for Michaella Jane Calunsag (iche.calunsag.cocw@phinmaed.com).','2026-09-18 11:05:17'),(98,1,'Site Timekeeper Assigned','Admin assigned Timekeeper Michaella Jane Calunsag to Sm Middle Town','2026-09-18 11:05:32'),(99,1,'User Account Created','Created Payroll Staff account for Shangkoy Pacana (iche.calunsgdgdag.coc@phinmaed.com).','2026-09-18 11:10:44'),(100,1,'User Account Created','Created Payroll Staff account for Michaell dffs (michael1@gmail.com).','2026-09-18 11:11:40'),(101,1,'User Account Created','Created Payroll Staff account for triie mifrano (lightfurysks@gmail.com).','2026-09-18 11:14:57'),(102,15,'User Login','Successful login as Payroll Staff after first-time password setup','2026-09-18 11:18:34'),(103,1,'Site Timekeeper Assigned','Admin assigned Timekeeper Michaella Calunsag to Haynako road','2026-09-18 11:19:16'),(104,1,'User Login','Successful login as Admin','2026-09-18 11:21:22'),(105,1,'Employee Position Note','Preferred position captured during employee creation: Heavy Equipment Operator','2026-09-18 11:21:41'),(106,1,'Worker Added','Admin added worker: Michaella Calunsag (ID: 4) with Approved approval status','2026-09-18 11:21:41'),(107,1,'Employee Position Note','Preferred position captured during employee creation: Laborer','2026-09-18 11:23:01'),(108,1,'Worker Added','Admin added worker: Michaella kljrwlrjewrkww (ID: 5) with Approved approval status','2026-09-18 11:23:01'),(109,1,'Employee Position Note','Preferred position captured during employee creation: Plumber','2026-09-18 11:24:21'),(110,1,'Worker Added','Admin added worker: Shangkoy Pacana (ID: 6) with Approved approval status','2026-09-18 11:24:21'),(111,1,'Assign Worker to Site','Admin assigned Michaella Calunsag to Haynako road','2026-09-18 11:24:58'),(112,1,'Assign Worker to Site','Admin assigned Shangkoy Pacana to Haynako road','2026-09-18 11:24:58'),(113,1,'Assign Worker to Site','Admin assigned Michaella kljrwlrjewrkww to Haynako road','2026-09-18 11:24:59'),(114,1,'Update Worker Site Role','Admin updated role to Heavy Equipment Operator for Michaella Calunsag at Haynako road','2026-09-18 11:24:59'),(115,1,'Update Worker Site Role','Admin updated role to Plumber for Shangkoy Pacana at Haynako road','2026-09-18 11:25:00'),(116,1,'Update Worker Site Role','Admin updated role to Laborer for Michaella kljrwlrjewrkww at Haynako road','2026-09-18 11:25:00'),(117,1,'Site Assigned to Staff','Assigned site \'Haynako road\' to staff \'triie mifrano\'','2026-09-18 11:25:33'),(118,1,'Site Assignment Removed','Removed site \'Haynako road\' from staff \'triie mifrano\'','2026-09-18 11:25:46'),(119,1,'Site Assigned to Staff','Assigned site \'Haynako road\' to staff \'Shandy Pacana\'','2026-09-18 11:25:52'),(120,1,'Lunch In','Worker 3 PM time in at site 1','2026-09-18 11:40:37'),(121,1,'User Login','Successful login as Admin','2026-09-20 09:08:02'),(122,1,'User Account Created','Created Payroll Staff account for Shangkoydsd Pacanadss (dsadsa@gmail.com).','2026-09-20 09:10:37'),(123,1,'User Login','Successful login as Admin','2026-09-20 18:01:43'),(124,1,'User Login','Successful login as Admin','2026-09-20 18:06:08'),(125,1,'User Password Changed','Changed password for triie mifrano','2026-09-20 18:06:45'),(126,15,'User Login','Successful login as Payroll Staff','2026-09-20 18:06:59'),(127,1,'User Login','Successful login as Admin','2026-09-20 18:08:01'),(128,15,'User Login','Successful login as Payroll Staff','2026-09-20 18:09:13'),(129,1,'User Login','Successful login as Admin','2026-09-20 23:40:53'),(130,1,'User Account Created','Created Payroll Staff account for Michaelladsad Calunsag (iche.caludsdsnsag.coc@phinmaed.com).','2026-09-20 23:41:41'),(131,1,'User Login','Successful login as Admin','2026-09-21 06:26:45'),(132,1,'Site Updated','Admin updated site: Haynako road (ID: 5)','2026-09-21 06:31:39'),(133,1,'Site Updated','Admin updated site: Mj House (ID: 1)','2026-09-21 06:32:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brokenequipment`
--

DROP TABLE IF EXISTS `brokenequipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brokenequipment` (
  `BrokenEquipment_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Equipment_Name` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Description` text DEFAULT NULL,
  `Replacement_Cost` decimal(10,2) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Timekeeper_ID` int(11) NOT NULL,
  PRIMARY KEY (`BrokenEquipment_ID`),
  KEY `brokenequipment_fk_site` (`SiteID`),
  KEY `fk_brokenequipment_timekeeper` (`Timekeeper_ID`),
  CONSTRAINT `brokenequipment_fk_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  CONSTRAINT `fk_brokenequipment_timekeeper` FOREIGN KEY (`Timekeeper_ID`) REFERENCES `timekeeper` (`Timekeeper_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brokenequipment`
--

LOCK TABLES `brokenequipment` WRITE;
/*!40000 ALTER TABLE `brokenequipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `brokenequipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT 'Philippians CDO Construction Company',
  `tax_id` varchar(50) DEFAULT '123-45-6789',
  `phone` varchar(20) DEFAULT '(555) 123-4567',
  `email` varchar(255) DEFAULT 'info@philippianscdo.com',
  `address` text DEFAULT '123 Main Street, CDO City',
  `logo_path` varchar(255) DEFAULT NULL,
  `ConfigurationID` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_company_settings_configuration` (`ConfigurationID`),
  CONSTRAINT `fk_company_settings_configuration` FOREIGN KEY (`ConfigurationID`) REFERENCES `configuration_registry` (`ConfigurationID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'Philippians CDO Construction Company','123-45-6789','(555) 123-4567','info@philippianscdo.com','123 Main Street, CDO City','uploads/company_logo_1776922991.png',1);
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuration_registry`
--

DROP TABLE IF EXISTS `configuration_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuration_registry` (
  `ConfigurationID` tinyint(3) unsigned NOT NULL,
  `ConfigurationName` varchar(100) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ConfigurationID`),
  UNIQUE KEY `uq_configuration_registry_name` (`ConfigurationName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuration_registry`
--

LOCK TABLES `configuration_registry` WRITE;
/*!40000 ALTER TABLE `configuration_registry` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuration_registry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deduction`
--

DROP TABLE IF EXISTS `deduction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deduction` (
  `DeductionID` int(11) NOT NULL AUTO_INCREMENT,
  `Deduction_Name` varchar(100) NOT NULL,
  PRIMARY KEY (`DeductionID`),
  UNIQUE KEY `Deduction_Name` (`Deduction_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deduction`
--

LOCK TABLES `deduction` WRITE;
/*!40000 ALTER TABLE `deduction` DISABLE KEYS */;
/*!40000 ALTER TABLE `deduction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delayedcategory`
--

DROP TABLE IF EXISTS `delayedcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delayedcategory` (
  `Delayed_Category` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  PRIMARY KEY (`Delayed_Category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delayedcategory`
--

LOCK TABLES `delayedcategory` WRITE;
/*!40000 ALTER TABLE `delayedcategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `delayedcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_position_catalog`
--

DROP TABLE IF EXISTS `employee_position_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_position_catalog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position_name` varchar(100) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `salary_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_position_name` (`position_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3365 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_position_catalog`
--

LOCK TABLES `employee_position_catalog` WRITE;
/*!40000 ALTER TABLE `employee_position_catalog` DISABLE KEYS */;
INSERT INTO `employee_position_catalog` VALUES (1,'Construction Worker',125.00,22000.00,'2026-08-13 22:43:25'),(2,'Laborer',110.00,19000.00,'2026-08-13 22:43:25'),(3,'Carpenter',150.00,26000.00,'2026-08-13 22:43:25'),(4,'Mason',150.00,26000.00,'2026-08-13 22:43:25'),(5,'Electrician',175.00,30000.00,'2026-08-13 22:43:25'),(6,'Plumber',170.00,29000.00,'2026-08-13 22:43:25'),(7,'Welder',165.00,28000.00,'2026-08-13 22:43:25'),(8,'Painter',140.00,24000.00,'2026-08-13 22:43:25'),(9,'Heavy Equipment Operator',190.00,33000.00,'2026-08-13 22:43:25'),(10,'Site Foreman',220.00,38000.00,'2026-08-13 22:43:25'),(3331,'Manager',250.00,45000.00,'2026-09-21 00:12:20');
/*!40000 ALTER TABLE `employee_position_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr`
--

DROP TABLE IF EXISTS `hr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr` (
  `HR_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`HR_ID`),
  UNIQUE KEY `uq_hr_user` (`UserID`),
  CONSTRAINT `hr_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr`
--

LOCK TABLES `hr` WRITE;
/*!40000 ALTER TABLE `hr` DISABLE KEYS */;
INSERT INTO `hr` VALUES (1,3),(2,10);
/*!40000 ALTER TABLE `hr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locationstatus`
--

DROP TABLE IF EXISTS `locationstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locationstatus` (
  `LocationID` int(11) NOT NULL AUTO_INCREMENT,
  `Status` varchar(50) NOT NULL,
  PRIMARY KEY (`LocationID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locationstatus`
--

LOCK TABLES `locationstatus` WRITE;
/*!40000 ALTER TABLE `locationstatus` DISABLE KEYS */;
INSERT INTO `locationstatus` VALUES (1,'Active'),(2,'Inactive'),(3,'Pending');
/*!40000 ALTER TABLE `locationstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_notifications` tinyint(1) DEFAULT 1,
  `in_system_notifications` tinyint(1) DEFAULT 1,
  `leave_request_updates` tinyint(1) DEFAULT 1,
  `payroll_processing` tinyint(1) DEFAULT 1,
  `attendance_issues` tinyint(1) DEFAULT 0,
  `system_updates` tinyint(1) DEFAULT 0,
  `daily_reports` tinyint(1) DEFAULT 0,
  `email_digest_frequency` varchar(20) DEFAULT 'Daily',
  `ConfigurationID` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_notification_settings_configuration` (`ConfigurationID`),
  CONSTRAINT `fk_notification_settings_configuration` FOREIGN KEY (`ConfigurationID`) REFERENCES `configuration_registry` (`ConfigurationID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
INSERT INTO `notification_settings` VALUES (1,1,1,1,1,1,1,1,'Daily',1);
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_requests`
--

DROP TABLE IF EXISTS `overtime_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overtime_requests` (
  `OvertimeID` int(11) NOT NULL AUTO_INCREMENT,
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
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`OvertimeID`),
  KEY `idx_overtime_worker` (`WorkerID`),
  KEY `idx_overtime_site` (`SiteID`),
  KEY `idx_overtime_status` (`Status`),
  KEY `idx_overtime_request_date` (`RequestDate`),
  KEY `idx_overtime_submitted_by` (`SubmittedBy`),
  KEY `fk_overtime_approved_by` (`ApprovedBy`),
  CONSTRAINT `fk_overtime_approved_by` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_overtime_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  CONSTRAINT `fk_overtime_submitted_by` FOREIGN KEY (`SubmittedBy`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_overtime_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_requests`
--

LOCK TABLES `overtime_requests` WRITE;
/*!40000 ALTER TABLE `overtime_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `overtime_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `PayrollID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkerID` int(11) NOT NULL,
  `Pay_Period_Start` date NOT NULL,
  `Pay_Period_End` date NOT NULL,
  `Gross_Pay` decimal(10,2) NOT NULL,
  `Total_Deductions` decimal(10,2) NOT NULL,
  `Net_Pay` decimal(10,2) NOT NULL,
  `Date_Processed` date NOT NULL,
  PRIMARY KEY (`PayrollID`),
  UNIQUE KEY `unique_worker_period` (`WorkerID`,`Pay_Period_Start`,`Pay_Period_End`),
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
INSERT INTO `payroll` VALUES (1,2,'2026-09-08','2026-09-12',0.00,0.00,0.00,'2026-09-07'),(2,1,'2026-09-08','2026-09-12',0.00,0.00,0.00,'2026-09-07'),(3,3,'2026-09-08','2026-09-12',0.00,0.00,0.00,'2026-09-07');
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_deduction`
--

DROP TABLE IF EXISTS `payroll_deduction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_deduction` (
  `PayrollDeductionID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollID` int(11) NOT NULL,
  `DeductionID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`PayrollDeductionID`),
  KEY `idx_pd_payroll` (`PayrollID`),
  KEY `idx_pd_deduction` (`DeductionID`),
  CONSTRAINT `payroll_deduction_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  CONSTRAINT `payroll_deduction_ibfk_2` FOREIGN KEY (`DeductionID`) REFERENCES `deduction` (`DeductionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deduction`
--

LOCK TABLES `payroll_deduction` WRITE;
/*!40000 ALTER TABLE `payroll_deduction` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_deduction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_records` (
  `Payroll_RecordsID` int(11) NOT NULL AUTO_INCREMENT,
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
  `overtime_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`Payroll_RecordsID`),
  KEY `PayrollID` (`PayrollID`),
  KEY `SiteID` (`SiteID`),
  KEY `fk_payroll_records_submitted_by` (`submitted_by`),
  KEY `fk_payroll_records_approved_by` (`approved_by`),
  KEY `fk_payroll_records_rejected_by` (`rejected_by`),
  CONSTRAINT `fk_payroll_records_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payroll_records_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payroll_records_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
INSERT INTO `payroll_records` VALUES (1,1,1,'2026-09-08','2026-09-12',0.00,0.00,0.00,'Approved',1,'2026-09-07 23:04:33',1,'2026-09-07 23:04:33',NULL,NULL,NULL,3,0.00,0.00);
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_settings`
--

DROP TABLE IF EXISTS `payroll_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pay_periods` varchar(50) DEFAULT 'Semi-monthly (1-15, 16-end)',
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
  `ConfigurationID` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_settings_configuration` (`ConfigurationID`),
  CONSTRAINT `fk_payroll_settings_configuration` FOREIGN KEY (`ConfigurationID`) REFERENCES `configuration_registry` (`ConfigurationID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_settings`
--

LOCK TABLES `payroll_settings` WRITE;
/*!40000 ALTER TABLE `payroll_settings` DISABLE KEYS */;
INSERT INTO `payroll_settings` VALUES (1,'Weekly',0.00,3.00,2.00,'Latest BIR Tax Table',1,1,1.25,1.10,0.00,NULL,1);
/*!40000 ALTER TABLE `payroll_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrollattendance`
--

DROP TABLE IF EXISTS `payrollattendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrollattendance` (
  `PayrollAttendanceID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollID` int(11) NOT NULL,
  `AttendanceID` int(11) NOT NULL,
  PRIMARY KEY (`PayrollAttendanceID`),
  KEY `PayrollID` (`PayrollID`),
  KEY `AttendanceID` (`AttendanceID`),
  CONSTRAINT `payrollattendance_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`),
  CONSTRAINT `payrollattendance_ibfk_2` FOREIGN KEY (`AttendanceID`) REFERENCES `attendance` (`AttendanceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrollattendance`
--

LOCK TABLES `payrollattendance` WRITE;
/*!40000 ALTER TABLE `payrollattendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrollattendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrollbatch`
--

DROP TABLE IF EXISTS `payrollbatch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrollbatch` (
  `Payroll_BatchID` int(11) NOT NULL AUTO_INCREMENT,
  `SiteID` int(11) NOT NULL,
  `Date_Created` date NOT NULL,
  `Status` varchar(50) NOT NULL,
  `Total_Amount` decimal(12,2) NOT NULL,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`Payroll_BatchID`),
  KEY `SiteID` (`SiteID`),
  KEY `UserID` (`UserID`),
  CONSTRAINT `payrollbatch_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  CONSTRAINT `payrollbatch_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrollbatch`
--

LOCK TABLES `payrollbatch` WRITE;
/*!40000 ALTER TABLE `payrollbatch` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrollbatch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrollstaff`
--

DROP TABLE IF EXISTS `payrollstaff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrollstaff` (
  `PayrollStaff_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`PayrollStaff_ID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `payrollstaff_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrollstaff`
--

LOCK TABLES `payrollstaff` WRITE;
/*!40000 ALTER TABLE `payrollstaff` DISABLE KEYS */;
INSERT INTO `payrollstaff` VALUES (1,4),(2,13),(3,14),(4,15),(5,19);
/*!40000 ALTER TABLE `payrollstaff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrollstaffassignment`
--

DROP TABLE IF EXISTS `payrollstaffassignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrollstaffassignment` (
  `staffAssignID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollStaff_ID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Created_at` datetime NOT NULL,
  `Updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`staffAssignID`),
  UNIQUE KEY `uq_payrollstaffassignment_site` (`SiteID`),
  KEY `PayrollStaff_ID` (`PayrollStaff_ID`),
  KEY `SiteID` (`SiteID`),
  CONSTRAINT `payrollstaffassignment_ibfk_1` FOREIGN KEY (`PayrollStaff_ID`) REFERENCES `payrollstaff` (`PayrollStaff_ID`),
  CONSTRAINT `payrollstaffassignment_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrollstaffassignment`
--

LOCK TABLES `payrollstaffassignment` WRITE;
/*!40000 ALTER TABLE `payrollstaffassignment` DISABLE KEYS */;
INSERT INTO `payrollstaffassignment` VALUES (3,1,1,'2026-09-15 23:03:36',NULL),(5,1,5,'2026-09-18 11:25:52',NULL);
/*!40000 ALTER TABLE `payrollstaffassignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip`
--

DROP TABLE IF EXISTS `payslip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslip` (
  `PayslipID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollID` int(11) NOT NULL,
  `Issue_Date` date NOT NULL,
  `Payslip_Number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`PayslipID`),
  UNIQUE KEY `Payslip_Number` (`Payslip_Number`),
  KEY `PayrollID` (`PayrollID`),
  CONSTRAINT `payslip_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payroll` (`PayrollID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip`
--

LOCK TABLES `payslip` WRITE;
/*!40000 ALTER TABLE `payslip` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `PositionsID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkerID` int(11) NOT NULL,
  `PositionName` varchar(100) NOT NULL,
  `BasedHourlyRate` decimal(10,2) NOT NULL,
  `OvertimeMultiplier` decimal(5,2) NOT NULL,
  PRIMARY KEY (`PositionsID`),
  KEY `idx_positions_worker` (`WorkerID`),
  CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projecthistory`
--

DROP TABLE IF EXISTS `projecthistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projecthistory` (
  `History_ID` int(11) NOT NULL AUTO_INCREMENT,
  `SiteID` int(11) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `Updated_By` int(11) NOT NULL,
  `Update_Date` datetime NOT NULL,
  `Notes` text DEFAULT NULL,
  PRIMARY KEY (`History_ID`),
  KEY `Updated_By` (`Updated_By`),
  KEY `idx_history_site` (`SiteID`),
  CONSTRAINT `projecthistory_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  CONSTRAINT `projecthistory_ibfk_2` FOREIGN KEY (`Updated_By`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projecthistory`
--

LOCK TABLES `projecthistory` WRITE;
/*!40000 ALTER TABLE `projecthistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `projecthistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projectsite`
--

DROP TABLE IF EXISTS `projectsite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projectsite` (
  `SiteID` int(11) NOT NULL AUTO_INCREMENT,
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
  `Is_Priority` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`SiteID`),
  KEY `idx_site_location` (`LocationID`),
  KEY `idx_projectsite_timekeeper_user` (`Timekeeper_UserID`),
  CONSTRAINT `fk_projectsite_timekeeper_user` FOREIGN KEY (`Timekeeper_UserID`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `projectsite_ibfk_1` FOREIGN KEY (`LocationID`) REFERENCES `locationstatus` (`LocationID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projectsite`
--

LOCK TABLES `projectsite` WRITE;
/*!40000 ALTER TABLE `projectsite` DISABLE KEYS */;
INSERT INTO `projectsite` VALUES (1,'Mj House','Carmen-Canitoan Road, Purok 2-C, Macanhan, Carmen, Cagayan de Oro, Northern Mindanao, 9000, Philippines','8.495124, 124.616232',10.00,8.4951240,124.6162320,NULL,'2026-09-08',NULL,1,4,'Michaelladsad Calunsag',8,'Active','10:30:00','12:00:00','12:59:00','14:30:00',NULL,0),(2,'Macasandig','Macasandig Elementary School','8.463688, 124.643641',10.00,8.4636880,124.6436410,NULL,'2026-09-22',NULL,1,5,'Bjorn Latrell',NULL,'Inactive','07:00:00','12:00:00','13:00:00','17:00:00',NULL,0),(3,'Sm Middle Town','Macanhan Phillipines','8.469467, 124.633727',30.00,8.4694670,124.6337270,NULL,'2026-09-09',NULL,1,10,'Kenneth Lopez',12,'Inactive','07:00:00','12:00:00','13:00:00','17:00:00',NULL,0),(5,'Haynako road','7-Eleven, Max Suniel Street, Coop Village, Barangay 13, Carmen, Cagayan de Oro, Northern Mindanao, 9000, Philippines','8.481746, 124.636240',50.00,8.4817460,124.6362400,NULL,'2026-09-18',NULL,1,3,'Michaelladsad Calunsag',11,'Active','07:00:00','12:00:00','13:00:00','17:00:00',NULL,0);
/*!40000 ALTER TABLE `projectsite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_login_tokens`
--

DROP TABLE IF EXISTS `remember_login_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_login_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remember_selector` (`selector`),
  KEY `idx_remember_user` (`user_id`),
  KEY `idx_remember_expiry` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_login_tokens`
--

LOCK TABLES `remember_login_tokens` WRITE;
/*!40000 ALTER TABLE `remember_login_tokens` DISABLE KEYS */;
INSERT INTO `remember_login_tokens` VALUES (2,1,'174ce32c15dfce23034a684d','32022496ff100c76433cf912d6490ddfe344720aa6a527ef7206629ea81f72e9','2026-10-11 08:30:42','2026-09-11 14:30:42'),(3,1,'bc5527670a9cf635dd415197','96bbfbb207ea9551da3dab47d9ee518d201ec8c2d264f5ea65d01d40640529ff','2026-10-16 14:38:08','2026-09-16 20:38:08');
/*!40000 ALTER TABLE `remember_login_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_settings`
--

DROP TABLE IF EXISTS `security_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password_expiry_days` int(11) DEFAULT 90,
  `min_password_length` int(11) DEFAULT 8,
  `require_special_char` tinyint(1) DEFAULT 1,
  `require_number` tinyint(1) DEFAULT 1,
  `require_uppercase` tinyint(1) DEFAULT 1,
  `max_login_attempts` int(11) DEFAULT 5,
  `session_timeout_minutes` int(11) DEFAULT 30,
  `enable_2fa` tinyint(1) DEFAULT 0,
  `enable_ip_restriction` tinyint(1) DEFAULT 0,
  `ConfigurationID` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_security_settings_configuration` (`ConfigurationID`),
  CONSTRAINT `fk_security_settings_configuration` FOREIGN KEY (`ConfigurationID`) REFERENCES `configuration_registry` (`ConfigurationID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_settings`
--

LOCK TABLES `security_settings` WRITE;
/*!40000 ALTER TABLE `security_settings` DISABLE KEYS */;
INSERT INTO `security_settings` VALUES (1,30,8,1,1,1,5,54,0,0,1);
/*!40000 ALTER TABLE `security_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_schedule`
--

DROP TABLE IF EXISTS `site_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_schedule` (
  `Site_ScheduleID` int(11) NOT NULL AUTO_INCREMENT,
  `SiteID` int(11) NOT NULL,
  `DayOfWeek` varchar(20) NOT NULL,
  `ShiftStart` time NOT NULL,
  `ShiftEnd` time NOT NULL,
  `BreakDuration` int(11) DEFAULT 0,
  PRIMARY KEY (`Site_ScheduleID`),
  KEY `SiteID` (`SiteID`),
  CONSTRAINT `site_schedule_ibfk_1` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_schedule`
--

LOCK TABLES `site_schedule` WRITE;
/*!40000 ALTER TABLE `site_schedule` DISABLE KEYS */;
INSERT INTO `site_schedule` VALUES (1,1,'Monday','10:30:00','14:30:00',59),(2,2,'Monday','07:00:00','17:00:00',60),(3,3,'Monday','07:00:00','17:00:00',60),(5,5,'Monday','07:00:00','17:00:00',60);
/*!40000 ALTER TABLE `site_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siteassignmenthistory`
--

DROP TABLE IF EXISTS `siteassignmenthistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `siteassignmenthistory` (
  `Site_HistoryID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  PRIMARY KEY (`Site_HistoryID`),
  KEY `WorkerID` (`WorkerID`),
  KEY `SiteID` (`SiteID`),
  CONSTRAINT `siteassignmenthistory_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  CONSTRAINT `siteassignmenthistory_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siteassignmenthistory`
--

LOCK TABLES `siteassignmenthistory` WRITE;
/*!40000 ALTER TABLE `siteassignmenthistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `siteassignmenthistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `ConfigurationID` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_system_settings_configuration` (`ConfigurationID`),
  CONSTRAINT `fk_system_settings_configuration` FOREIGN KEY (`ConfigurationID`) REFERENCES `configuration_registry` (`ConfigurationID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,0,0,365,'Daily','Asia/Manila (GMT+8)','MM/DD/YYYY','12-hour (AM/PM)','1.0.5','2023-07-01','Production','125 MB','2026-07-28 19:18:53',1);
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timekeeper`
--

DROP TABLE IF EXISTS `timekeeper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timekeeper` (
  `Timekeeper_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`Timekeeper_ID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `timekeeper_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timekeeper`
--

LOCK TABLES `timekeeper` WRITE;
/*!40000 ALTER TABLE `timekeeper` DISABLE KEYS */;
INSERT INTO `timekeeper` VALUES (1,8),(2,11),(3,12);
/*!40000 ALTER TABLE `timekeeper` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timekeeper_assignment`
--

DROP TABLE IF EXISTS `timekeeper_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timekeeper_assignment` (
  `AssignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `AssignedDate` date NOT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AssignmentID`),
  KEY `idx_tk_assign_user` (`UserID`),
  KEY `idx_tk_assign_site` (`SiteID`),
  KEY `idx_tk_assign_status` (`Status`),
  CONSTRAINT `fk_timekeeper_assignment_site` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_timekeeper_assignment_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timekeeper_assignment`
--

LOCK TABLES `timekeeper_assignment` WRITE;
/*!40000 ALTER TABLE `timekeeper_assignment` DISABLE KEYS */;
INSERT INTO `timekeeper_assignment` VALUES (1,8,1,'2026-09-07','Active','2026-09-07 22:54:55'),(2,12,3,'2026-09-18','Active','2026-09-18 11:05:31'),(3,11,5,'2026-09-18','Active','2026-09-18 11:19:16');
/*!40000 ALTER TABLE `timekeeper_assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timekeeper_reports`
--

DROP TABLE IF EXISTS `timekeeper_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timekeeper_reports` (
  `TK_ReportsID` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`TK_ReportsID`),
  KEY `UserID` (`UserID`),
  KEY `SiteID` (`SiteID`),
  KEY `Delayed_Category` (`Delayed_Category`),
  KEY `fk_timekeeper_reports_timekeeper_user` (`TimekeeperID`),
  CONSTRAINT `fk_timekeeper_reports_timekeeper_user` FOREIGN KEY (`TimekeeperID`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `timekeeper_reports_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`),
  CONSTRAINT `timekeeper_reports_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`),
  CONSTRAINT `timekeeper_reports_ibfk_3` FOREIGN KEY (`Delayed_Category`) REFERENCES `delayedcategory` (`Delayed_Category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timekeeper_reports`
--

LOCK TABLES `timekeeper_reports` WRITE;
/*!40000 ALTER TABLE `timekeeper_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `timekeeper_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_site_priorities`
--

DROP TABLE IF EXISTS `user_site_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_site_priorities` (
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`UserID`,`SiteID`),
  KEY `idx_user_site_priority_site` (`SiteID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_site_priorities`
--

LOCK TABLES `user_site_priorities` WRITE;
/*!40000 ALTER TABLE `user_site_priorities` DISABLE KEYS */;
INSERT INTO `user_site_priorities` VALUES (4,1,'2026-09-07 15:03:44');
/*!40000 ALTER TABLE `user_site_priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  `failed_login_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `account_locked_at` datetime DEFAULT NULL,
  `admin_login_cooldown_until` datetime DEFAULT NULL,
  `password_last_set_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'iche.calunsag.coc@phinmaed.com','$2y$10$Dhzvla2Thl0GW7faYvnKKeiIUAWVAahqecMX.hPlZiUHUlnl76T.y','2026-09-07 00:07:09','Meejay Calunsag','uploads/profile_photos/user_1_d9b7741eccf417ed.jpg','Active','2026-09-21 06:26:45',0,NULL,NULL,'2026-09-07 08:35:44',0,NULL,NULL),(2,'trca.mifrano.coc@phinmaed.com','$2y$10$79SmHDqDcdks/MFarF0MheddLqGhkUwI/uM.0y/vAvdH0cgN1Z90u','2026-09-07 00:28:46','Trixie Mifrano',NULL,'Active',NULL,0,NULL,NULL,'2026-09-07 08:28:46',0,NULL,NULL),(3,'agneswalker190@gmail.com','$2y$10$7XTnTkEKMZY1u93GyQxALOy9qVovPlKVPnSsOB/krCgasS4eFSNkW','2026-09-07 00:29:41','Agnes Walker',NULL,'Active','2026-09-07 09:27:43',0,NULL,NULL,'2026-09-07 08:29:41',0,NULL,NULL),(4,'shangkoypacana@gmail.com','$2y$10$vI4uNcMF/0iR6ZJmRQx7eeNdkqnD1Fa6XM.3ea8DcdrUAx.dFLFae','2026-09-07 00:31:18','Shandy Pacana',NULL,'Active','2026-09-08 15:46:03',0,NULL,NULL,'2026-09-08 15:45:29',0,NULL,NULL),(5,'shandypacana456@gmail.com','$2y$10$1ZTaCexyCmTEYiIfTy5DwepcKJh47iIzhubYZRzsZHaWPGhssuz.y','2026-09-07 14:33:12','John Garcia',NULL,'Active','2026-09-07 23:55:43',0,NULL,NULL,'2026-09-07 23:55:43',0,NULL,NULL),(6,'r1ndessulogo@gmail.com','$2y$10$hi/n5y1K7VuGlL5v59a3xODc0ketuBiW9a.DWHUEfgtTzZu//q2nq','2026-09-07 14:41:30','Kirk Doho',NULL,'Active','2026-09-07 23:09:28',0,NULL,NULL,'2026-09-07 23:09:28',0,NULL,NULL),(7,'johnwick@gmail.com','$2y$10$O/v.wO9qfCgBaZgTE9A03OpeiZ1QuZI5CRicjf9U10fiLRq8DCLtC','2026-09-07 14:51:33','John Wick',NULL,'Active',NULL,0,NULL,NULL,'2026-09-07 22:51:33',1,NULL,NULL),(8,'shangkokoypacana@gmail.com','$2y$10$KdkOZio05atYamAJR0P2ze.9n.eeVRfmqGCiPGD3hzEpTw6jHwKQ2','2026-09-07 14:54:24','Shanny Pacana',NULL,'Active','2026-09-18 11:31:11',0,NULL,NULL,'2026-09-07 22:54:24',0,NULL,NULL),(9,'meinn3835@gmail.com','$2y$10$E2Jw.Fyjis/P0hRfj1JLDe0mlWYxiKZXH.5Yg9IFb4/WXhtYidbNW','2026-09-07 15:22:44','Shandy Pacana','uploads/profile_photos/user_9_507da899cacf00dc.jpg','Active','2026-09-07 23:42:00',0,NULL,NULL,'2026-09-07 23:22:44',0,NULL,NULL),(10,'pacana.shandys@gmail.com','$2y$10$M1/HHQmA8i1a2MEXcGVb1e.Cnt5wh2W9TVwCC/cXH6mnwtKEChTiG','2026-09-13 13:40:30','rindessu pacana',NULL,'Active','2026-09-18 10:39:22',0,NULL,NULL,'2026-09-13 21:44:12',0,NULL,NULL),(11,'iche.calunsag.coc1@phinmaed.com','$2y$10$FZDYe79kqfZ3b.RlZxTa2Of6t4oe0cId74K5UovVZm7Cx75r7ts.q','2026-09-18 03:04:16','Michaella Calunsag',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:04:16',1,NULL,NULL),(12,'iche.calunsag.cocw@phinmaed.com','$2y$10$AfCVHUCzG.ZHGZgcDPMo9eGH6kTFPraJhFzWGV4txLkRp..53stjS','2026-09-18 03:05:11','Michaella Jane Calunsag',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:05:11',1,NULL,NULL),(13,'iche.calunsgdgdag.coc@phinmaed.com','$2y$10$swd2Bfcx4u0TMdorL775KO6GaBRiA35PUaHnARVKxBDUANruYopLy','2026-09-18 03:10:32','Shangkoy Pacana',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:10:32',1,NULL,NULL),(14,'michael1@gmail.com','$2y$10$50Ni/qlwU9x33NNhikbeAOgt0L0SicNadB8/TglmiEz1r9nX87sc6','2026-09-18 03:11:33','Michaell dffs',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:11:33',1,NULL,NULL),(15,'lightfurysks@gmail.com','$2y$10$J5OxiEtGSTGExYOA0k.MWORWGMWVafSg5YXrcEmUpIyQzGjriYvyi','2026-09-18 03:14:48','triie mifrano',NULL,'Active','2026-09-20 18:09:13',0,NULL,NULL,'2026-09-20 18:06:45',0,NULL,NULL),(16,'iche.caluqwqeqnsag.coc@phinmaed.com','$2y$10$9nTGfdNV.KaExk83cDohLOaNcoJIeCHs.uhPL2S0lyhUNqbf0wTpC','2026-09-18 03:21:40','Michaella Calunsag',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:21:40',1,NULL,NULL),(17,'iche.calunsagrer.coc@phinmaed.com','$2y$10$xU/HG3X.DLgLSYp0yn4Rqu4zeKKGfzgvsD4ijCjglXvSdLabNitJu','2026-09-18 03:23:01','Michaella kljrwlrjewrkww',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:23:01',1,NULL,NULL),(18,'iche.calunstyryag.coc@phinmaed.com','$2y$10$e1CM8Jf8XSc9u5oCYyhh6uoGME4L1POtk2m6CCn7S7OW79n4wYrLW','2026-09-18 03:24:21','Shangkoy Pacana',NULL,'Active',NULL,0,NULL,NULL,'2026-09-18 11:24:21',1,NULL,NULL),(19,'dsadsa@gmail.com','$2y$10$egbEtzP.IwHZaJOWYVDTW.ZdeTk92c4w8N0Owo9q0B1vpqGkbTvj2','2026-09-20 01:10:31','Shangkoydsd Pacanadss',NULL,'Active',NULL,0,NULL,NULL,'2026-09-20 09:10:31',1,'Shangkoydsd','Pacanadss'),(20,'iche.caludsdsnsag.coc@phinmaed.com','$2y$10$wuvKVWpTZw9yw0OT8JkKmeqi9qkyGSpqq06zoP7oP2145KTtBWn6.','2026-09-20 15:41:35','Michaelladsad Calunsag',NULL,'Active',NULL,0,NULL,NULL,'2026-09-20 23:41:35',1,'Michaelladsad','Calunsag');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker`
--

DROP TABLE IF EXISTS `worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker` (
  `WorkerID` int(11) NOT NULL AUTO_INCREMENT,
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
  `GovernmentDeductionTypes` text DEFAULT NULL,
  PRIMARY KEY (`WorkerID`),
  UNIQUE KEY `Phone` (`Phone`),
  KEY `idx_worker_status` (`WorkerStatusID`),
  KEY `idx_worker_user` (`UserID`),
  CONSTRAINT `worker_ibfk_1` FOREIGN KEY (`WorkerStatusID`) REFERENCES `workerstatus` (`WorkerStatusID`),
  CONSTRAINT `worker_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker`
--

LOCK TABLES `worker` WRITE;
/*!40000 ALTER TABLE `worker` DISABLE KEYS */;
INSERT INTO `worker` VALUES (1,'John','Garcia','Construction Worker','Hourly',125.00,'No Deductions','09332456722','2026-09-07',1,5,'uploads/employees/employee_1_13e43ea2e1f8.png','uploads/qrcodes/employee_1_qr_c875fedb.png',NULL),(2,'Kirk','Doho','Laborer','Hourly',110.00,'No Deductions','09341233498','2026-09-07',1,6,'uploads/employees/employee_2_968f99acca5f.png','uploads/qrcodes/employee_2_qr_d1dbe5d1.png',NULL),(3,'John','Wick','Carpenter','Hourly',150.00,'With Deductions','09540444558','2026-09-07',1,7,'uploads/employees/employee_3_07f47b357080.jpg','uploads/qrcodes/employee_3_qr_b1ed8189.png','[\"sss\",\"philhealth\",\"pagibig\"]'),(4,'Michaella','Calunsag','Heavy Equipment Operator','Hourly',190.00,'With Deductions','09757576567','2026-09-18',1,16,'uploads/employees/employee_4_fd816da43eb5.png','uploads/qrcodes/employee_4_qr_207f7e04.png','[\"sss\",\"philhealth\",\"pagibig\"]'),(5,'Michaella','kljrwlrjewrkww','Laborer','Hourly',110.00,'With Deductions','00879878789','2026-09-18',1,17,'uploads/employees/employee_5_3b9796b82f14.png','uploads/qrcodes/employee_5_qr_045e5e78.png','[\"sss\",\"philhealth\",\"pagibig\"]'),(6,'Shangkoy','Pacana','Plumber','Hourly',170.00,'With Deductions','09778788888','2026-09-18',1,18,'uploads/employees/employee_6_9c2644fe8300.png','uploads/qrcodes/employee_6_qr_e0e9e9af.png','[\"sss\",\"philhealth\",\"pagibig\"]'),(7,'Michaelladsad','Calunsag','Manager','Hourly',0.00,'With Deductions',NULL,'2026-09-21',1,20,NULL,'uploads/qrcodes/employee_7_qr_6e73c41d.png',NULL);
/*!40000 ALTER TABLE `worker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_profile`
--

DROP TABLE IF EXISTS `worker_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`WorkerID`),
  CONSTRAINT `fk_worker_profile_worker` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_profile`
--

LOCK TABLES `worker_profile` WRITE;
/*!40000 ALTER TABLE `worker_profile` DISABLE KEYS */;
INSERT INTO `worker_profile` VALUES (1,'shandypacana456@gmail.com','2026-08-09','zone 8 zayas carmen','Cagayan De Oro city','Mis.Or','9000','Phillippines','r1ndessu','09323348123','Sister','2026-09-07 14:33:12'),(2,'r1ndessulogo@gmail.com','2003-01-07','baragay bulua highway','Cagayan De Oro city','Mis.Or','9000','Phillippines','juliana','09323348123','Sister','2026-09-07 14:41:30'),(3,'johnwick@gmail.com','2000-06-07','Carmen','Cagayan de Oro City','Misamis Oriental','9000','Philippines','97665444671','98885577445','Mother','2026-09-07 14:56:11'),(4,'iche.caluqwqeqnsag.coc@phinmaed.com','2004-02-02','Manila Group Zamora street Wao lanao del sur','Wao','Lanao del Sur','9716','Philippines','Michaella Jane H Calunsag','09063324234','Relative','2026-09-18 03:21:40'),(5,'iche.calunsagrer.coc@phinmaed.com','2007-12-05','Manila Group Zamora street Wao lanao del sur','Wao','Lanao del Sur','9716','Philippines','Hddasdasd sdsdsa','09373426732','Guardian','2026-09-18 03:23:01'),(6,'iche.calunstyryag.coc@phinmaed.com','2008-07-03','carmen','Cagayan De Oro City','Misamis Oriental','9000','Philippines','Shangkoy Pacana','09552352342','Father','2026-09-18 03:24:21');
/*!40000 ALTER TABLE `worker_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workerassignment`
--

DROP TABLE IF EXISTS `workerassignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workerassignment` (
  `AssignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkerID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `Assigned_Date` date NOT NULL,
  `Role_On_Site` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`AssignmentID`),
  UNIQUE KEY `unique_worker_site` (`WorkerID`,`SiteID`),
  KEY `SiteID` (`SiteID`),
  CONSTRAINT `workerassignment_ibfk_1` FOREIGN KEY (`WorkerID`) REFERENCES `worker` (`WorkerID`),
  CONSTRAINT `workerassignment_ibfk_2` FOREIGN KEY (`SiteID`) REFERENCES `projectsite` (`SiteID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workerassignment`
--

LOCK TABLES `workerassignment` WRITE;
/*!40000 ALTER TABLE `workerassignment` DISABLE KEYS */;
INSERT INTO `workerassignment` VALUES (1,1,1,'2026-09-07','Construction Worker'),(2,2,1,'2026-09-07','Laborer'),(3,3,1,'2026-09-07','Carpenter'),(4,4,5,'2026-09-18','Heavy Equipment Operator'),(5,6,5,'2026-09-18','Plumber'),(6,5,5,'2026-09-18','Laborer');
/*!40000 ALTER TABLE `workerassignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workerstatus`
--

DROP TABLE IF EXISTS `workerstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workerstatus` (
  `WorkerStatusID` int(11) NOT NULL AUTO_INCREMENT,
  `Status` enum('Active','OnLeave','Inactive') NOT NULL,
  PRIMARY KEY (`WorkerStatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workerstatus`
--

LOCK TABLES `workerstatus` WRITE;
/*!40000 ALTER TABLE `workerstatus` DISABLE KEYS */;
INSERT INTO `workerstatus` VALUES (1,'Active'),(2,'OnLeave'),(3,'Inactive');
/*!40000 ALTER TABLE `workerstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'payroll_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-21  6:39:07

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: payroll_db
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
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--
-- WHERE:  SiteID=13 AND Date='2026-10-11'

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (90,41,13,'2026-10-11',7.75,0.00,'08:15:00','12:00:00','13:00:00','17:00:00','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4832650,124.6353610,0.00,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg',1),(91,40,13,'2026-10-11',8.00,0.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4832650,124.6353610,0.00,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg',0),(92,42,13,'2026-10-11',8.00,0.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4832650,124.6353610,0.00,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg',0),(93,38,13,'2026-10-11',8.00,0.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4832650,124.6353610,0.00,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg',0),(94,36,13,'2026-10-11',8.00,0.00,'08:00:00','12:00:00','13:00:00','17:00:00','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4832650,124.6353610,0.00,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','uploads/attendance_photos/attendance_42_time_out_1791429589.jpg',0);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-11 17:36:01

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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `attendance`
--
-- WHERE:  SiteID=13

/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (32,42,13,'2026-10-08',0.25,0.00,'10:55:27','11:00:00','11:08:40','11:10:33','Present','uploads/attendance_photos/attendance_42_time_in_1791428128.jpg',8.4820626,124.6379775,0.00,NULL,NULL,NULL,0),(33,36,13,'2026-10-08',0.13,0.00,'10:55:00','11:00:00','11:07:00','11:10:00','Present','uploads/attendance_photos/attendance_36_time_in_1791428169.jpg',8.4820539,124.6379626,0.00,NULL,NULL,NULL,0),(34,41,13,'2026-10-08',0.00,0.00,'00:00:00',NULL,NULL,'00:00:00','Absent','uploads/attendance_photos/attendance_41_time_in_1791428221.jpg',8.4820437,124.6379378,0.00,NULL,NULL,NULL,0),(35,40,13,'2026-10-08',0.00,0.00,'00:00:00',NULL,NULL,'00:00:00','Absent','uploads/attendance_photos/attendance_40_time_in_1791428267.jpg',8.4820555,124.6379526,0.00,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;

--
-- Dumping data for table `attendance_photo_logs`
--
-- WHERE:  SiteID=13

/*!40000 ALTER TABLE `attendance_photo_logs` DISABLE KEYS */;
INSERT INTO `attendance_photo_logs` VALUES (82,32,42,13,72,'Time In','2026-10-08','10:55:27',8.4820626,124.6379775,NULL,'uploads/attendance_photos/attendance_42_time_in_1791428128.jpg','2026-10-08 02:55:28'),(83,33,36,13,72,'Time In','2026-10-08','10:55:47',8.4820539,124.6379626,NULL,'uploads/attendance_photos/attendance_36_time_in_1791428169.jpg','2026-10-08 02:56:09'),(84,34,41,13,72,'Time In','2026-10-08','10:56:58',8.4820437,124.6379378,NULL,'uploads/attendance_photos/attendance_41_time_in_1791428221.jpg','2026-10-08 02:57:01'),(85,35,40,13,72,'Time In','2026-10-08','10:57:45',8.4820555,124.6379526,NULL,'uploads/attendance_photos/attendance_40_time_in_1791428267.jpg','2026-10-08 02:57:47'),(86,33,36,13,72,'Lunch In','2026-10-08','11:07:29',8.4820884,124.6380110,NULL,'uploads/attendance_photos/attendance_36_lunch_in_1791428900.jpg','2026-10-08 03:08:20'),(87,32,42,13,72,'Lunch In','2026-10-08','11:08:40',8.4820588,124.6379791,NULL,'uploads/attendance_photos/attendance_42_lunch_in_1791428927.jpg','2026-10-08 03:08:47'),(88,32,42,13,72,'Time Out','2026-10-08','11:10:33',8.4820430,124.6379521,NULL,'uploads/attendance_photos/attendance_42_time_out_1791429589.jpg','2026-10-08 03:19:49');
/*!40000 ALTER TABLE `attendance_photo_logs` ENABLE KEYS */;

--
-- Dumping data for table `overtime_requests`
--
-- WHERE:  SiteID=13

/*!40000 ALTER TABLE `overtime_requests` DISABLE KEYS */;
INSERT INTO `overtime_requests` VALUES (8,42,13,'2026-10-08','Regular Overtime','12:00:00','13:00:00',1.00,'Submitted via mobile app',72,'Approved',71,'2026-10-08 03:27:21','2026-10-08 03:27:07');
/*!40000 ALTER TABLE `overtime_requests` ENABLE KEYS */;

--
-- Dumping data for table `payroll_records`
--
-- WHERE:  SiteID=13

/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
INSERT INTO `payroll_records` VALUES (6,14,13,'2026-10-08','2026-10-15',24.57,20.00,4.57,'Approved',1,'2026-10-08 03:22:49',1,'2026-10-08 03:22:49',NULL,NULL,NULL,5,0.38,0.00);
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;

--
-- Dumping data for table `payroll_submission_snapshots`
--
-- WHERE:  SiteID=13


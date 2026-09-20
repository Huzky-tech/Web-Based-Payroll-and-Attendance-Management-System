-- Attendance photo evidence and GPS verification fields (Flutter Timekeeper uploads)
ALTER TABLE `attendance`
  ADD COLUMN `PhotoPath` VARCHAR(500) DEFAULT NULL AFTER `AttendanceStatus`,
  ADD COLUMN `Latitude` DECIMAL(10,7) DEFAULT NULL AFTER `PhotoPath`,
  ADD COLUMN `Longitude` DECIMAL(10,7) DEFAULT NULL AFTER `Latitude`,
  ADD COLUMN `DistanceFromSite` DECIMAL(10,2) DEFAULT NULL AFTER `Longitude`;

-- Separate photo paths per punch type (AM Time In, PM Time In, Time Out)
-- Grouped by WorkerID + Date on the attendance row.
ALTER TABLE `attendance`
  ADD COLUMN `AMTimeInPhoto` VARCHAR(500) DEFAULT NULL AFTER `PhotoPath`,
  ADD COLUMN `PMTimeInPhoto` VARCHAR(500) DEFAULT NULL AFTER `AMTimeInPhoto`,
  ADD COLUMN `TimeOutPhoto` VARCHAR(500) DEFAULT NULL AFTER `PMTimeInPhoto`;

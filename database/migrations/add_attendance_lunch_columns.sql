-- Lunch break attendance markers (PM Time In stored as Lunch_In).
ALTER TABLE `attendance`
  ADD COLUMN `Lunch_Out` time DEFAULT NULL AFTER `Time_In`,
  ADD COLUMN `Lunch_In` time DEFAULT NULL AFTER `Lunch_Out`;

-- Timekeeper Reports Module: extend timekeeper_reports and add admin notifications.

CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `NotificationType` varchar(50) NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `idx_admin_notifications_read` (`IsRead`),
  KEY `idx_admin_notifications_type` (`NotificationType`),
  KEY `idx_admin_notifications_ref` (`ReferenceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Extend existing timekeeper_reports table (safe to run once; ignore duplicate column errors if re-run manually).
ALTER TABLE `timekeeper_reports`
  ADD COLUMN `Subject` varchar(255) DEFAULT NULL AFTER `SiteID`,
  ADD COLUMN `ReportType` varchar(100) DEFAULT NULL AFTER `Subject`,
  ADD COLUMN `Description` text DEFAULT NULL AFTER `ReportType`,
  ADD COLUMN `DelayCategory` varchar(100) DEFAULT NULL AFTER `Description`,
  ADD COLUMN `HoursLost` decimal(5,2) DEFAULT NULL AFTER `DelayCategory`,
  ADD COLUMN `WorkersAffected` int(11) DEFAULT NULL AFTER `HoursLost`,
  ADD COLUMN `CauseOfDelay` text DEFAULT NULL AFTER `WorkersAffected`,
  ADD COLUMN `RecommendedAction` text DEFAULT NULL AFTER `CauseOfDelay`,
  ADD COLUMN `AttachmentPath` varchar(500) DEFAULT NULL AFTER `RecommendedAction`,
  ADD COLUMN `AdminRemarks` text DEFAULT NULL AFTER `Status`,
  ADD COLUMN `CreatedAt` datetime DEFAULT NULL AFTER `AdminRemarks`,
  ADD COLUMN `UpdatedAt` datetime DEFAULT NULL AFTER `CreatedAt`;

-- Backfill legacy columns into the new schema where applicable.
UPDATE `timekeeper_reports`
SET
  `ReportType` = COALESCE(NULLIF(`ReportType`, ''), NULLIF(`DelayType`, '')),
  `Description` = COALESCE(NULLIF(`Description`, ''), NULLIF(`AdditionalNotes`, '')),
  `CreatedAt` = COALESCE(`CreatedAt`, CONCAT(`ReportDate`, ' 00:00:00')),
  `UpdatedAt` = COALESCE(`UpdatedAt`, CONCAT(`ReportDate`, ' 00:00:00')),
  `Status` = COALESCE(NULLIF(`Status`, ''), 'Pending')
WHERE `TK_ReportsID` > 0;

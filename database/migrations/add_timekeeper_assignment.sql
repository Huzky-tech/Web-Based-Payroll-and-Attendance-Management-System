-- Timekeeper-to-site assignment table for mobile API scoping.
-- Only one Active assignment per Timekeeper is enforced in application logic.

CREATE TABLE IF NOT EXISTS `timekeeper_assignment` (
  `AssignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `SiteID` int(11) NOT NULL,
  `AssignedDate` date NOT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AssignmentID`),
  KEY `idx_tk_assign_user` (`UserID`),
  KEY `idx_tk_assign_site` (`SiteID`),
  KEY `idx_tk_assign_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adds the HR role. HR shares payroll-staff features but has global site access
-- and is never added to payrollstaffassignment.
CREATE TABLE IF NOT EXISTS `hr` (
  `HR_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`HR_ID`),
  UNIQUE KEY `uq_hr_user` (`UserID`),
  CONSTRAINT `hr_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Extended employee profile fields (run once on payroll_db)
CREATE TABLE IF NOT EXISTS worker_profile (
  WorkerID INT NOT NULL PRIMARY KEY,
  Email VARCHAR(150) NULL,
  DateOfBirth DATE NULL,
  StreetAddress VARCHAR(255) NULL,
  City VARCHAR(100) NULL,
  StateProvince VARCHAR(100) NULL,
  PostalCode VARCHAR(20) NULL,
  Country VARCHAR(100) NULL,
  EmergencyContactName VARCHAR(150) NULL,
  EmergencyContactPhone VARCHAR(30) NULL,
  EmergencyContactRelationship VARCHAR(80) NULL,
  UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_worker_profile_worker FOREIGN KEY (WorkerID) REFERENCES worker(WorkerID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

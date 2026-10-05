START TRANSACTION;

CREATE TABLE IF NOT EXISTS managers (
    ManagerID INT NOT NULL AUTO_INCREMENT,
    UserID INT NOT NULL,
    PRIMARY KEY (ManagerID),
    UNIQUE KEY uq_managers_user (UserID),
    CONSTRAINT fk_managers_user
        FOREIGN KEY (UserID) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO managers (UserID)
SELECT id FROM users WHERE email = 'Rosal@gmail.com' LIMIT 1;

DELETE FROM worker
WHERE UserID = (SELECT id FROM users WHERE email = 'Rosal@gmail.com' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM workerassignment wa WHERE wa.WorkerID = worker.WorkerID)
  AND NOT EXISTS (SELECT 1 FROM attendance a WHERE a.WorkerID = worker.WorkerID)
  AND NOT EXISTS (SELECT 1 FROM payroll p WHERE p.WorkerID = worker.WorkerID);

COMMIT;

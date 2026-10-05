CREATE TABLE IF NOT EXISTS managers (
    ManagerID INT NOT NULL AUTO_INCREMENT,
    UserID INT NOT NULL,
    PRIMARY KEY (ManagerID),
    UNIQUE KEY uq_managers_user (UserID),
    CONSTRAINT fk_managers_user
        FOREIGN KEY (UserID) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

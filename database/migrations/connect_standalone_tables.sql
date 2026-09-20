-- Give singleton configuration tables a legitimate shared parent and add a
-- user recipient relationship to the global/admin notification store.

CREATE TABLE IF NOT EXISTS configuration_registry (
    ConfigurationID TINYINT UNSIGNED NOT NULL,
    ConfigurationName VARCHAR(100) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ConfigurationID),
    UNIQUE KEY uq_configuration_registry_name (ConfigurationName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO configuration_registry (ConfigurationID, ConfigurationName)
VALUES (1, 'Primary System Configuration')
ON DUPLICATE KEY UPDATE ConfigurationName = VALUES(ConfigurationName);

ALTER TABLE company_settings
    ADD COLUMN ConfigurationID TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD KEY idx_company_settings_configuration (ConfigurationID),
    ADD CONSTRAINT fk_company_settings_configuration
        FOREIGN KEY (ConfigurationID) REFERENCES configuration_registry (ConfigurationID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE notification_settings
    ADD COLUMN ConfigurationID TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD KEY idx_notification_settings_configuration (ConfigurationID),
    ADD CONSTRAINT fk_notification_settings_configuration
        FOREIGN KEY (ConfigurationID) REFERENCES configuration_registry (ConfigurationID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE payroll_settings
    ADD COLUMN ConfigurationID TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD KEY idx_payroll_settings_configuration (ConfigurationID),
    ADD CONSTRAINT fk_payroll_settings_configuration
        FOREIGN KEY (ConfigurationID) REFERENCES configuration_registry (ConfigurationID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE security_settings
    ADD COLUMN ConfigurationID TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD KEY idx_security_settings_configuration (ConfigurationID),
    ADD CONSTRAINT fk_security_settings_configuration
        FOREIGN KEY (ConfigurationID) REFERENCES configuration_registry (ConfigurationID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE system_settings
    ADD COLUMN ConfigurationID TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD KEY idx_system_settings_configuration (ConfigurationID),
    ADD CONSTRAINT fk_system_settings_configuration
        FOREIGN KEY (ConfigurationID) REFERENCES configuration_registry (ConfigurationID)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE admin_notifications
    ADD COLUMN RecipientUserID INT NULL AFTER NotificationID,
    ADD KEY idx_admin_notifications_recipient (RecipientUserID),
    ADD CONSTRAINT fk_admin_notifications_recipient
        FOREIGN KEY (RecipientUserID) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE;

UPDATE admin_notifications
SET RecipientUserID = (SELECT UserID FROM admin ORDER BY Admin_ID LIMIT 1)
WHERE RecipientUserID IS NULL;

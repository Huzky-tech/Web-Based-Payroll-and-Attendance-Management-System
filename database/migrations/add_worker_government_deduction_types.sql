-- Stores the individual government deductions selected for each worker.
-- Safe to run on a database where the column is already present.
SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'worker'
      AND COLUMN_NAME = 'GovernmentDeductionTypes'
);
SET @migration_sql := IF(
    @column_exists = 0,
    'ALTER TABLE worker ADD COLUMN GovernmentDeductionTypes TEXT NULL AFTER GovernmentDeductionStatus',
    'SELECT ''GovernmentDeductionTypes already exists'' AS result'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- Payroll approval workflow for site-level payroll batches (payroll_records).
-- Run once against payroll_db.

UPDATE payroll_records
SET Status = 'Pending'
WHERE Status IN ('Processed', 'processed', '') OR Status IS NULL;

ALTER TABLE payroll_records
  MODIFY COLUMN Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending';

ALTER TABLE payroll_records
  ADD COLUMN submitted_by INT NULL AFTER Status;

ALTER TABLE payroll_records
  ADD COLUMN submitted_at DATETIME NULL DEFAULT NULL AFTER submitted_by;

ALTER TABLE payroll_records
  ADD COLUMN approved_by INT NULL AFTER submitted_at;

ALTER TABLE payroll_records
  ADD COLUMN approved_at DATETIME NULL DEFAULT NULL AFTER approved_by;

ALTER TABLE payroll_records
  ADD COLUMN rejected_by INT NULL AFTER approved_at;

ALTER TABLE payroll_records
  ADD COLUMN rejected_at DATETIME NULL DEFAULT NULL AFTER rejected_by;

ALTER TABLE payroll_records
  ADD COLUMN rejection_reason TEXT NULL AFTER rejected_at;

ALTER TABLE payroll_records
  ADD COLUMN worker_count INT NOT NULL DEFAULT 0 AFTER rejection_reason;

ALTER TABLE payroll_records
  ADD COLUMN regular_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER worker_count;

ALTER TABLE payroll_records
  ADD COLUMN overtime_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER regular_hours;

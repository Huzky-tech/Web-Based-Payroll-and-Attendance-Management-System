-- Per-worker government deduction eligibility for payroll computation.

ALTER TABLE `worker`
  ADD COLUMN `GovernmentDeductionStatus` enum('With Deductions','No Deductions') NOT NULL DEFAULT 'With Deductions'
  AFTER `RateAmount`;

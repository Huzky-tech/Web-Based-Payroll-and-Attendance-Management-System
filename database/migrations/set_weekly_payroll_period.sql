-- Weekly is the only supported payroll period.
ALTER TABLE payroll_settings
    MODIFY pay_periods VARCHAR(50) DEFAULT 'Weekly';

UPDATE payroll_settings
SET pay_periods = 'Weekly';

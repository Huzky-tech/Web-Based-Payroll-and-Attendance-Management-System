-- Fixed government deduction amounts used by payroll.
-- Application code also enforces these values, so they cannot be changed from Settings.
UPDATE payroll_settings
SET sss_rate = 950.00,
    philhealth_rate = 262.50,
    pagibig_rate = 200.00;

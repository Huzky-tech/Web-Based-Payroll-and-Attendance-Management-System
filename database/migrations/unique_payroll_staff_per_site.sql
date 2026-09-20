-- One site can have only one Payroll Staff assignment.
-- A Payroll Staff member may still be assigned to any number of sites.
ALTER TABLE payrollstaffassignment
    ADD UNIQUE KEY uq_payrollstaffassignment_site (SiteID);

-- Fix approvals.WorkerID foreign key.
-- The incorrect constraint named `WorkerID` points approvals.WorkerID to users.id.
-- approvals.WorkerID should only reference worker.WorkerID.

ALTER TABLE approvals
    DROP FOREIGN KEY `WorkerID`;

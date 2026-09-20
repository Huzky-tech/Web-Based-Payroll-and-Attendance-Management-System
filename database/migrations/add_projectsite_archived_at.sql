-- Stores when a project site was archived (soft archive; no related data deleted).
ALTER TABLE `projectsite`
  ADD COLUMN `Archived_At` datetime DEFAULT NULL AFTER `Status`;

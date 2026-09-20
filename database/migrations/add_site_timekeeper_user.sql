-- Links an active site to a Timekeeper user account (users.id via timekeeper table).
ALTER TABLE `projectsite`
  ADD COLUMN `Timekeeper_UserID` int(11) DEFAULT NULL AFTER `Site_Manager`,
  ADD KEY `idx_projectsite_timekeeper_user` (`Timekeeper_UserID`);

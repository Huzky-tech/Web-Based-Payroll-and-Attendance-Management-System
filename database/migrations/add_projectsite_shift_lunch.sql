-- Official work schedule per site (shift + lunch break).
ALTER TABLE `projectsite`
  ADD COLUMN `ShiftStart` time DEFAULT NULL AFTER `Status`,
  ADD COLUMN `LunchStart` time DEFAULT NULL AFTER `ShiftStart`,
  ADD COLUMN `LunchEnd` time DEFAULT NULL AFTER `LunchStart`,
  ADD COLUMN `ShiftEnd` time DEFAULT NULL AFTER `LunchEnd`;

-- Backfill from existing site_schedule where possible.
UPDATE `projectsite` ps
INNER JOIN (
    SELECT SiteID, MIN(Site_ScheduleID) AS FirstScheduleID
    FROM site_schedule
    GROUP BY SiteID
) first_sched ON first_sched.SiteID = ps.SiteID
INNER JOIN site_schedule ss ON ss.Site_ScheduleID = first_sched.FirstScheduleID
SET
    ps.ShiftStart = COALESCE(ps.ShiftStart, ss.ShiftStart),
    ps.ShiftEnd = COALESCE(ps.ShiftEnd, ss.ShiftEnd),
    ps.LunchStart = COALESCE(ps.LunchStart, '12:00:00'),
    ps.LunchEnd = COALESCE(ps.LunchEnd, '13:00:00')
WHERE ps.ShiftStart IS NULL OR ps.ShiftEnd IS NULL;

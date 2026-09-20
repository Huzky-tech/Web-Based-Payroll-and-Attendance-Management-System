-- Location lock / geofence fields for site attendance validation
ALTER TABLE `projectsite`
  ADD COLUMN `Geofence_Radius_M` DECIMAL(10,2) DEFAULT NULL AFTER `Coordinates`,
  ADD COLUMN `Geofence_Lat` DECIMAL(10,7) DEFAULT NULL AFTER `Geofence_Radius_M`,
  ADD COLUMN `Geofence_Lng` DECIMAL(10,7) DEFAULT NULL AFTER `Geofence_Lat`;

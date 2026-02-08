<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

$siteName = $data['siteName'];
$location = $data['location'];
$requiredWorkers = $data['requiredWorkers'];
$startDate = $data['startDate'];
$siteManager = $data['siteManager'];
$status = $data['status'];
$locationID = 1; // Default location ID

$sql = "INSERT INTO ProjectSite (Site_Name, Location, Required_Workers, Start_Date, Site_Manager, Status, LocationID)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisssi", $siteName, $location, $requiredWorkers, $startDate, $siteManager, $status, $locationID);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$conn->close();
?>

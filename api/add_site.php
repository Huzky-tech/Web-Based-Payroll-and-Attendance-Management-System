<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

$siteName = $data['siteName'];
$location = $data['location'];
$startDate = $data['startDate'];
$requiredWorkers = $data['requiredWorkers'];
$siteManager = $data['siteManager'];
$status = $data['status'];

$sql = "INSERT INTO ProjectSite (Site_Name, Location, Start_Date, Required_Workers, Site_Manager, Status)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiss", $siteName, $location, $startDate, $requiredWorkers, $siteManager, $status);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$conn->close();
?>

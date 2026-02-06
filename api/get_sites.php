<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $sql = "SELECT 
                s.SiteID,
                s.Site_Name,
                s.Location,
                s.Start_Date,
                s.End_Date,
                s.Required_Workers,
                s.Site_Manager,
                ls.Status,
                (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = s.SiteID) AS currentWorkers
            FROM ProjectSite s
            LEFT JOIN LocationStatus ls ON s.LocationID = ls.LocationID
            ORDER BY s.Site_Name";

    $result = $conn->query($sql);

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = $row;
    }

    echo json_encode($sites);
    exit;
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $siteName = $data['siteName'];
    $location = $data['location'];
    $startDate = $data['startDate'];
    $requiredWorkers = $data['requiredWorkers'];
    $siteManager = $data['siteManager'];

    $sql = "INSERT INTO ProjectSite (Site_Name, Location, Start_Date, Required_Workers, Site_Manager)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $siteName, $location, $startDate, $requiredWorkers, $siteManager);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit;
}
?>

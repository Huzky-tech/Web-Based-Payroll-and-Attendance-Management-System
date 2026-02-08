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
                s.Status,
                (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = s.SiteID) AS Current_Workers
            FROM ProjectSite s
            ORDER BY s.Site_Name";

    $result = $conn->query($sql);

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = $row;
    }

    echo json_encode($sites);
    exit;
}


?>

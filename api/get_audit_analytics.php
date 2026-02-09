<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$response = [
    'trends' => [],
    'actionDistribution' => [],
    'topUsers' => []
];

/* =========================
   Activity Trends (Last 7 Days)
========================= */
$sql = "
    SELECT DATE(`Date`) as log_date, COUNT(*) as count
    FROM Audit_logs
    WHERE `Date` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(`Date`)
";

$result = $conn->query($sql);

$trendMap = [];
while ($row = $result->fetch_assoc()) {
    $trendMap[$row['log_date']] = (int)$row['count'];
}

// Fill missing days with 0
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $response['trends'][] = [
        'day' => date('D', strtotime($date)), // Mon, Tue, etc.
        'count' => $trendMap[$date] ?? 0
    ];
}

/* =========================
   Action Distribution (Last 7 Days)
========================= */
$sql = "
    SELECT COALESCE(Action, 'Unknown') as action, COUNT(*) as count
    FROM Audit_logs
    WHERE `Date` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY Action
    ORDER BY count DESC
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $response['actionDistribution'][] = [
        'action' => $row['action'],
        'count' => (int)$row['count']
    ];
}

/* =========================
   Top Active Users (Last 7 Days)
========================= */
$sql = "
    SELECT
        COALESCE(u.email, 'System') as user,
        COUNT(a.Audit_logsID) as actions
    FROM Audit_logs a
    LEFT JOIN users u ON a.UserID = u.id
    WHERE a.`Date` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY a.UserID
    ORDER BY actions DESC
    LIMIT 4
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $response['topUsers'][] = [
        'user' => $row['user'],
        'actions' => (int)$row['actions']
    ];
}

$conn->close();
echo json_encode($response);
?>

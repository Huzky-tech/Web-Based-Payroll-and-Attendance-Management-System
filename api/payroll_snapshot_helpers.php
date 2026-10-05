<?php

function payroll_snapshot_ensure_table(mysqli $conn): void
{
    if (!$conn->query("CREATE TABLE IF NOT EXISTS payroll_submission_snapshots (
        Payroll_RecordsID INT NOT NULL PRIMARY KEY,
        SnapshotData LONGTEXT NOT NULL,
        CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        throw new RuntimeException('Unable to create payroll snapshot storage.');
    }
}

function payroll_snapshot_save(mysqli $conn, int $recordId, array $snapshot): void
{
    $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare('INSERT INTO payroll_submission_snapshots (Payroll_RecordsID, SnapshotData) VALUES (?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare payroll snapshot.');
    }
    $stmt->bind_param('is', $recordId, $json);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to save payroll snapshot.');
    }
    $stmt->close();
}

function payroll_snapshot_load(mysqli $conn, int $recordId): ?array
{
    try {
        $stmt = $conn->prepare('SELECT SnapshotData FROM payroll_submission_snapshots WHERE Payroll_RecordsID = ?');
    } catch (mysqli_sql_exception $error) {
        if ((int) $error->getCode() === 1146) {
            return null;
        }
        throw $error;
    }
    if (!$stmt) {
        if ($conn->errno === 1146) {
            return null;
        }
        throw new RuntimeException('Unable to read payroll snapshot.');
    }
    $stmt->bind_param('i', $recordId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to read payroll snapshot.');
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    $snapshot = json_decode($row['SnapshotData'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($snapshot) || !isset($snapshot['workers'], $snapshot['attendance'], $snapshot['settings'])) {
        throw new RuntimeException('Payroll snapshot is incomplete.');
    }
    return $snapshot;
}

function payroll_snapshot_attendance(mysqli $conn, int $siteId, string $start, string $end): array
{
    $stmt = $conn->prepare('SELECT WorkerID, Date, Time_In, Time_Out, Hours_Worked, AttendanceStatus FROM attendance WHERE SiteID = ? AND Date BETWEEN ? AND ? ORDER BY Date');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare payroll attendance snapshot.');
    }
    $stmt->bind_param('iss', $siteId, $start, $end);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to capture payroll attendance.');
    }
    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[(int) $row['WorkerID']][$row['Date']] = $row;
    }
    $stmt->close();
    return $rows;
}

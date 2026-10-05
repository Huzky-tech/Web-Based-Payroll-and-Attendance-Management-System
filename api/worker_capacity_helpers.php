<?php

// Caller must hold a transaction until its assignment insert/update completes.
function worker_assignment_require_capacity(mysqli $conn, int $siteId): void
{
    $stmt = $conn->prepare('SELECT Required_Workers FROM projectsite WHERE SiteID = ? FOR UPDATE');
    if (!$stmt) {
        throw new RuntimeException('Unable to check site capacity.');
    }
    $stmt->bind_param('i', $siteId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to check site capacity.');
    }
    $site = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$site) {
        throw new RuntimeException('Site not found.');
    }

    // A locking read sees the latest committed assignments after waiting for
    // another request's site lock, including under REPEATABLE READ.
    $stmt = $conn->prepare('SELECT AssignmentID FROM workerassignment WHERE SiteID = ? FOR UPDATE');
    if (!$stmt) {
        throw new RuntimeException('Unable to count site workers.');
    }
    $stmt->bind_param('i', $siteId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to count site workers.');
    }
    $count = $stmt->get_result()->num_rows;
    $stmt->close();
    $capacity = max(0, (int) $site['Required_Workers']);
    if ($count >= $capacity) {
        throw new RuntimeException("This site has reached its capacity of {$capacity} workers. Unassign a worker before adding another.");
    }
}

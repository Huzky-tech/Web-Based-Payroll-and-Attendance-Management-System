<?php
require_once __DIR__ . '/../api/worker_capacity_helpers.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'payroll_db', (int) (getenv('DB_PORT') ?: 3306));
// Temporary tables shadow application tables on this connection only.
$conn->query('CREATE TEMPORARY TABLE projectsite (SiteID INT PRIMARY KEY, Required_Workers INT) ENGINE=InnoDB');
$conn->query('CREATE TEMPORARY TABLE workerassignment (AssignmentID INT PRIMARY KEY AUTO_INCREMENT, SiteID INT) ENGINE=InnoDB');
$conn->query('INSERT INTO projectsite VALUES (1, 2), (2, 0)');

function expectCapacity(mysqli $conn, int $siteId, bool $allowed): void
{
    $conn->begin_transaction();
    try {
        worker_assignment_require_capacity($conn, $siteId);
        $actual = true;
    } catch (RuntimeException $error) {
        $actual = false;
    } finally {
        $conn->rollback();
    }
    if ($actual !== $allowed) {
        throw new RuntimeException("Unexpected capacity result for site {$siteId}");
    }
}

expectCapacity($conn, 1, true);
$conn->query('INSERT INTO workerassignment (SiteID) VALUES (1)');
expectCapacity($conn, 1, true);
$conn->query('INSERT INTO workerassignment (SiteID) VALUES (1)');
expectCapacity($conn, 1, false);
$conn->query('INSERT INTO workerassignment (SiteID) VALUES (1)');
expectCapacity($conn, 1, false);
$conn->query('DELETE FROM workerassignment WHERE SiteID = 1 LIMIT 2');
expectCapacity($conn, 1, true);
expectCapacity($conn, 2, false);
expectCapacity($conn, 999, false);
echo "Passed: empty, below capacity, full, over capacity, removal frees capacity, zero capacity, missing site.\n";

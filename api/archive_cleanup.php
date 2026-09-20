<?php
/**
 * Archive cleanup API
 * Deletes archived records older than retention period.
 * - Removes/cleans data/archive_items.json entries older than the configured retention period.
 * - Removes projectsite records (and related assignments/logs where possible) older than the retention period.

 * Admin only.
 */

header('Content-Type: application/json');

include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';

require_auth($conn, ['Admin']);

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$settingsResult = $conn->query('SELECT data_retention_days FROM system_settings WHERE id = 1 LIMIT 1');
$settingsRow = $settingsResult ? $settingsResult->fetch_assoc() : null;
$retentionDays = max(1, (int) ($settingsRow['data_retention_days'] ?? 365));

// Optional manual override, while retaining compatibility with the former
// years-only cleanup request.
if (isset($payload['days']) && (int) $payload['days'] > 0) {
    $retentionDays = (int) $payload['days'];
} elseif (isset($payload['years']) && (int) $payload['years'] > 0) {
    $retentionDays = (int) $payload['years'] * 365;
}

$retentionDate = (new DateTime('now'))->modify('-' . $retentionDays . ' days');
$retentionTimestamp = $retentionDate->getTimestamp();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$actor = 'Admin User';

$results = [
    'success' => false,
    'message' => 'Not executed',
    'retention_days' => $retentionDays,
    'cutoff_iso' => $retentionDate->format('c'),
    'file_cleanup' => ['deleted_items' => 0],
    'db_cleanup' => ['deleted_sites' => 0]
];

// ---- Cleanup file archive_items.json ----
$archiveFile = __DIR__ . '/../data/archive_items.json';
$deletedFileCount = 0;

if (file_exists($archiveFile)) {
    $contents = file_get_contents($archiveFile);
    $fileItems = json_decode($contents, true);

    if (is_array($fileItems)) {
        $kept = [];
        foreach ($fileItems as $item) {
            // $type is not used for filtering; retention is based on archived/original dates.
            // $type = strtolower((string)($item['type'] ?? ''));

            // Prefer the most accurate fields first.
            $dateRaw = $item['archived_at'] ?? ($item['archived_date'] ?? ($item['date_archived'] ?? null));

            // If archived_* is missing/invalid, fall back to original_date.
            if (!$dateRaw) {
                $dateRaw = $item['original_date'] ?? null;
            }

            $ts = null;
            if ($dateRaw) {
                $raw = trim((string)$dateRaw);

                // Handle ISO-like formats first.
                $ts = strtotime($raw);

                // If strtotime fails, try m/d/Y, d/m/Y, and n/j/Y (already covered by strtotime in most cases,
                // but keep explicit patterns to reduce dependency on hardcoded formatting).
                if ($ts === false || $ts === null) {
                    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m)) {
                        $a = (int)$m[1];
                        $b = (int)$m[2];
                        $y = (int)$m[3];

                        // Try as m/d/Y
                        $ts1 = strtotime(sprintf('%04d-%02d-%02d', $y, $a, $b));
                        // Try as d/m/Y
                        $ts2 = strtotime(sprintf('%04d-%02d-%02d', $y, $b, $a));

                        $ts = ($ts1 !== false && $ts1 !== null) ? $ts1 : (($ts2 !== false && $ts2 !== null) ? $ts2 : null);
                    }
                }
            }

            // If we still can't parse the date, keep the item.
            if ($ts === null || $ts === false) {
                $kept[] = $item;
                continue;
            }


            if ($ts < $retentionTimestamp) {
                $deletedFileCount++;
                continue;
            }

            $kept[] = $item;
        }

        $encoded = json_encode(array_values($kept), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded !== false && file_put_contents($archiveFile, $encoded . PHP_EOL, LOCK_EX) !== false) {
            $results['file_cleanup']['deleted_items'] = $deletedFileCount;
        }
    }
}

// ---- Cleanup DB archived sites older than retention ----
// projectsite.Status is expected to be 'Archived' or 'Inactive' but archived_at varies.
$deletedSites = 0;

$hasArchivedAt = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Archived_At'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasArchivedAt = true;
}

try {
    if ($hasArchivedAt) {
        // Prefer Archived_At if present, else use End_Date/Start_Date heuristics.
        // Delete only if status is archived/inactive and archived_at (or end_date) older than cutoff.
        $sql = "
            SELECT SiteID
            FROM projectsite
            WHERE LOWER(COALESCE(Status,'')) IN ('archived','inactive')
              AND (
                    (Archived_At IS NOT NULL AND UNIX_TIMESTAMP(Archived_At) < ?) OR
                    (Archived_At IS NULL AND End_Date IS NOT NULL AND UNIX_TIMESTAMP(End_Date) < ?)
              )
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $retentionTimestamp, $retentionTimestamp);
            $stmt->execute();
            $res = $stmt->get_result();

            $siteIds = [];
            while ($row = $res->fetch_assoc()) {
                $siteIds[] = (int)$row['SiteID'];
            }

            $stmt->close();

            if ($siteIds) {
                // NOTE: schema-specific cleanup. We'll try to remove assignments; if tables don't exist, errors are ignored.
                foreach ($siteIds as $siteId) {
                    try { $conn->query("DELETE FROM WorkerAssignment WHERE SiteID = " . (int)$siteId); } catch (Throwable $e) {}
                    try { $conn->query("DELETE FROM attendance WHERE SiteID = " . (int)$siteId); } catch (Throwable $e) {}
                    try { $conn->query("DELETE FROM overtime_requests WHERE SiteID = " . (int)$siteId); } catch (Throwable $e) {}
                    try { $conn->query("DELETE FROM overtime WHERE SiteID = " . (int)$siteId); } catch (Throwable $e) {}
                    try { $conn->query("DELETE FROM payroll_records WHERE SiteID = " . (int)$siteId); } catch (Throwable $e) {}

                    $ok = $conn->query("DELETE FROM projectsite WHERE SiteID = " . (int)$siteId);
                    if ($ok) {
                        $deletedSites++;
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    // keep results as-is
}

$results['db_cleanup']['deleted_sites'] = $deletedSites;

record_audit_log($userId, 'Archive Cleanup', $actor . ' cleaned archived records older than ' . $retentionDays . ' days');

$results['success'] = true;
$results['message'] = 'Cleanup completed';

echo json_encode($results);


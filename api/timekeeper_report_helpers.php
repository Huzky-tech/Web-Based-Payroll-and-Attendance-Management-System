<?php

require_once __DIR__ . '/site_schedule_helpers.php';

if (!function_exists('timekeeper_report_valid_types')) {
    function timekeeper_report_valid_types(): array
    {
        return [
            'Daily Accomplishment Report',
            'Site Delay',
            'Worker Absence',
            'Worker Late Arrival',
            'Equipment Breakdown',
            'Material Shortage',
            'Weather Delay',
            'Safety Incident',
            'Overtime Concern',
            'Other',
        ];
    }
}

if (!function_exists('timekeeper_report_table_exists')) {
    function timekeeper_report_table_exists(mysqli $conn): bool
    {
        $result = $conn->query("SHOW TABLES LIKE 'timekeeper_reports'");
        return $result !== false && $result->num_rows > 0;
    }
}

if (!function_exists('timekeeper_report_ensure_table')) {
    function timekeeper_report_ensure_table(mysqli $conn): void
    {
        $conn->query("
            CREATE TABLE IF NOT EXISTS timekeeper_reports (
                ReportID INT AUTO_INCREMENT PRIMARY KEY,
                TimekeeperID INT NOT NULL,
                SiteID INT NOT NULL,
                SiteName VARCHAR(255) NOT NULL DEFAULT '',
                ReportType VARCHAR(120) NOT NULL,
                Subject VARCHAR(255) NOT NULL,
                Description TEXT NOT NULL,
                ReportDate DATE NOT NULL,
                PhotoPath VARCHAR(500) NULL,
                Status VARCHAR(40) NOT NULL DEFAULT 'Submitted',
                CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}

/**
 * Admin/web schema bootstrap — also used by mobile endpoints for compatibility.
 */
if (!function_exists('tk_report_ensure_schema')) {
    function tk_report_ensure_schema(mysqli $conn): void
    {
        if (!timekeeper_report_table_exists($conn)) {
            timekeeper_report_ensure_table($conn);
            return;
        }

        $required = [
            'TimekeeperID' => "INT NOT NULL DEFAULT 0",
            'SiteID' => "INT NOT NULL DEFAULT 0",
            'SiteName' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'ReportType' => "VARCHAR(120) NOT NULL DEFAULT ''",
            'Subject' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'Description' => "TEXT NOT NULL",
            'ReportDate' => "DATE NULL",
            'PhotoPath' => "VARCHAR(500) NULL",
            'Status' => "VARCHAR(40) NOT NULL DEFAULT 'Submitted'",
            'CreatedAt' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'timekeeper_id' => "INT NOT NULL DEFAULT 0",
            'site_id' => "INT NOT NULL DEFAULT 0",
            'site_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'report_type' => "VARCHAR(120) NOT NULL DEFAULT ''",
            'subject' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'description' => "TEXT NULL",
            'report_date' => "DATE NULL",
            'photo_path' => "VARCHAR(500) NULL",
            'attachment_path' => "VARCHAR(500) NULL",
            'status' => "VARCHAR(40) NOT NULL DEFAULT 'Submitted'",
            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'notes' => "TEXT NULL",
            'delay_type' => "VARCHAR(120) NULL",
        ];

        $adminColumns = [
            'Subject' => "VARCHAR(255) DEFAULT NULL",
            'ReportType' => "VARCHAR(100) DEFAULT NULL",
            'Description' => "TEXT DEFAULT NULL",
            'DelayCategory' => "VARCHAR(100) DEFAULT NULL",
            'HoursLost' => "DECIMAL(5,2) DEFAULT NULL",
            'WorkersAffected' => "INT DEFAULT NULL",
            'CauseOfDelay' => "TEXT DEFAULT NULL",
            'RecommendedAction' => "TEXT DEFAULT NULL",
            'AttachmentPath' => "VARCHAR(500) DEFAULT NULL",
            'AdminRemarks' => "TEXT DEFAULT NULL",
            'UpdatedAt' => "DATETIME DEFAULT NULL",
        ];

        timekeeper_report_add_missing_columns($conn, array_merge($required, $adminColumns));
        timekeeper_report_normalize_storage_columns($conn);

        $conn->query("
            CREATE TABLE IF NOT EXISTS admin_notifications (
                NotificationID INT AUTO_INCREMENT PRIMARY KEY,
                RecipientUserID INT DEFAULT NULL,
                NotificationType VARCHAR(50) NOT NULL,
                ReferenceID INT DEFAULT NULL,
                Title VARCHAR(255) NOT NULL,
                Message TEXT NOT NULL,
                IsRead TINYINT(1) NOT NULL DEFAULT 0,
                CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_notifications_read (IsRead),
                KEY idx_admin_notifications_type (NotificationType),
                KEY idx_admin_notifications_ref (ReferenceID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $recipientColumn = $conn->query("SHOW COLUMNS FROM admin_notifications LIKE 'RecipientUserID'");
        if ($recipientColumn && $recipientColumn->num_rows === 0) {
            $conn->query('ALTER TABLE admin_notifications ADD COLUMN RecipientUserID INT NULL AFTER NotificationID');
        }
    }
}

if (!function_exists('timekeeper_report_last_error')) {
    function timekeeper_report_last_error(): string
    {
        return $GLOBALS['timekeeper_report_last_db_error'] ?? '';
    }
}

if (!function_exists('timekeeper_report_set_last_error')) {
    function timekeeper_report_set_last_error(string $message): void
    {
        $GLOBALS['timekeeper_report_last_db_error'] = $message;
    }
}

if (!function_exists('timekeeper_report_normalize_storage_columns')) {
    function timekeeper_report_normalize_storage_columns(mysqli $conn): void
    {
        $result = $conn->query('SHOW FULL COLUMNS FROM timekeeper_reports');
        if ($result === false) {
            return;
        }

        $widen = [
            'reporttype',
            'delaytype',
            'report_type',
            'delay_type',
            'status',
        ];

        while ($row = $result->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            $type = strtolower((string) ($row['Type'] ?? ''));
            if ($field === '' || strpos($type, 'enum') === false) {
                continue;
            }

            $fieldLower = strtolower($field);
            if (!in_array($fieldLower, $widen, true)) {
                continue;
            }

            $length = $fieldLower === 'status' ? 40 : 120;
            $conn->query(
                "ALTER TABLE timekeeper_reports MODIFY COLUMN `{$field}` VARCHAR({$length}) NOT NULL DEFAULT ''"
            );
        }
    }
}

if (!function_exists('timekeeper_report_add_missing_columns')) {
    function timekeeper_report_add_missing_columns(mysqli $conn, array $columns): void
    {
        $existing = timekeeper_report_table_columns($conn);
        foreach ($columns as $column => $definition) {
            if (isset($existing[strtolower($column)])) {
                continue;
            }
            $conn->query("ALTER TABLE timekeeper_reports ADD COLUMN {$column} {$definition}");
        }
    }
}

if (!function_exists('timekeeper_report_table_columns')) {
    function timekeeper_report_table_columns(mysqli $conn): array
    {
        $columns = [];
        $result = $conn->query('SHOW COLUMNS FROM timekeeper_reports');
        if ($result === false) {
            return $columns;
        }

        while ($row = $result->fetch_assoc()) {
            $name = (string) ($row['Field'] ?? '');
            if ($name !== '') {
                $columns[strtolower($name)] = $name;
            }
        }

        return $columns;
    }
}

if (!function_exists('timekeeper_report_resolve_column')) {
    function timekeeper_report_resolve_column(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);
            if (isset($columns[$key])) {
                return $columns[$key];
            }
        }

        return null;
    }
}

if (!function_exists('timekeeper_report_schema_map')) {
    function timekeeper_report_schema_map(mysqli $conn): array
    {
        if (function_exists('tk_report_ensure_schema')) {
            tk_report_ensure_schema($conn);
        } elseif (!timekeeper_report_table_exists($conn)) {
            timekeeper_report_ensure_table($conn);
        }
        timekeeper_report_normalize_storage_columns($conn);
        $columns = timekeeper_report_table_columns($conn);

        $map = [
            'id' => timekeeper_report_resolve_column($columns, ['TK_ReportsID', 'ReportID', 'report_id', 'id']),
            'user_id' => timekeeper_report_resolve_column($columns, ['UserID', 'user_id']),
            'timekeeper_id' => timekeeper_report_resolve_column($columns, ['TimekeeperID', 'timekeeper_id', 'UserID', 'user_id', 'SubmittedBy', 'submitted_by']),
            'site_id' => timekeeper_report_resolve_column($columns, ['SiteID', 'site_id']),
            'site_name' => timekeeper_report_resolve_column($columns, ['SiteName', 'site_name']),
            'report_type' => timekeeper_report_resolve_column($columns, ['ReportType', 'report_type', 'DelayType', 'delay_type']),
            'subject' => timekeeper_report_resolve_column($columns, ['Subject', 'subject', 'Title', 'title']),
            'description' => timekeeper_report_resolve_column($columns, ['Description', 'description', 'Notes', 'notes', 'AdditionalNotes', 'additional_notes']),
            'report_date' => timekeeper_report_resolve_column($columns, ['ReportDate', 'report_date', 'Date', 'date']),
            'photo_path' => timekeeper_report_resolve_column($columns, ['PhotoPath', 'photo_path', 'AttachmentPath', 'attachment_path']),
            'status' => timekeeper_report_resolve_column($columns, ['Status', 'status']),
            'created_at' => timekeeper_report_resolve_column($columns, ['CreatedAt', 'created_at', 'SubmittedAt', 'submitted_at']),
        ];

        foreach (['timekeeper_id', 'site_id', 'description', 'report_date'] as $required) {
            if ($map[$required] === null) {
                timekeeper_report_set_last_error("Missing required column: {$required}");
                return [];
            }
        }

        if ($map['report_type'] === null) {
            timekeeper_report_set_last_error('Missing report type column (ReportType/report_type/delay_type).');
            return [];
        }

        return $map;
    }
}

if (!function_exists('timekeeper_report_insert_row')) {
    function timekeeper_report_insert_row(mysqli $conn, array $data): int
    {
        timekeeper_report_set_last_error('');
        $map = timekeeper_report_schema_map($conn);
        if ($map === []) {
            if (timekeeper_report_last_error() === '') {
                timekeeper_report_set_last_error('Unable to map timekeeper_reports columns.');
            }
            return 0;
        }

        $columns = [];
        $placeholders = [];
        $values = [];
        $types = '';

        $fieldMap = [
            'user_id' => [(int) ($data['timekeeper_id'] ?? 0), 'i'],
            'timekeeper_id' => [(int) ($data['timekeeper_id'] ?? 0), 'i'],
            'site_id' => [(int) ($data['site_id'] ?? 0), 'i'],
            'site_name' => [(string) ($data['site_name'] ?? ''), 's'],
            'report_type' => [(string) ($data['report_type'] ?? ''), 's'],
            'subject' => [(string) ($data['subject'] ?? ''), 's'],
            'description' => [(string) ($data['description'] ?? ''), 's'],
            'report_date' => [(string) ($data['report_date'] ?? ''), 's'],
            'photo_path' => [(string) ($data['photo_path'] ?? ''), 's'],
            'status' => [(string) ($data['status'] ?? 'Pending'), 's'],
            'created_at' => [date('Y-m-d H:i:s'), 's'],
        ];

        foreach ($fieldMap as $logical => [$value, $type]) {
            $column = $map[$logical] ?? null;
            if ($column === null) {
                continue;
            }
            if ($logical === 'photo_path' && $value === '') {
                continue;
            }
            if ($logical === 'site_name' && $value === '') {
                continue;
            }
            if ($logical === 'subject' && $value === '') {
                continue;
            }

            $columns[] = '`' . str_replace('`', '', $column) . '`';
            $placeholders[] = '?';
            $values[] = $value;
            $types .= $type;
        }

        if ($columns === []) {
            timekeeper_report_set_last_error('No insertable columns resolved for timekeeper_reports.');
            return 0;
        }

        $sql = 'INSERT INTO timekeeper_reports (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            timekeeper_report_set_last_error($conn->error !== '' ? $conn->error : 'Failed to prepare insert statement.');
            return 0;
        }

        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            timekeeper_report_set_last_error($stmt->error !== '' ? $stmt->error : 'Failed to execute insert statement.');
            $stmt->close();
            return 0;
        }

        $insertId = (int) $stmt->insert_id;
        $stmt->close();
        return $insertId;
    }
}

if (!function_exists('timekeeper_report_update_photo_path')) {
    function timekeeper_report_update_photo_path(mysqli $conn, int $reportId, string $photoPath): bool
    {
        $map = timekeeper_report_schema_map($conn);
        $idColumn = $map['id'] ?? null;
        $photoColumn = $map['photo_path'] ?? null;
        if ($idColumn === null || $photoColumn === null) {
            return false;
        }

        $sql = "UPDATE timekeeper_reports SET {$photoColumn} = ? WHERE {$idColumn} = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $photoPath, $reportId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('timekeeper_report_fetch_for_timekeeper')) {
    function timekeeper_report_fetch_for_timekeeper(mysqli $conn, int $siteId, int $timekeeperId): array
    {
        $map = timekeeper_report_schema_map($conn);
        if ($map === []) {
            return [];
        }

        $selectParts = [];
        foreach ($map as $alias => $column) {
            if ($column !== null) {
                $selectParts[] = "{$column} AS {$alias}";
            }
        }

        $siteColumn = $map['site_id'];
        $timekeeperColumn = $map['timekeeper_id'];
        $userColumn = $map['user_id'] ?? null;
        $idColumn = $map['id'] ?? 'TK_ReportsID';

        if ($userColumn !== null && $userColumn !== $timekeeperColumn) {
            $sql = 'SELECT ' . implode(', ', $selectParts)
                . " FROM timekeeper_reports WHERE {$siteColumn} = ?"
                . " AND ({$timekeeperColumn} = ? OR {$userColumn} = ?)"
                . " ORDER BY {$idColumn} DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param('iii', $siteId, $timekeeperId, $timekeeperId);
        } else {
            $sql = 'SELECT ' . implode(', ', $selectParts)
                . " FROM timekeeper_reports WHERE {$siteColumn} = ? AND {$timekeeperColumn} = ?"
                . " ORDER BY {$idColumn} DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param('ii', $siteId, $timekeeperId);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }
}

if (!function_exists('timekeeper_report_normalize_type')) {
    function timekeeper_report_normalize_type(string $raw): string
    {
        $raw = trim($raw);
        foreach (timekeeper_report_valid_types() as $type) {
            if (strcasecmp($raw, $type) === 0) {
                return $type;
            }
        }

        $legacy = [
            'weather condition' => 'Weather Delay',
            'broken equipment' => 'Equipment Breakdown',
        ];
        $key = strtolower($raw);
        if (isset($legacy[$key])) {
            return $legacy[$key];
        }

        return '';
    }
}

if (!function_exists('timekeeper_report_save_photo')) {
    function timekeeper_report_save_photo(?string $photoBase64, int $reportId): ?string
    {
        if ($photoBase64 === null || trim($photoBase64) === '') {
            return null;
        }

        $decoded = base64_decode($photoBase64, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $uploadDir = __DIR__ . '/../uploads/timekeeper_reports';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'report_' . $reportId . '_' . time() . '.jpg';
        $fullPath = $uploadDir . '/' . $filename;
        if (!file_put_contents($fullPath, $decoded)) {
            return null;
        }

        return 'uploads/timekeeper_reports/' . $filename;
    }
}

if (!function_exists('timekeeper_report_format_row')) {
    function timekeeper_report_format_row(array $row): array
    {
        $id = (int) ($row['id'] ?? $row['TK_ReportsID'] ?? $row['ReportID'] ?? $row['report_id'] ?? 0);
        $reportDateRaw = (string) ($row['report_date'] ?? $row['ReportDate'] ?? $row['date'] ?? '');
        $createdRaw = (string) ($row['created_at'] ?? $row['CreatedAt'] ?? $row['submitted_at'] ?? '');

        return [
            'id' => $id,
            'report_id' => $id,
            'timekeeper_id' => (int) ($row['timekeeper_id'] ?? $row['TimekeeperID'] ?? 0),
            'site_id' => (int) ($row['site_id'] ?? $row['SiteID'] ?? 0),
            'site_name' => (string) ($row['site_name'] ?? $row['SiteName'] ?? ''),
            'report_type' => (string) ($row['report_type'] ?? $row['ReportType'] ?? $row['delay_type'] ?? ''),
            'subject' => (string) ($row['subject'] ?? $row['Subject'] ?? ''),
            'description' => (string) ($row['description'] ?? $row['Description'] ?? $row['notes'] ?? ''),
            'report_date' => $reportDateRaw !== '' && strtotime($reportDateRaw) !== false
                ? date('m/d/Y', strtotime($reportDateRaw))
                : '',
            'photo_path' => (string) ($row['photo_path'] ?? $row['PhotoPath'] ?? $row['attachment_path'] ?? ''),
            'status' => (string) ($row['status'] ?? $row['Status'] ?? 'Submitted'),
            'created_at' => $createdRaw,
            'submitted_at' => $createdRaw,
        ];
    }
}

if (!function_exists('tk_report_valid_types')) {
    function tk_report_valid_types(): array
    {
        return timekeeper_report_valid_types();
    }
}

if (!function_exists('tk_report_valid_statuses')) {
    function tk_report_valid_statuses(): array
    {
        return ['Pending', 'Reviewed', 'Resolved'];
    }
}

if (!function_exists('tk_report_delay_categories')) {
    function tk_report_delay_categories(): array
    {
        return [
            'Weather',
            'Equipment Failure',
            'Material Shortage',
            'Manpower Issue',
            'Permit / Approval Delay',
            'Safety Concern',
            'Site Access Issue',
            'Other',
        ];
    }
}

if (!function_exists('tk_report_type_sql')) {
    function tk_report_type_sql(): string
    {
        return "COALESCE(NULLIF(tr.ReportType, ''), NULLIF(tr.DelayType, ''), NULLIF(tr.report_type, ''), NULLIF(tr.delay_type, ''), 'Site Report')";
    }
}

if (!function_exists('tk_report_description_sql')) {
    function tk_report_description_sql(): string
    {
        return "COALESCE(NULLIF(tr.Description, ''), NULLIF(tr.AdditionalNotes, ''), NULLIF(tr.description, ''), NULLIF(tr.notes, ''), '')";
    }
}

if (!function_exists('tk_report_created_at_sql')) {
    function tk_report_created_at_sql(): string
    {
        return "COALESCE(tr.CreatedAt, tr.created_at, CONCAT(tr.ReportDate, ' 00:00:00'), CONCAT(tr.report_date, ' 00:00:00'))";
    }
}

if (!function_exists('tk_report_column_exists')) {
    function tk_report_column_exists(mysqli $conn, string $column): bool
    {
        $columns = timekeeper_report_table_columns($conn);
        return isset($columns[strtolower($column)]);
    }
}

if (!function_exists('tk_report_table_exists')) {
    function tk_report_table_exists(mysqli $conn): bool
    {
        if (!timekeeper_report_table_exists($conn)) {
            return false;
        }

        return tk_report_column_exists($conn, 'TK_ReportsID')
            || tk_report_column_exists($conn, 'ReportID');
    }
}

if (!function_exists('tk_report_format_row')) {
    function tk_report_format_row(array $row): array
    {
        if (!isset($row['report_type_raw']) && !isset($row['timekeeper_name'])) {
            return timekeeper_report_format_row($row);
        }

        $reportType = trim((string) ($row['report_type_raw'] ?? $row['ReportType'] ?? $row['DelayType'] ?? 'Site Report'));
        $description = trim((string) ($row['description_raw'] ?? $row['Description'] ?? $row['AdditionalNotes'] ?? ''));
        $createdRaw = trim((string) ($row['created_at_raw'] ?? $row['CreatedAt'] ?? ''));
        if ($createdRaw === '' && !empty($row['ReportDate'])) {
            $createdRaw = (string) $row['ReportDate'] . ' 00:00:00';
        }

        $status = trim((string) ($row['Status'] ?? 'Pending'));
        if ($status === '') {
            $status = 'Pending';
        }

        $createdTimestamp = $createdRaw !== '' ? strtotime($createdRaw) : false;
        $dateSubmitted = $createdTimestamp ? date('m/d/Y', $createdTimestamp) : '';
        $timeSubmitted = $createdTimestamp ? date('h:i A', $createdTimestamp) : '';
        $isNew = $status === 'Pending'
            && $createdTimestamp
            && date('Y-m-d', $createdTimestamp) === date('Y-m-d');

        $attachmentPath = trim((string) ($row['AttachmentPath'] ?? $row['PhotoPath'] ?? $row['attachment_path'] ?? ''));
        $attachmentUrl = '';
        if ($attachmentPath !== '') {
            $attachmentUrl = (strpos($attachmentPath, '../') === 0 || strpos($attachmentPath, 'http') === 0)
                ? $attachmentPath
                : '../' . ltrim($attachmentPath, '/');
        }

        $reportId = (int) ($row['TK_ReportsID'] ?? $row['ReportID'] ?? $row['report_id'] ?? 0);

        return [
            'id' => $reportId,
            'report_id' => $reportId,
            'user_id' => (int) ($row['UserID'] ?? $row['TimekeeperID'] ?? $row['timekeeper_id'] ?? 0),
            'site_id' => (int) ($row['SiteID'] ?? $row['site_id'] ?? 0),
            'site_name' => (string) ($row['Site_Name'] ?? $row['SiteName'] ?? $row['site_name'] ?? ''),
            'site_location' => (string) ($row['Location'] ?? ''),
            'timekeeper_name' => (string) ($row['timekeeper_name'] ?? 'Unknown Timekeeper'),
            'timekeeper_email' => (string) ($row['timekeeper_email'] ?? ''),
            'report_type' => $reportType,
            'subject' => trim((string) ($row['Subject'] ?? $row['subject'] ?? '')),
            'description' => $description,
            'status' => $status,
            'date_submitted' => $dateSubmitted,
            'time_submitted' => $timeSubmitted,
            'is_today' => (bool) ($createdTimestamp && date('Y-m-d', $createdTimestamp) === date('Y-m-d')),
            'is_new' => $isNew,
            'is_site_delay' => strcasecmp($reportType, 'Site Delay') === 0,
            'delay_category' => trim((string) ($row['DelayCategory'] ?? '')),
            'hours_lost' => isset($row['HoursLost']) && $row['HoursLost'] !== null && $row['HoursLost'] !== ''
                ? (float) $row['HoursLost']
                : null,
            'workers_affected' => isset($row['WorkersAffected']) && $row['WorkersAffected'] !== null && $row['WorkersAffected'] !== ''
                ? (int) $row['WorkersAffected']
                : null,
            'cause_of_delay' => trim((string) ($row['CauseOfDelay'] ?? '')),
            'recommended_action' => trim((string) ($row['RecommendedAction'] ?? '')),
            'admin_remarks' => trim((string) ($row['AdminRemarks'] ?? '')),
            'attachment_path' => $attachmentPath,
            'attachment_url' => $attachmentUrl,
            'report_date' => !empty($row['ReportDate'])
                ? date('m/d/Y', strtotime((string) $row['ReportDate']))
                : $dateSubmitted,
            'created_at' => $createdRaw,
        ];
    }
}

if (!function_exists('tk_report_get_stats')) {
    function tk_report_get_stats(mysqli $conn, string $whereSql, string $types = '', array $params = []): array
    {
        $createdSql = tk_report_created_at_sql();
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN COALESCE(NULLIF(tr.Status, ''), 'Pending') = 'Pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN COALESCE(NULLIF(tr.Status, ''), 'Pending') = 'Reviewed' THEN 1 ELSE 0 END) AS reviewed,
                SUM(CASE WHEN COALESCE(NULLIF(tr.Status, ''), 'Pending') = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
                SUM(CASE WHEN DATE({$createdSql}) = CURDATE() THEN 1 ELSE 0 END) AS submitted_today
            FROM timekeeper_reports tr
            INNER JOIN projectsite ps ON ps.SiteID = tr.SiteID
            INNER JOIN users u ON u.id = tr.UserID
            WHERE {$whereSql}
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [
                'total' => 0,
                'pending' => 0,
                'reviewed' => 0,
                'resolved' => 0,
                'submitted_today' => 0,
            ];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'reviewed' => (int) ($row['reviewed'] ?? 0),
            'resolved' => (int) ($row['resolved'] ?? 0),
            'submitted_today' => (int) ($row['submitted_today'] ?? 0),
        ];
    }
}

if (!function_exists('tk_report_validate_timekeeper_site')) {
    function tk_report_validate_timekeeper_site(mysqli $conn, int $timekeeperId, int $siteId): ?string
    {
        require_once __DIR__ . '/timekeeper_assignment_helpers.php';

        if ($timekeeperId <= 0 || $siteId <= 0) {
            return 'Invalid timekeeper or site.';
        }

        $siteIds = get_timekeeper_site_ids($conn, $timekeeperId);
        if (!in_array($siteId, $siteIds, true)) {
            return 'Access denied for this site.';
        }

        return null;
    }
}

if (!function_exists('tk_report_save_attachment')) {
    function tk_report_save_attachment(?string $photoBase64, ?string $filename = null): ?string
    {
        if ($photoBase64 === null || trim($photoBase64) === '') {
            return null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64) === 1) {
            $photoBase64 = substr($photoBase64, (int) strpos($photoBase64, ',') + 1);
        }

        $decoded = base64_decode($photoBase64, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $extension = 'jpg';
        if (is_string($filename) && preg_match('/\.([A-Za-z0-9]+)$/', $filename, $matches) === 1) {
            $extension = strtolower($matches[1]);
        }

        $uploadDir = __DIR__ . '/../uploads/timekeeper_reports';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $storedName = 'report_' . uniqid('', true) . '.' . $extension;
        $fullPath = $uploadDir . '/' . $storedName;
        if (!file_put_contents($fullPath, $decoded)) {
            return null;
        }

        return 'uploads/timekeeper_reports/' . $storedName;
    }
}

if (!function_exists('tk_report_create_admin_notification')) {
    function tk_report_create_admin_notification(
        mysqli $conn,
        int $reportId,
        string $reportType,
        string $subject,
        string $siteName,
        string $timekeeperName
    ): void {
        tk_report_ensure_schema($conn);

        $title = 'New Timekeeper Report: ' . $reportType;
        $message = trim($timekeeperName) . ' submitted "' . trim($subject) . '" for ' . trim($siteName) . '.';
        $notificationType = 'timekeeper_report';

        $stmt = $conn->prepare("
            INSERT INTO admin_notifications (NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('siss', $notificationType, $reportId, $title, $message);
        $stmt->execute();
        $stmt->close();
    }
}

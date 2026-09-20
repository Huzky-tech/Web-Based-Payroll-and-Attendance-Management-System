<?php

function payroll_approval_columns_ready(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $requiredColumns = [
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'worker_count',
        'regular_hours',
        'overtime_hours',
    ];

    foreach ($requiredColumns as $column) {
        $escapedColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM payroll_records LIKE '{$escapedColumn}'");
        if (!$result || $result->num_rows === 0) {
            $ready = false;
            return $ready;
        }
    }

    $ready = true;
    return $ready;
}


function payroll_approval_normalize_status(?string $status): string
{
    $normalized = trim((string) $status);
    if ($normalized === '' || strcasecmp($normalized, 'Processed') === 0) {
        return 'Pending';
    }

    if (strcasecmp($normalized, 'Approved') === 0) {
        return 'Approved';
    }

    if (strcasecmp($normalized, 'Rejected') === 0) {
        return 'Rejected';
    }

    if (strcasecmp($normalized, 'Pending') === 0) {
        return 'Pending';
    }

    return 'Pending';
}

function payroll_approval_format_period(?string $start, ?string $end): string
{
    if (!$start || !$end) {
        return '-';
    }

    $startDate = strtotime($start);
    $endDate = strtotime($end);
    if (!$startDate || !$endDate) {
        return '-';
    }

    if (date('Y', $startDate) === date('Y', $endDate)) {
        return date('M j', $startDate) . ' - ' . date('M j, Y', $endDate);
    }

    return date('M j, Y', $startDate) . ' - ' . date('M j, Y', $endDate);
}

function payroll_approval_get_summary_counts(mysqli $conn, ?int $submittedBy = null): array
{
    $counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total' => 0,
    ];

    $sql = "
        SELECT Status, COUNT(*) AS total
        FROM payroll_records
    ";
    $params = [];
    $types = '';

    if ($submittedBy !== null && $submittedBy > 0 && payroll_approval_columns_ready($conn)) {
        $sql .= " WHERE submitted_by = ?";
        $types = 'i';
        $params[] = $submittedBy;
    }

    $sql .= " GROUP BY Status";

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $counts;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = payroll_approval_normalize_status($row['Status'] ?? 'Pending');
            $total = (int) ($row['total'] ?? 0);
            $counts['total'] += $total;

            if ($status === 'Approved') {
                $counts['approved'] += $total;
            } elseif ($status === 'Rejected') {
                $counts['rejected'] += $total;
            } else {
                $counts['pending'] += $total;
            }
        }
    }

    return $counts;
}

function payroll_approval_map_record(array $row): array
{
    $status = payroll_approval_normalize_status($row['Status'] ?? 'Pending');
    $periodLabel = payroll_approval_format_period($row['Period_start'] ?? null, $row['Period_end'] ?? null);

    return [
        'id' => (int) ($row['Payroll_RecordsID'] ?? 0),
        'site_id' => (int) ($row['SiteID'] ?? 0),
        'site_name' => (string) ($row['Site_Name'] ?? ''),
        'period_start' => (string) ($row['Period_start'] ?? ''),
        'period_end' => (string) ($row['Period_end'] ?? ''),
        'period_label' => $periodLabel,
        'worker_count' => (int) ($row['worker_count'] ?? 0),
        'regular_hours' => round((float) ($row['regular_hours'] ?? 0), 2),
        'overtime_hours' => round((float) ($row['overtime_hours'] ?? 0), 2),
        'total_gross_pay' => round((float) ($row['Total_gross_pay'] ?? 0), 2),
        'total_deductions' => round((float) ($row['Total_deductions'] ?? 0), 2),
        'total_net_pay' => round((float) ($row['Total_net_pay'] ?? 0), 2),
        'status' => $status,
        'submitted_by' => (int) ($row['submitted_by'] ?? 0),
        'submitted_by_name' => (string) ($row['submitted_by_name'] ?? 'Unknown'),
        'submitted_at' => (string) ($row['submitted_at'] ?? ''),
        'submitted_at_label' => !empty($row['submitted_at'])
            ? date('M j, Y g:i A', strtotime((string) $row['submitted_at']))
            : '',
        'approved_by' => (int) ($row['approved_by'] ?? 0),
        'approved_by_name' => (string) ($row['approved_by_name'] ?? ''),
        'approved_at' => (string) ($row['approved_at'] ?? ''),
        'approved_at_label' => !empty($row['approved_at'])
            ? date('M j, Y g:i A', strtotime((string) $row['approved_at']))
            : '',
        'rejected_by' => (int) ($row['rejected_by'] ?? 0),
        'rejected_by_name' => (string) ($row['rejected_by_name'] ?? ''),
        'rejected_at' => (string) ($row['rejected_at'] ?? ''),
        'rejected_at_label' => !empty($row['rejected_at'])
            ? date('M j, Y g:i A', strtotime((string) $row['rejected_at']))
            : '',
        'rejection_reason' => (string) ($row['rejection_reason'] ?? ''),
        'can_approve' => $status === 'Pending',
        'can_reject' => $status === 'Pending',
    ];
}

function payroll_approval_fetch_records(
    mysqli $conn,
    string $role,
    int $userId,
    string $statusFilter = '',
    string $search = ''
): array {
    $hasWorkflowColumns = payroll_approval_columns_ready($conn);
    $submittedByFilter = null;

    if (in_array($role, ['Payroll Staff', 'HR'], true) && $hasWorkflowColumns) {
        $submittedByFilter = $userId;
    }

    $selectExtras = $hasWorkflowColumns
        ? "
            pr.submitted_by,
            pr.submitted_at,
            pr.approved_by,
            pr.approved_at,
            pr.rejected_by,
            pr.rejected_at,
            pr.rejection_reason,
            pr.worker_count,
            pr.regular_hours,
            pr.overtime_hours,
            COALESCE(submitter.full_name, submitter.email, 'Unknown') AS submitted_by_name,
            COALESCE(approver.full_name, approver.email, '') AS approved_by_name,
            COALESCE(rejector.full_name, rejector.email, '') AS rejected_by_name
        "
        : "
            0 AS submitted_by,
            NULL AS submitted_at,
            NULL AS approved_by,
            NULL AS approved_at,
            NULL AS rejected_by,
            NULL AS rejected_at,
            NULL AS rejection_reason,
            0 AS worker_count,
            0 AS regular_hours,
            0 AS overtime_hours,
            'Unknown' AS submitted_by_name,
            '' AS approved_by_name,
            '' AS rejected_by_name
        ";

    $joinExtras = $hasWorkflowColumns
        ? "
            LEFT JOIN users submitter ON submitter.id = pr.submitted_by
            LEFT JOIN users approver ON approver.id = pr.approved_by
            LEFT JOIN users rejector ON rejector.id = pr.rejected_by
        "
        : '';

    $sql = "
        SELECT
            pr.Payroll_RecordsID,
            pr.SiteID,
            pr.Period_start,
            pr.Period_end,
            pr.Total_gross_pay,
            pr.Total_deductions,
            pr.Total_net_pay,
            pr.Status,
            ps.Site_Name,
            {$selectExtras}
        FROM payroll_records pr
        INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
        {$joinExtras}
        WHERE 1 = 1
    ";

    $params = [];
    $types = '';

    if ($submittedByFilter !== null) {
        $sql .= " AND pr.submitted_by = ?";
        $types .= 'i';
        $params[] = $submittedByFilter;
    }

    if ($statusFilter !== '' && in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
        if ($statusFilter === 'Pending') {
            $sql .= " AND (LOWER(pr.Status) = 'pending' OR LOWER(pr.Status) = 'processed' OR pr.Status = '' OR pr.Status IS NULL)";
        } else {
            $sql .= " AND LOWER(pr.Status) = LOWER(?)";
            $types .= 's';
            $params[] = $statusFilter;
        }
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        if ($hasWorkflowColumns) {
            $sql .= " AND (
                ps.Site_Name LIKE ?
                OR COALESCE(submitter.full_name, submitter.email, '') LIKE ?
                OR DATE_FORMAT(pr.Period_start, '%b %d, %Y') LIKE ?
                OR DATE_FORMAT(pr.Period_end, '%b %d, %Y') LIKE ?
                OR CONCAT(DATE_FORMAT(pr.Period_start, '%b %d'), ' - ', DATE_FORMAT(pr.Period_end, '%b %d, %Y')) LIKE ?
            )";
            $types .= 'sssss';
            array_push($params, $like, $like, $like, $like, $like);
        } else {
            $sql .= " AND (
                ps.Site_Name LIKE ?
                OR DATE_FORMAT(pr.Period_start, '%b %d, %Y') LIKE ?
                OR DATE_FORMAT(pr.Period_end, '%b %d, %Y') LIKE ?
                OR CONCAT(DATE_FORMAT(pr.Period_start, '%b %d'), ' - ', DATE_FORMAT(pr.Period_end, '%b %d, %Y')) LIKE ?
            )";
            $types .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
    }

    $sql .= $hasWorkflowColumns
        ? " ORDER BY COALESCE(pr.submitted_at, pr.Period_end) DESC, pr.Payroll_RecordsID DESC"
        : " ORDER BY pr.Period_end DESC, pr.Payroll_RecordsID DESC";

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }

    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = payroll_approval_map_record($row);
        }
    }

    return $items;
}

function payroll_approval_fetch_record_by_id(mysqli $conn, int $recordId): ?array
{
    if ($recordId <= 0) {
        return null;
    }

    $hasWorkflowColumns = payroll_approval_columns_ready($conn);
    $selectExtras = $hasWorkflowColumns
        ? "
            pr.submitted_by,
            pr.submitted_at,
            pr.approved_by,
            pr.approved_at,
            pr.rejected_by,
            pr.rejected_at,
            pr.rejection_reason,
            pr.worker_count,
            pr.regular_hours,
            pr.overtime_hours,
            COALESCE(submitter.full_name, submitter.email, 'Unknown') AS submitted_by_name,
            COALESCE(approver.full_name, approver.email, '') AS approved_by_name,
            COALESCE(rejector.full_name, rejector.email, '') AS rejected_by_name
        "
        : "
            0 AS submitted_by,
            NULL AS submitted_at,
            NULL AS approved_by,
            NULL AS approved_at,
            NULL AS rejected_by,
            NULL AS rejected_at,
            NULL AS rejection_reason,
            0 AS worker_count,
            0 AS regular_hours,
            0 AS overtime_hours,
            'Unknown' AS submitted_by_name,
            '' AS approved_by_name,
            '' AS rejected_by_name
        ";

    $joinExtras = $hasWorkflowColumns
        ? "
            LEFT JOIN users submitter ON submitter.id = pr.submitted_by
            LEFT JOIN users approver ON approver.id = pr.approved_by
            LEFT JOIN users rejector ON rejector.id = pr.rejected_by
        "
        : '';

    $sql = "
        SELECT
            pr.Payroll_RecordsID,
            pr.SiteID,
            pr.Period_start,
            pr.Period_end,
            pr.Total_gross_pay,
            pr.Total_deductions,
            pr.Total_net_pay,
            pr.Status,
            ps.Site_Name,
            {$selectExtras}
        FROM payroll_records pr
        INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
        {$joinExtras}
        WHERE pr.Payroll_RecordsID = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $recordId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? payroll_approval_map_record($row) : null;
}

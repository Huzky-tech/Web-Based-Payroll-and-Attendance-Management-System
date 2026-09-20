<?php
require_once __DIR__ . '/../includes/user_identity.php';
/**
 * Sample data generator for capstone demonstrations.
 * Inserts demo sites, workers, attendance, overtime, reports, and payroll.
 * Never updates or deletes existing unrelated records.
 */

require_once __DIR__ . '/site_schedule_helpers.php';
require_once __DIR__ . '/payroll_deduction_helpers.php';
require_once __DIR__ . '/payroll_approval_helpers.php';
require_once __DIR__ . '/overtime_helpers.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';
require_once __DIR__ . '/attendance_schema_helpers.php';

if (!function_exists('sample_data_constants')) {
    function sample_data_constants(): array
    {
        return [
            'site_names' => [
                'Bakery Construction Site',
                'Commercial Building Project',
                'Warehouse Expansion Project',
            ],
            'site_locations' => [
                'Corner Masterson Avenue, Cagayan de Oro City',
                'Limketkai Drive, Cagayan de Oro City',
                'Bulua Industrial Zone, Cagayan de Oro City',
            ],
            'site_managers' => [
                'Engr. Ramon Villareal',
                'Engr. Patricia Mendoza',
                'Engr. Gilbert Navarro',
            ],
            'timekeepers' => [
                ['full_name' => 'Rico Almazan', 'email' => 'sample.tk.bakery@capstone.demo'],
                ['full_name' => 'Theresa Villar', 'email' => 'sample.tk.commercial@capstone.demo'],
                ['full_name' => 'Marissa Santos', 'email' => 'sample.tk.warehouse@capstone.demo'],
            ],
            'positions' => ['Laborer', 'Mason', 'Carpenter', 'Electrician', 'Plumber', 'Foreman'],
            'position_rates' => [
                'Laborer' => 450.00,
                'Mason' => 500.00,
                'Carpenter' => 520.00,
                'Electrician' => 550.00,
                'Plumber' => 540.00,
                'Foreman' => 650.00,
            ],
            'workers' => [
                ['Juan', 'Dela Cruz'], ['Maria', 'Santos'], ['Mark', 'Reyes'], ['Ana', 'Bautista'],
                ['Carlo', 'Mendoza'], ['Lorna', 'Villanueva'], ['Roberto', 'Aquino'], ['Elena', 'Ramos'],
                ['Miguel', 'Torres'], ['Grace', 'Fernandez'], ['Paolo', 'Navarro'], ['Christine', 'Lopez'],
                ['Anthony', 'Cruz'], ['Sofia', 'Garcia'], ['Rafael', 'Domingo'], ['Patricia', 'Morales'],
                ['Jerome', 'Castillo'], ['Hannah', 'Rivera'], ['Dominic', 'Sy'], ['Isabelle', 'Tan'],
                ['Kenneth', 'Go'], ['Angela', 'Chu'], ['Francis', 'Lim'], ['Nicole', 'Ong'],
                ['Bryan', 'Te'], ['Katrina', 'Co'], ['Oliver', 'Yap'], ['Michelle', 'Ho'],
                ['Adrian', 'King'], ['Bianca', 'Wu'],
            ],
            'phone_prefix' => '09991000',
            'default_password' => 'SampleData1!',
            'payroll_staff_email' => 'sample.payroll@capstone.demo',
            'payroll_staff_name' => 'Sample Payroll Staff',
        ];
    }
}

if (!function_exists('sample_data_is_dev_environment')) {
    function sample_data_is_dev_environment(mysqli $conn): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost:')) {
            return true;
        }

        $result = $conn->query('SELECT debug_mode, server_environment FROM system_settings WHERE id = 1 LIMIT 1');
        if (!$result) {
            return false;
        }

        $row = $result->fetch_assoc();
        $result->close();

        if ((int) ($row['debug_mode'] ?? 0) === 1) {
            return true;
        }

        $environment = strtolower(trim((string) ($row['server_environment'] ?? '')));
        return in_array($environment, ['development', 'dev', 'local', 'testing'], true);
    }
}

if (!function_exists('sample_data_already_seeded')) {
    function sample_data_already_seeded(mysqli $conn): bool
    {
        $constants = sample_data_constants();
        $siteNames = $constants['site_names'];
        $placeholders = implode(',', array_fill(0, count($siteNames), '?'));
        $types = str_repeat('s', count($siteNames));

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM projectsite WHERE Site_Name IN ({$placeholders})");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$siteNames);
        $stmt->execute();
        $siteCount = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        $phonePrefix = $constants['phone_prefix'];
        $workerStmt = $conn->prepare('SELECT COUNT(*) AS total FROM worker WHERE Phone LIKE ?');
        if (!$workerStmt) {
            return false;
        }

        $likePhone = $phonePrefix . '%';
        $workerStmt->bind_param('s', $likePhone);
        $workerStmt->execute();
        $workerCount = (int) ($workerStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $workerStmt->close();

        return $siteCount >= count($siteNames) && $workerCount >= count($constants['workers']);
    }
}

if (!function_exists('sample_data_fetch_scalar')) {
    function sample_data_fetch_scalar(mysqli $conn, string $sql, string $types = '', array $params = [])
    {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if ($types !== '' && $params !== []) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();

        return $row[0] ?? null;
    }
}

if (!function_exists('sample_data_get_user_id_by_email')) {
    function sample_data_get_user_id_by_email(mysqli $conn, string $email): int
    {
        $id = sample_data_fetch_scalar($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
        return (int) ($id ?? 0);
    }
}

if (!function_exists('sample_data_get_or_create_user')) {
    function sample_data_get_or_create_user(mysqli $conn, string $fullName, string $email, string $password): int
    {
        $existingId = sample_data_get_user_id_by_email($conn, $email);
        if ($existingId > 0) {
            return $existingId;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, status) VALUES (?, ?, ?, 'Active')");
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare user insert.');
        }

        $stmt->bind_param('sss', $fullName, $email, $hash);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to create user: ' . $email);
        }

        $userId = (int) $stmt->insert_id;
        $stmt->close();

        return $userId;
    }
}

if (!function_exists('sample_data_link_role')) {
    function sample_data_link_role(mysqli $conn, string $role, int $userId): void
    {
        switch ($role) {
            case 'Timekeeper':
                $exists = sample_data_fetch_scalar($conn, 'SELECT Timekeeper_ID FROM timekeeper WHERE UserID = ? LIMIT 1', 'i', [$userId]);
                if ($exists) {
                    return;
                }
                $stmt = $conn->prepare('INSERT INTO timekeeper (UserID) VALUES (?)');
                break;
            case 'Payroll Staff':
                $exists = sample_data_fetch_scalar($conn, 'SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1', 'i', [$userId]);
                if ($exists) {
                    return;
                }
                $stmt = $conn->prepare('INSERT INTO payrollstaff (UserID) VALUES (?)');
                break;
            default:
                return;
        }

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare role link for ' . $role);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('sample_data_get_payroll_staff_id')) {
    function sample_data_get_payroll_staff_id(mysqli $conn, int $userId): int
    {
        $id = sample_data_fetch_scalar($conn, 'SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1', 'i', [$userId]);
        return (int) ($id ?? 0);
    }
}

if (!function_exists('sample_data_get_or_create_site')) {
    function sample_data_get_or_create_site(
        mysqli $conn,
        string $siteName,
        string $location,
        string $siteManager,
        int $timekeeperUserId
    ): int {
        $existingId = sample_data_fetch_scalar(
            $conn,
            'SELECT SiteID FROM projectsite WHERE Site_Name = ? LIMIT 1',
            's',
            [$siteName]
        );
        if ($existingId) {
            return (int) $existingId;
        }

        $startDate = date('Y-m-d', strtotime('-6 months'));
        $locationId = (int) (sample_data_fetch_scalar($conn, 'SELECT LocationID FROM locationstatus LIMIT 1') ?? 1);
        $status = 'Active';

        $stmt = $conn->prepare("
            INSERT INTO projectsite (
                Site_Name, Location, Start_Date, LocationID, Required_Workers,
                Site_Manager, Status, ShiftStart, LunchStart, LunchEnd, ShiftEnd, Timekeeper_UserID
            ) VALUES (?, ?, ?, ?, 10, ?, ?, '07:00:00', '12:00:00', '13:00:00', '17:00:00', ?)
        ");
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare site insert.');
        }

        $stmt->bind_param('ssisssi', $siteName, $location, $startDate, $locationId, $siteManager, $status, $timekeeperUserId);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Failed to create site: ' . $error);
        }

        $siteId = (int) $stmt->insert_id;
        $stmt->close();

        return $siteId;
    }
}

if (!function_exists('sample_data_assign_timekeeper')) {
    function sample_data_assign_timekeeper(mysqli $conn, int $timekeeperUserId, int $siteId): void
    {
        $exists = sample_data_fetch_scalar(
            $conn,
            'SELECT AssignmentID FROM timekeeper_assignment WHERE UserID = ? AND SiteID = ? LIMIT 1',
            'ii',
            [$timekeeperUserId, $siteId]
        );
        if ($exists) {
            return;
        }

        $assignedDate = date('Y-m-d');
        $status = 'Active';
        $stmt = $conn->prepare('
            INSERT INTO timekeeper_assignment (UserID, SiteID, AssignedDate, Status)
            VALUES (?, ?, ?, ?)
        ');
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare timekeeper assignment insert.');
        }

        $stmt->bind_param('iiss', $timekeeperUserId, $siteId, $assignedDate, $status);
        $stmt->execute();
        $stmt->close();

        $updateSite = $conn->prepare('UPDATE projectsite SET Timekeeper_UserID = ? WHERE SiteID = ? LIMIT 1');
        if ($updateSite) {
            $updateSite->bind_param('ii', $timekeeperUserId, $siteId);
            $updateSite->execute();
            $updateSite->close();
        }
    }
}

if (!function_exists('sample_data_assign_payroll_staff')) {
    function sample_data_assign_payroll_staff(mysqli $conn, int $payrollStaffId, int $siteId): void
    {
        $exists = sample_data_fetch_scalar(
            $conn,
            'SELECT staffAssignID FROM payrollstaffassignment WHERE PayrollStaff_ID = ? AND SiteID = ? LIMIT 1',
            'ii',
            [$payrollStaffId, $siteId]
        );
        if ($exists) {
            return;
        }

        $stmt = $conn->prepare('INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) VALUES (?, ?, NOW())');
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare payroll staff assignment insert.');
        }

        $stmt->bind_param('ii', $payrollStaffId, $siteId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('sample_data_get_or_create_worker')) {
    function sample_data_get_or_create_worker(
        mysqli $conn,
        string $firstName,
        string $lastName,
        string $phone,
        string $position,
        float $rate
    ): int {
        $existingId = sample_data_fetch_scalar($conn, 'SELECT WorkerID FROM worker WHERE Phone = ? LIMIT 1', 's', [$phone]);
        if ($existingId) {
            return (int) $existingId;
        }

        $workerStatusId = (int) (sample_data_fetch_scalar($conn, "SELECT WorkerStatusID FROM workerstatus WHERE Status = 'Active' LIMIT 1") ?? 1);
        $rateType = 'Hourly';
        $dateHired = date('Y-m-d', strtotime('-' . (90 + (crc32($phone) % 300)) . ' days'));

        $hasGovColumn = worker_government_deduction_column_exists($conn);
        if ($hasGovColumn) {
            $govStatus = (crc32($phone) % 10) === 0 ? 'No Deductions' : 'With Deductions';
            $stmt = $conn->prepare("
                INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, GovernmentDeductionStatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new RuntimeException('Failed to prepare worker insert.');
            }
            $stmt->bind_param('sssdsiss', $firstName, $lastName, $rateType, $rate, $phone, $dateHired, $workerStatusId, $govStatus);
        } else {
            $stmt = $conn->prepare('
                INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            if (!$stmt) {
                throw new RuntimeException('Failed to prepare worker insert.');
            }
            $stmt->bind_param('sssdsi', $firstName, $lastName, $rateType, $rate, $phone, $dateHired, $workerStatusId);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Failed to create worker: ' . $error);
        }

        $workerId = (int) $stmt->insert_id;
        $stmt->close();

        return $workerId;
    }
}

if (!function_exists('sample_data_assign_worker')) {
    function sample_data_assign_worker(mysqli $conn, int $workerId, int $siteId, string $position): void
    {
        $exists = sample_data_fetch_scalar(
            $conn,
            'SELECT AssignmentID FROM workerassignment WHERE WorkerID = ? AND SiteID = ? LIMIT 1',
            'ii',
            [$workerId, $siteId]
        );
        if ($exists) {
            return;
        }

        $otherAssignment = sample_data_fetch_scalar(
            $conn,
            'SELECT AssignmentID FROM workerassignment WHERE WorkerID = ? LIMIT 1',
            'i',
            [$workerId]
        );
        if ($otherAssignment) {
            return;
        }

        $assignedDate = date('Y-m-d', strtotime('-3 months'));
        $stmt = $conn->prepare('
            INSERT INTO workerassignment (WorkerID, SiteID, Assigned_Date, Role_On_Site)
            VALUES (?, ?, ?, ?)
        ');
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare worker assignment insert.');
        }

        $stmt->bind_param('iiss', $workerId, $siteId, $assignedDate, $position);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('sample_data_attendance_bucket')) {
    function sample_data_attendance_bucket(int $workerId, string $date): string
    {
        $roll = crc32($workerId . '|' . $date) % 100;
        if ($roll < 5) {
            return 'Absent';
        }
        if ($roll < 20) {
            return 'Late';
        }

        return 'Present';
    }
}

if (!function_exists('sample_data_attendance_times')) {
    function sample_data_attendance_times(string $status, int $workerId, string $date): ?array
    {
        if ($status === 'Absent') {
            return null;
        }

        $offset = crc32($workerId . '|' . $date . '|time') % 8;

        if ($status === 'Late') {
            return [
                'time_in' => sprintf('08:%02d:00', 15 + $offset),
                'lunch_out' => '12:00:00',
                'lunch_in' => sprintf('13:%02d:00', 3 + ($offset % 4)),
                'time_out' => sprintf('17:%02d:00', $offset % 5),
                'status' => 'Late',
            ];
        }

        return [
            'time_in' => sprintf('07:%02d:00', 50 + ($offset % 8)),
            'lunch_out' => '12:00:00',
            'lunch_in' => sprintf('13:%02d:00', $offset % 6),
            'time_out' => sprintf('17:%02d:00', 1 + ($offset % 6)),
            'status' => 'Present',
        ];
    }
}

if (!function_exists('sample_data_seed_attendance')) {
    function sample_data_seed_attendance(mysqli $conn, array $workerSiteMap, string $periodStart, string $periodEnd): int
    {
        $hasLunchColumns = attendance_lunch_columns_exist($conn);
        $inserted = 0;
        $schedule = [
            'ShiftStart' => '07:00:00',
            'LunchStart' => '12:00:00',
            'LunchEnd' => '13:00:00',
            'ShiftEnd' => '17:00:00',
        ];

        $period = new DatePeriod(
            new DateTimeImmutable($periodStart),
            new DateInterval('P1D'),
            (new DateTimeImmutable($periodEnd))->modify('+1 day')
        );

        foreach ($workerSiteMap as $entry) {
            $workerId = (int) $entry['worker_id'];
            $siteId = (int) $entry['site_id'];

            foreach ($period as $day) {
                $date = $day->format('Y-m-d');
                $exists = sample_data_fetch_scalar(
                    $conn,
                    'SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND Date = ? LIMIT 1',
                    'is',
                    [$workerId, $date]
                );
                if ($exists) {
                    continue;
                }

                $bucket = sample_data_attendance_bucket($workerId, $date);
                $times = sample_data_attendance_times($bucket, $workerId, $date);
                if ($times === null) {
                    continue;
                }

                $hourTotals = calculate_attendance_hours_worked(
                    $times['time_in'],
                    $times['lunch_out'],
                    $times['lunch_in'],
                    $times['time_out'],
                    $schedule
                );

                $hoursWorked = (float) ($hourTotals['hours_worked'] ?? 0);
                $overtimeHours = (float) ($hourTotals['overtime_hours'] ?? 0);
                $attendanceStatus = $times['status'];

                if ($hasLunchColumns) {
                    $stmt = $conn->prepare('
                        INSERT INTO attendance (
                            WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours,
                            Time_In, Lunch_Out, Lunch_In, Time_Out, AttendanceStatus
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    if (!$stmt) {
                        throw new RuntimeException('Failed to prepare attendance insert.');
                    }
                    $stmt->bind_param(
                        'iisddsssss',
                        $workerId,
                        $siteId,
                        $date,
                        $hoursWorked,
                        $overtimeHours,
                        $times['time_in'],
                        $times['lunch_out'],
                        $times['lunch_in'],
                        $times['time_out'],
                        $attendanceStatus
                    );
                } else {
                    $stmt = $conn->prepare('
                        INSERT INTO attendance (
                            WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours,
                            Time_In, Time_Out, AttendanceStatus
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    if (!$stmt) {
                        throw new RuntimeException('Failed to prepare attendance insert.');
                    }
                    $stmt->bind_param(
                        'iisddsss',
                        $workerId,
                        $siteId,
                        $date,
                        $hoursWorked,
                        $overtimeHours,
                        $times['time_in'],
                        $times['time_out'],
                        $attendanceStatus
                    );
                }

                if ($stmt->execute()) {
                    $inserted++;
                }
                $stmt->close();
            }
        }

        return $inserted;
    }
}

if (!function_exists('sample_data_seed_overtime')) {
    function sample_data_seed_overtime(
        mysqli $conn,
        array $workerSiteMap,
        array $siteTimekeepers,
        int $adminUserId,
        string $periodStart,
        string $periodEnd
    ): int {
        if (!overtime_table_exists($conn)) {
            return 0;
        }

        $overtimeHoursOptions = [1.0, 2.0, 3.0];
        $statuses = ['Approved', 'Pending', 'Rejected'];
        $inserted = 0;
        $period = new DatePeriod(
            new DateTimeImmutable($periodStart),
            new DateInterval('P1D'),
            (new DateTimeImmutable($periodEnd))->modify('+1 day')
        );
        $dates = iterator_to_array($period);

        foreach ($workerSiteMap as $index => $entry) {
            if (($index % 4) !== 0) {
                continue;
            }

            $workerId = (int) $entry['worker_id'];
            $siteId = (int) $entry['site_id'];
            $submittedBy = (int) ($siteTimekeepers[$siteId] ?? 0);
            if ($submittedBy <= 0) {
                continue;
            }

            $requestDate = $dates[crc32((string) $workerId) % count($dates)]->format('Y-m-d');
            $hours = $overtimeHoursOptions[crc32('ot|' . $workerId) % count($overtimeHoursOptions)];
            $status = $statuses[crc32('status|' . $workerId) % count($statuses)];
            $overtimeStart = '17:00:00';
            $overtimeEnd = minutes_to_site_time((int) round(17 * 60 + ($hours * 60)));
            $totalHours = calculate_overtime_total_hours($overtimeStart, $overtimeEnd);
            $reason = sprintf('Additional site work requirement for %.0f hour(s) overtime.', $hours);

            $exists = sample_data_fetch_scalar(
                $conn,
                'SELECT OvertimeID FROM overtime_requests WHERE WorkerID = ? AND SiteID = ? AND RequestDate = ? LIMIT 1',
                'iis',
                [$workerId, $siteId, $requestDate]
            );
            if ($exists) {
                continue;
            }

            $approvedBy = null;
            $approvedDate = null;
            if ($status === 'Approved' && $adminUserId > 0) {
                $approvedBy = $adminUserId;
                $approvedDate = $requestDate . ' 18:00:00';
            }

            if ($approvedBy !== null) {
                $stmt = $conn->prepare('
                    INSERT INTO overtime_requests (
                        WorkerID, SiteID, RequestDate, OvertimeType, OvertimeStart, OvertimeEnd,
                        TotalHours, Reason, SubmittedBy, Status, ApprovedBy, ApprovedDate
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $overtimeType = 'Regular Overtime';
                $stmt->bind_param(
                    'iissssdsisis',
                    $workerId,
                    $siteId,
                    $requestDate,
                    $overtimeType,
                    $overtimeStart,
                    $overtimeEnd,
                    $totalHours,
                    $reason,
                    $submittedBy,
                    $status,
                    $approvedBy,
                    $approvedDate
                );
            } else {
                $stmt = $conn->prepare('
                    INSERT INTO overtime_requests (
                        WorkerID, SiteID, RequestDate, OvertimeType, OvertimeStart, OvertimeEnd,
                        TotalHours, Reason, SubmittedBy, Status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $overtimeType = 'Regular Overtime';
                $stmt->bind_param(
                    'iissssdsis',
                    $workerId,
                    $siteId,
                    $requestDate,
                    $overtimeType,
                    $overtimeStart,
                    $overtimeEnd,
                    $totalHours,
                    $reason,
                    $submittedBy,
                    $status
                );
            }

            if ($stmt && $stmt->execute()) {
                $inserted++;
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        return $inserted;
    }
}

if (!function_exists('sample_data_seed_timekeeper_reports')) {
    function sample_data_seed_timekeeper_reports(mysqli $conn, array $sites, array $siteTimekeepers): int
    {
        tk_report_ensure_schema($conn);

        $templates = [
            [
                'report_type' => 'Worker Late Arrival',
                'subject' => 'Worker Late Arrival',
                'description' => 'Two workers arrived after the 8:00 AM grace period due to transport delays.',
            ],
            [
                'report_type' => 'Material Shortage',
                'subject' => 'Material Delivery Delay',
                'description' => 'Cement delivery was delayed by approximately 2 hours, affecting masonry activities.',
            ],
            [
                'report_type' => 'Equipment Breakdown',
                'subject' => 'Equipment Maintenance Report',
                'description' => 'Concrete mixer required emergency maintenance and was unavailable until mid-afternoon.',
            ],
            [
                'report_type' => 'Weather Delay',
                'subject' => 'Weather Delay Report',
                'description' => 'Heavy rain suspended outdoor work for 3 hours in the morning.',
            ],
        ];

        $inserted = 0;
        foreach ($sites as $site) {
            $siteId = (int) $site['site_id'];
            $siteName = (string) $site['site_name'];
            $timekeeperId = (int) ($siteTimekeepers[$siteId] ?? 0);
            if ($timekeeperId <= 0) {
                continue;
            }

            foreach ($templates as $templateIndex => $template) {
                $reportDate = date('Y-m-d', strtotime('-' . (2 + $templateIndex) . ' days'));
                $exists = sample_data_fetch_scalar(
                    $conn,
                    'SELECT TK_ReportsID FROM timekeeper_reports WHERE SiteID = ? AND ReportDate = ? AND Subject = ? LIMIT 1',
                    'iss',
                    [$siteId, $reportDate, $template['subject']]
                );
                if (!$exists) {
                    $exists = sample_data_fetch_scalar(
                        $conn,
                        'SELECT ReportID FROM timekeeper_reports WHERE SiteID = ? AND ReportDate = ? AND Subject = ? LIMIT 1',
                        'iss',
                        [$siteId, $reportDate, $template['subject']]
                    );
                }
                if ($exists) {
                    continue;
                }

                $reportId = timekeeper_report_insert_row($conn, [
                    'timekeeper_id' => $timekeeperId,
                    'site_id' => $siteId,
                    'site_name' => $siteName,
                    'report_type' => $template['report_type'],
                    'subject' => $template['subject'],
                    'description' => $template['description'],
                    'report_date' => $reportDate,
                    'status' => $templateIndex % 2 === 0 ? 'Pending' : 'Reviewed',
                ]);

                if ($reportId > 0) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }
}

if (!function_exists('sample_data_process_site_payroll')) {
    function sample_data_process_site_payroll(
        mysqli $conn,
        int $siteId,
        string $periodStart,
        string $periodEnd,
        int $submittedByUserId
    ): array {
        $existing = sample_data_fetch_scalar(
            $conn,
            'SELECT Payroll_RecordsID FROM payroll_records WHERE SiteID = ? AND Period_start = ? AND Period_end = ? LIMIT 1',
            'iss',
            [$siteId, $periodStart, $periodEnd]
        );
        if ($existing) {
            return ['workers' => 0, 'skipped' => true];
        }

        $settingsResult = $conn->query('SELECT * FROM payroll_settings WHERE id = 1 LIMIT 1');
        $settings = $settingsResult ? $settingsResult->fetch_assoc() : null;
        if (!$settings) {
            throw new RuntimeException('Payroll settings not found.');
        }

        $overtimeRate = (float) ($settings['overtime_rate'] ?? 1.25);
        $hasGovernmentDeductionColumn = worker_government_deduction_column_exists($conn);
        $governmentDeductionSelect = $hasGovernmentDeductionColumn
            ? "COALESCE(w.GovernmentDeductionStatus, 'With Deductions') AS GovernmentDeductionStatus"
            : "'With Deductions' AS GovernmentDeductionStatus";
        $governmentDeductionGroupBy = $hasGovernmentDeductionColumn ? ', w.GovernmentDeductionStatus' : '';

        $approvedOvertimeJoin = overtime_table_exists($conn)
            ? "LEFT JOIN (
                    SELECT WorkerID, SiteID, COALESCE(SUM(TotalHours), 0) AS approved_overtime_hours
                    FROM overtime_requests
                    WHERE Status = 'Approved'
                      AND RequestDate BETWEEN ? AND ?
                    GROUP BY WorkerID, SiteID
                ) ot ON ot.WorkerID = w.WorkerID AND ot.SiteID = wa.SiteID"
            : '';
        $approvedOvertimeSelect = overtime_table_exists($conn)
            ? 'COALESCE(ot.approved_overtime_hours, 0) AS approved_overtime_hours'
            : '0 AS approved_overtime_hours';

        $workerSql = "
            SELECT
                w.WorkerID,
                w.RateType,
                w.RateAmount,
                {$governmentDeductionSelect},
                COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS role_on_site,
                COALESCE(SUM(a.Hours_Worked), 0) AS total_hours,
                COALESCE(SUM(a.Overtime_Hours), 0) AS attendance_overtime_hours,
                SUM(CASE WHEN a.AttendanceStatus = 'Late' THEN 1 ELSE 0 END) AS late_days,
                {$approvedOvertimeSelect}
            FROM workerassignment wa
            INNER JOIN worker w ON wa.WorkerID = w.WorkerID
            LEFT JOIN attendance a
                ON a.WorkerID = w.WorkerID
               AND a.SiteID = wa.SiteID
               AND a.Date BETWEEN ? AND ?
            {$approvedOvertimeJoin}
            WHERE wa.SiteID = ?
            GROUP BY w.WorkerID, w.RateType, w.RateAmount, wa.Role_On_Site{$governmentDeductionGroupBy}
        ";

        $workerStmt = $conn->prepare($workerSql);
        if (!$workerStmt) {
            throw new RuntimeException('Failed to prepare payroll worker query.');
        }

        if (overtime_table_exists($conn)) {
            $workerStmt->bind_param('ssssi', $periodStart, $periodEnd, $periodStart, $periodEnd, $siteId);
        } else {
            $workerStmt->bind_param('ssi', $periodStart, $periodEnd, $siteId);
        }

        $workerStmt->execute();
        $workers = $workerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $workerStmt->close();

        if ($workers === []) {
            return ['workers' => 0, 'skipped' => false];
        }

        $insertPayrollStmt = $conn->prepare('
            INSERT INTO payroll (WorkerID, Pay_Period_Start, Pay_Period_End, Gross_Pay, Total_Deductions, Net_Pay, Date_Processed)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE())
        ');
        if (!$insertPayrollStmt) {
            throw new RuntimeException('Failed to prepare payroll insert.');
        }

        $totalGross = 0.0;
        $totalDeductions = 0.0;
        $totalNet = 0.0;
        $totalRegularHours = 0.0;
        $totalOvertimeHours = 0.0;
        $processedWorkers = 0;
        $firstPayrollId = null;

        foreach ($workers as $worker) {
            $workerId = (int) $worker['WorkerID'];
            $existingPayroll = sample_data_fetch_scalar(
                $conn,
                'SELECT PayrollID FROM payroll WHERE WorkerID = ? AND Pay_Period_Start = ? AND Pay_Period_End = ? LIMIT 1',
                'iss',
                [$workerId, $periodStart, $periodEnd]
            );
            if ($existingPayroll) {
                continue;
            }

            $rateType = strtolower((string) ($worker['RateType'] ?? 'hourly'));
            $rateAmount = (float) ($worker['RateAmount'] ?? 0);
            $regularHours = (float) ($worker['total_hours'] ?? 0);
            $attendanceOvertimeHours = (float) ($worker['attendance_overtime_hours'] ?? 0);
            $approvedOvertimeHours = (float) ($worker['approved_overtime_hours'] ?? 0);
            $overtimeHours = $attendanceOvertimeHours + $approvedOvertimeHours;
            $lateDays = (int) ($worker['late_days'] ?? 0);
            $roleOnSite = (string) ($worker['role_on_site'] ?? 'Construction Worker');

            if ($rateType === 'salary') {
                $grossPay = $rateAmount;
            } else {
                $grossPay = ($regularHours * $rateAmount) + ($overtimeHours * $rateAmount * $overtimeRate);
            }

            $grossPay = round($grossPay, 2);
            $deductionBreakdown = compute_worker_payroll_deductions(
                $grossPay,
                (string) ($worker['GovernmentDeductionStatus'] ?? GOVERNMENT_DEDUCTION_WITH),
                $settings
            );
            $fixedDeductionBreakdown = compute_fixed_payroll_deductions($roleOnSite, $lateDays, $settings);
            $deductions = round($deductionBreakdown['total'] + $fixedDeductionBreakdown['total'], 2);
            $netPay = round($grossPay - $deductions, 2);

            $insertPayrollStmt->bind_param('issddd', $workerId, $periodStart, $periodEnd, $grossPay, $deductions, $netPay);
            if (!$insertPayrollStmt->execute()) {
                continue;
            }

            if ($firstPayrollId === null) {
                $firstPayrollId = (int) $insertPayrollStmt->insert_id;
            }

            $totalGross += $grossPay;
            $totalDeductions += $deductions;
            $totalNet += $netPay;
            $totalRegularHours += $regularHours;
            $totalOvertimeHours += $overtimeHours;
            $processedWorkers++;
        }

        $insertPayrollStmt->close();

        if ($processedWorkers === 0 || $firstPayrollId === null) {
            return ['workers' => 0, 'skipped' => false];
        }

        $status = 'Approved';
        $hasWorkflowColumns = payroll_approval_columns_ready($conn);

        if ($hasWorkflowColumns) {
            $recordStmt = $conn->prepare('
                INSERT INTO payroll_records (
                    PayrollID, SiteID, Period_start, Period_end,
                    Total_gross_pay, Total_deductions, Total_net_pay, Status,
                    submitted_by, submitted_at, approved_by, approved_at,
                    worker_count, regular_hours, overtime_hours
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), ?, ?, ?)
            ');
            if (!$recordStmt) {
                throw new RuntimeException('Failed to prepare payroll record insert.');
            }

            $roundedGross = round($totalGross, 2);
            $roundedDeductions = round($totalDeductions, 2);
            $roundedNet = round($totalNet, 2);
            $roundedRegularHours = round($totalRegularHours, 2);
            $roundedOvertimeHours = round($totalOvertimeHours, 2);

            $recordStmt->bind_param(
                'iissdddsiiidd',
                $firstPayrollId,
                $siteId,
                $periodStart,
                $periodEnd,
                $roundedGross,
                $roundedDeductions,
                $roundedNet,
                $status,
                $submittedByUserId,
                $submittedByUserId,
                $processedWorkers,
                $roundedRegularHours,
                $roundedOvertimeHours
            );
            $recordStmt->execute();
            $recordStmt->close();
        } else {
            $recordStmt = $conn->prepare('
                INSERT INTO payroll_records (
                    PayrollID, SiteID, Period_start, Period_end,
                    Total_gross_pay, Total_deductions, Total_net_pay, Status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            if ($recordStmt) {
                $roundedGross = round($totalGross, 2);
                $roundedDeductions = round($totalDeductions, 2);
                $roundedNet = round($totalNet, 2);
                $recordStmt->bind_param(
                    'iissddds',
                    $firstPayrollId,
                    $siteId,
                    $periodStart,
                    $periodEnd,
                    $roundedGross,
                    $roundedDeductions,
                    $roundedNet,
                    $status
                );
                $recordStmt->execute();
                $recordStmt->close();
            }
        }

        return ['workers' => $processedWorkers, 'skipped' => false];
    }
}

if (!function_exists('sample_data_run_seeder')) {
    function sample_data_run_seeder(mysqli $conn, int $adminUserId): array
    {
        if (!sample_data_is_dev_environment($conn)) {
            throw new RuntimeException('Sample data generation is only allowed in development or testing environments.');
        }

        if (sample_data_already_seeded($conn)) {
            throw new RuntimeException('Sample data has already been generated. Existing records were not modified.');
        }

        $constants = sample_data_constants();
        $password = $constants['default_password'];
        $counts = [
            'sites' => 0,
            'workers' => 0,
            'assignments' => 0,
            'attendance' => 0,
            'overtime' => 0,
            'reports' => 0,
            'payroll_batches' => 0,
            'payroll_workers' => 0,
        ];

        $conn->query("INSERT IGNORE INTO locationstatus (LocationID, Status) VALUES (1, 'Active')");
        $conn->query("INSERT IGNORE INTO workerstatus (WorkerStatusID, Status) VALUES (1, 'Active')");

        $payrollStaffUserId = sample_data_get_or_create_user(
            $conn,
            $constants['payroll_staff_name'],
            $constants['payroll_staff_email'],
            $password
        );
        sample_data_link_role($conn, 'Payroll Staff', $payrollStaffUserId);
        $payrollStaffId = sample_data_get_payroll_staff_id($conn, $payrollStaffUserId);

        $sites = [];
        $siteTimekeepers = [];

        foreach ($constants['site_names'] as $index => $siteName) {
            $timekeeper = $constants['timekeepers'][$index];
            $timekeeperUserId = sample_data_get_or_create_user(
                $conn,
                $timekeeper['full_name'],
                $timekeeper['email'],
                $password
            );
            sample_data_link_role($conn, 'Timekeeper', $timekeeperUserId);

            $siteId = sample_data_get_or_create_site(
                $conn,
                $siteName,
                $constants['site_locations'][$index],
                $constants['site_managers'][$index],
                $timekeeperUserId
            );

            sample_data_assign_timekeeper($conn, $timekeeperUserId, $siteId);
            if ($payrollStaffId > 0) {
                sample_data_assign_payroll_staff($conn, $payrollStaffId, $siteId);
            }

            $sites[] = ['site_id' => $siteId, 'site_name' => $siteName];
            $siteTimekeepers[$siteId] = $timekeeperUserId;
            $counts['sites']++;
        }

        $workerSiteMap = [];
        $workersPerSite = (int) ceil(count($constants['workers']) / count($sites));

        foreach ($constants['workers'] as $index => $workerName) {
            [$firstName, $lastName] = $workerName;
            $phone = $constants['phone_prefix'] . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $position = $constants['positions'][$index % count($constants['positions'])];
            $rate = (float) ($constants['position_rates'][$position] ?? 500.00);
            $siteIndex = (int) floor($index / $workersPerSite);
            if ($siteIndex >= count($sites)) {
                $siteIndex = count($sites) - 1;
            }

            $siteId = (int) $sites[$siteIndex]['site_id'];
            $workerId = sample_data_get_or_create_worker($conn, $firstName, $lastName, $phone, $position, $rate);
            sample_data_assign_worker($conn, $workerId, $siteId, $position);

            $workerSiteMap[] = [
                'worker_id' => $workerId,
                'site_id' => $siteId,
                'position' => $position,
            ];

            $counts['workers']++;
            $counts['assignments']++;
        }

        $periodEnd = date('Y-m-d', strtotime('-1 day'));
        $periodStart = date('Y-m-d', strtotime('-7 days'));

        $counts['attendance'] = sample_data_seed_attendance($conn, $workerSiteMap, $periodStart, $periodEnd);
        $counts['overtime'] = sample_data_seed_overtime(
            $conn,
            $workerSiteMap,
            $siteTimekeepers,
            $adminUserId,
            $periodStart,
            $periodEnd
        );
        $counts['reports'] = sample_data_seed_timekeeper_reports($conn, $sites, $siteTimekeepers);

        foreach ($sites as $site) {
            $result = sample_data_process_site_payroll(
                $conn,
                (int) $site['site_id'],
                $periodStart,
                $periodEnd,
                $adminUserId > 0 ? $adminUserId : $payrollStaffUserId
            );
            if (!$result['skipped'] && ($result['workers'] ?? 0) > 0) {
                $counts['payroll_batches']++;
                $counts['payroll_workers'] += (int) $result['workers'];
            }
        }

        return [
            'success' => true,
            'message' => 'Sample data generated successfully.',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'counts' => $counts,
            'demo_accounts' => [
                'timekeepers' => array_map(static fn(array $tk): array => [
                    'name' => $tk['full_name'],
                    'email' => $tk['email'],
                    'password' => $password,
                ], $constants['timekeepers']),
                'payroll_staff' => [
                    'name' => $constants['payroll_staff_name'],
                    'email' => $constants['payroll_staff_email'],
                    'password' => $password,
                ],
            ],
        ];
    }
}

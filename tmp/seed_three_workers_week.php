<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/connection/db_config.php';

$temporaryPassword = 'Worker@2026!';
$passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
$workers = [
    ['Carlo', 'Reyes', 'carlo.reyes@example.com', '09171234001', 'Carpenter', 150.00],
    ['Ana', 'Santos', 'ana.santos@example.com', '09171234002', 'Electrician', 175.00],
    ['Miguel', 'Dela Cruz', 'miguel.delacruz@example.com', '09171234003', 'Laborer', 110.00],
];

$attendancePatterns = [
    [
        ['Present', '07:54:00', '17:03:00', 8.00, 0],
        ['Late',    '08:24:00', '17:00:00', 7.60, 1],
        ['Present', '07:58:00', '17:12:00', 8.00, 0],
        ['Absent',  '00:00:00', '00:00:00', 0.00, 0],
        ['Present', '07:51:00', '17:05:00', 8.00, 0],
        ['Late',    '08:17:00', '17:02:00', 7.72, 1],
        ['Present', '07:56:00', '17:00:00', 8.00, 0],
    ],
    [
        ['Present', '07:48:00', '17:06:00', 8.00, 0],
        ['Present', '07:55:00', '17:00:00', 8.00, 0],
        ['Late',    '08:31:00', '17:04:00', 7.55, 1],
        ['Present', '07:57:00', '17:15:00', 8.00, 0],
        ['Absent',  '00:00:00', '00:00:00', 0.00, 0],
        ['Present', '07:53:00', '17:01:00', 8.00, 0],
        ['Late',    '08:12:00', '17:00:00', 7.80, 1],
    ],
    [
        ['Late',    '08:19:00', '17:00:00', 7.68, 1],
        ['Present', '07:59:00', '17:08:00', 8.00, 0],
        ['Absent',  '00:00:00', '00:00:00', 0.00, 0],
        ['Present', '07:50:00', '17:02:00', 8.00, 0],
        ['Late',    '08:27:00', '17:06:00', 7.65, 1],
        ['Present', '07:52:00', '17:10:00', 8.00, 0],
        ['Present', '07:58:00', '17:00:00', 8.00, 0],
    ],
];

$dates = [];
$start = new DateTimeImmutable('2026-09-14');
for ($day = 0; $day < 7; $day++) {
    $dates[] = $start->modify("+{$day} day")->format('Y-m-d');
}

try {
    $conn->begin_transaction();

    $siteStmt = $conn->prepare(
        "INSERT INTO projectsite
            (Site_Name, Location, Project_Type, Start_Date, End_Date, LocationID,
             Required_Workers, Site_Manager, Status, ShiftStart, LunchStart, LunchEnd, ShiftEnd)
         VALUES
            ('Riverside Commercial Building', 'Cagayan de Oro City', 'Commercial',
             '2026-09-01', '2027-03-31', 1, 3, 'Unassigned', 'Active',
             '08:00:00', '12:00:00', '13:00:00', '17:00:00')"
    );
    if (!$siteStmt || !$siteStmt->execute()) {
        throw new RuntimeException('Unable to create the project site: ' . $conn->error);
    }
    $siteId = (int) $conn->insert_id;
    $siteStmt->close();

    $userStmt = $conn->prepare(
        "INSERT INTO users
            (email, password, full_name, status, password_last_set_at, must_change_password, first_name, last_name)
         VALUES (?, ?, ?, 'Active', NOW(), 0, ?, ?)"
    );
    $workerStmt = $conn->prepare(
        "INSERT INTO worker
            (First_Name, Last_Name, Position, RateType, RateAmount, GovernmentDeductionStatus,
             Phone, DateHired, WorkerStatusID, UserID)
         VALUES (?, ?, ?, 'Hourly', ?, 'No Deductions', ?, '2026-09-01', 1, ?)"
    );
    $profileStmt = $conn->prepare("INSERT INTO worker_profile (WorkerID, Email) VALUES (?, ?)");
    $assignmentStmt = $conn->prepare(
        "INSERT INTO workerassignment (WorkerID, SiteID, Assigned_Date, Role_On_Site)
         VALUES (?, ?, '2026-09-01', ?)"
    );
    $attendanceStmt = $conn->prepare(
        "INSERT INTO attendance
            (WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours, Time_In,
             Lunch_Out, Lunch_In, Time_Out, AttendanceStatus, IsLate)
         VALUES (?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($workers as $workerIndex => [$firstName, $lastName, $email, $phone, $position, $rate]) {
        $fullName = $firstName . ' ' . $lastName;
        $userStmt->bind_param('sssss', $email, $passwordHash, $fullName, $firstName, $lastName);
        if (!$userStmt->execute()) throw new RuntimeException("Unable to create {$fullName}'s account: {$userStmt->error}");
        $userId = (int) $conn->insert_id;

        $workerStmt->bind_param('sssdsi', $firstName, $lastName, $position, $rate, $phone, $userId);
        if (!$workerStmt->execute()) throw new RuntimeException("Unable to create {$fullName}'s employee record: {$workerStmt->error}");
        $workerId = (int) $conn->insert_id;

        $profileStmt->bind_param('is', $workerId, $email);
        if (!$profileStmt->execute()) throw new RuntimeException("Unable to create {$fullName}'s profile: {$profileStmt->error}");

        $assignmentStmt->bind_param('iis', $workerId, $siteId, $position);
        if (!$assignmentStmt->execute()) throw new RuntimeException("Unable to assign {$fullName}: {$assignmentStmt->error}");

        foreach ($attendancePatterns[$workerIndex] as $dayIndex => [$status, $timeIn, $timeOut, $hours, $isLate]) {
            $lunchOut = $status === 'Absent' ? null : '12:00:00';
            $lunchIn = $status === 'Absent' ? null : '13:00:00';
            $date = $dates[$dayIndex];
            $attendanceStmt->bind_param(
                'iisdsssssi',
                $workerId,
                $siteId,
                $date,
                $hours,
                $timeIn,
                $lunchOut,
                $lunchIn,
                $timeOut,
                $status,
                $isLate
            );
            if (!$attendanceStmt->execute()) {
                throw new RuntimeException("Unable to add {$fullName}'s attendance for {$date}: {$attendanceStmt->error}");
            }
        }
    }

    $userStmt->close();
    $workerStmt->close();
    $profileStmt->close();
    $assignmentStmt->close();
    $attendanceStmt->close();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'site_id' => $siteId,
        'temporary_password' => $temporaryPassword,
        'attendance_dates' => [$dates[0], $dates[6]],
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $error) {
    $conn->rollback();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}

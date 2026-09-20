<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../api/connection/db_config.php';

$workers = $conn->query("
    SELECT w.WorkerID, w.First_Name, w.Last_Name, w.UserID, wp.Email
    FROM worker w
    LEFT JOIN worker_profile wp ON wp.WorkerID = w.WorkerID
    LEFT JOIN users u ON u.id = w.UserID
    LEFT JOIN admin a ON a.UserID = w.UserID
    LEFT JOIN assistantmanager am ON am.UserID = w.UserID
    LEFT JOIN payrollstaff ps ON ps.UserID = w.UserID
    LEFT JOIN timekeeper tk ON tk.UserID = w.UserID
    LEFT JOIN (
        SELECT UserID, COUNT(*) AS worker_count
        FROM worker
        WHERE UserID IS NOT NULL
        GROUP BY UserID
    ) links ON links.UserID = w.UserID
    WHERE u.id IS NULL
       OR a.UserID IS NOT NULL
       OR am.UserID IS NOT NULL
       OR ps.UserID IS NOT NULL
       OR tk.UserID IS NOT NULL
       OR COALESCE(links.worker_count, 0) > 1
    ORDER BY w.WorkerID
");

if (!$workers) {
    throw new RuntimeException($conn->error);
}

$emailExists = $conn->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
$emailFrequency = $conn->prepare('SELECT COUNT(*) AS total FROM worker_profile WHERE LOWER(Email) = LOWER(?)');
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0");
$insertUser = $conn->prepare("
    INSERT INTO users (email, password, full_name, status, password_last_set_at, must_change_password)
    VALUES (?, ?, ?, 'Active', NOW(), 1)
");
$linkWorker = $conn->prepare('UPDATE worker SET UserID = ? WHERE WorkerID = ?');

if (!$emailExists || !$emailFrequency || !$insertUser || !$linkWorker) {
    throw new RuntimeException('Unable to prepare worker account provisioning statements.');
}

$created = 0;
$conn->begin_transaction();

try {
    while ($worker = $workers->fetch_assoc()) {
        $workerId = (int) $worker['WorkerID'];
        $fullName = trim((string) $worker['First_Name'] . ' ' . (string) $worker['Last_Name']);
        $profileEmail = trim((string) ($worker['Email'] ?? ''));
        $email = '';

        if (filter_var($profileEmail, FILTER_VALIDATE_EMAIL)) {
            $emailFrequency->bind_param('s', $profileEmail);
            $emailFrequency->execute();
            $frequency = (int) ($emailFrequency->get_result()->fetch_assoc()['total'] ?? 0);

            $emailExists->bind_param('s', $profileEmail);
            $emailExists->execute();
            $alreadyUsed = $emailExists->get_result()->num_rows > 0;

            if ($frequency === 1 && !$alreadyUsed) {
                $email = $profileEmail;
            }
        }

        if ($email === '') {
            $email = 'worker' . $workerId . '@philippians.local';
        }

        $passwordHash = password_hash('password', PASSWORD_DEFAULT);

        $insertUser->bind_param('sss', $email, $passwordHash, $fullName);
        if (!$insertUser->execute()) {
            throw new RuntimeException("Unable to create account for worker {$workerId}: " . $insertUser->error);
        }

        $newUserId = (int) $conn->insert_id;
        $linkWorker->bind_param('ii', $newUserId, $workerId);
        if (!$linkWorker->execute()) {
            throw new RuntimeException("Unable to link account for worker {$workerId}: " . $linkWorker->error);
        }

        $created++;
    }

    $conn->commit();
    fwrite(STDOUT, "Provisioned {$created} worker account(s).\n");
} catch (Throwable $error) {
    $conn->rollback();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}

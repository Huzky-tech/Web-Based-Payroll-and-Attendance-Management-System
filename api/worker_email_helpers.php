<?php
function worker_email_in_use(mysqli $conn, string $email, int $workerId = 0): bool
{
    $sql = "SELECT 1 FROM worker_profile WHERE LOWER(TRIM(Email)) = LOWER(?) AND WorkerID <> ?
        UNION ALL
        SELECT 1 FROM users u WHERE LOWER(TRIM(u.email)) = LOWER(?)
        AND NOT EXISTS (
            SELECT 1 FROM worker w WHERE w.WorkerID = ? AND w.UserID = u.id
            AND NOT EXISTS (SELECT 1 FROM admin WHERE UserID = u.id)
            AND NOT EXISTS (SELECT 1 FROM hr WHERE UserID = u.id)
            AND NOT EXISTS (SELECT 1 FROM payrollstaff WHERE UserID = u.id)
            AND NOT EXISTS (SELECT 1 FROM timekeeper WHERE UserID = u.id)
            AND NOT EXISTS (SELECT 1 FROM assistantmanager WHERE UserID = u.id)
            AND NOT EXISTS (SELECT 1 FROM worker other WHERE other.UserID = u.id AND other.WorkerID <> w.WorkerID)
        ) LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('Unable to verify email availability. Please try again.');
    $stmt->bind_param('sisi', $email, $workerId, $email, $workerId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to verify email availability. Please try again.');
    $duplicate = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $duplicate;
}

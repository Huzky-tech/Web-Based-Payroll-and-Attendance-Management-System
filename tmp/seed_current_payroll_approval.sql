START TRANSACTION;

-- Current weekly payroll view: Sep 21-26, 2026.
INSERT INTO attendance
    (WorkerID, SiteID, Date, Hours_Worked, Overtime_Hours, Time_In, Lunch_Out, Lunch_In, Time_Out, AttendanceStatus, IsLate)
VALUES
    (1, 1, '2026-09-21', 8.00, 0.00, '07:54:00', '12:00:00', '13:00:00', '17:03:00', 'Present', 0),
    (1, 1, '2026-09-22', 7.60, 0.00, '08:24:00', '12:00:00', '13:00:00', '17:00:00', 'Late', 1),
    (1, 1, '2026-09-23', 8.00, 0.00, '07:58:00', '12:00:00', '13:00:00', '17:12:00', 'Present', 0),
    (1, 1, '2026-09-24', 0.00, 0.00, '00:00:00', NULL, NULL, '00:00:00', 'Absent', 0),
    (1, 1, '2026-09-25', 8.00, 0.00, '07:51:00', '12:00:00', '13:00:00', '17:05:00', 'Present', 0),
    (1, 1, '2026-09-26', 7.72, 0.00, '08:17:00', '12:00:00', '13:00:00', '17:02:00', 'Late', 1),

    (2, 1, '2026-09-21', 8.00, 0.00, '07:48:00', '12:00:00', '13:00:00', '17:06:00', 'Present', 0),
    (2, 1, '2026-09-22', 8.00, 0.00, '07:55:00', '12:00:00', '13:00:00', '17:00:00', 'Present', 0),
    (2, 1, '2026-09-23', 7.55, 0.00, '08:31:00', '12:00:00', '13:00:00', '17:04:00', 'Late', 1),
    (2, 1, '2026-09-24', 8.00, 0.00, '07:57:00', '12:00:00', '13:00:00', '17:15:00', 'Present', 0),
    (2, 1, '2026-09-25', 0.00, 0.00, '00:00:00', NULL, NULL, '00:00:00', 'Absent', 0),
    (2, 1, '2026-09-26', 8.00, 0.00, '07:53:00', '12:00:00', '13:00:00', '17:01:00', 'Present', 0),

    (3, 1, '2026-09-21', 7.68, 0.00, '08:19:00', '12:00:00', '13:00:00', '17:00:00', 'Late', 1),
    (3, 1, '2026-09-22', 8.00, 0.00, '07:59:00', '12:00:00', '13:00:00', '17:08:00', 'Present', 0),
    (3, 1, '2026-09-23', 0.00, 0.00, '00:00:00', NULL, NULL, '00:00:00', 'Absent', 0),
    (3, 1, '2026-09-24', 8.00, 0.00, '07:50:00', '12:00:00', '13:00:00', '17:02:00', 'Present', 0),
    (3, 1, '2026-09-25', 7.65, 0.00, '08:27:00', '12:00:00', '13:00:00', '17:06:00', 'Late', 1),
    (3, 1, '2026-09-26', 8.00, 0.00, '07:52:00', '12:00:00', '13:00:00', '17:10:00', 'Present', 0);

INSERT INTO payroll
    (WorkerID, Pay_Period_Start, Pay_Period_End, Gross_Pay, Total_Deductions, Net_Pay, Date_Processed)
SELECT
    w.WorkerID,
    '2026-09-21',
    '2026-09-26',
    ROUND(SUM(CASE WHEN a.Time_In <> '00:00:00' AND a.Time_Out <> '00:00:00' THEN a.Hours_Worked ELSE 0 END) * w.RateAmount, 2),
    0.00,
    ROUND(SUM(CASE WHEN a.Time_In <> '00:00:00' AND a.Time_Out <> '00:00:00' THEN a.Hours_Worked ELSE 0 END) * w.RateAmount, 2),
    '2026-09-21'
FROM worker w
INNER JOIN workerassignment wa ON wa.WorkerID = w.WorkerID AND wa.SiteID = 1
INNER JOIN attendance a ON a.WorkerID = w.WorkerID AND a.SiteID = 1
    AND a.Date BETWEEN '2026-09-21' AND '2026-09-26'
GROUP BY w.WorkerID, w.RateAmount;

SET @first_payroll_id = (
    SELECT MIN(PayrollID) FROM payroll
    WHERE Pay_Period_Start = '2026-09-21' AND Pay_Period_End = '2026-09-26'
);
SET @admin_user_id = (SELECT UserID FROM admin ORDER BY Admin_ID LIMIT 1);

INSERT INTO payroll_records
    (PayrollID, SiteID, Period_start, Period_end, Total_gross_pay, Total_deductions,
     Total_net_pay, Status, submitted_by, submitted_at, worker_count, regular_hours, overtime_hours)
SELECT
    @first_payroll_id,
    1,
    '2026-09-21',
    '2026-09-26',
    ROUND(SUM(Gross_Pay), 2),
    ROUND(SUM(Total_Deductions), 2),
    ROUND(SUM(Net_Pay), 2),
    'Pending',
    @admin_user_id,
    NOW(),
    COUNT(*),
    (SELECT ROUND(SUM(Hours_Worked), 2) FROM attendance WHERE SiteID = 1 AND Date BETWEEN '2026-09-21' AND '2026-09-26'),
    0.00
FROM payroll
WHERE Pay_Period_Start = '2026-09-21' AND Pay_Period_End = '2026-09-26';

SET @payroll_record_id = LAST_INSERT_ID();
INSERT INTO admin_notifications
    (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
VALUES
    (NULL, 'Payroll Submitted', @payroll_record_id, 'Payroll awaiting approval',
     'Payroll for Riverside Commercial Building (Sep 21-26, 2026, 3 workers) is ready for approval.', 0, NOW());

COMMIT;

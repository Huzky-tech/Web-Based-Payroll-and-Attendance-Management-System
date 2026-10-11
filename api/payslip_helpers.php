<?php

/** Create the worker payslip entries once a payroll batch is released. */
function payslip_release_for_record(mysqli $conn, int $recordId): void
{
    $recordStmt = $conn->prepare(
        'SELECT SiteID, Period_start, Period_end, Status FROM payroll_records WHERE Payroll_RecordsID = ? LIMIT 1'
    );
    if (!$recordStmt) {
        throw new RuntimeException('Unable to prepare the payslip release.');
    }

    $recordStmt->bind_param('i', $recordId);
    $recordStmt->execute();
    $record = $recordStmt->get_result()->fetch_assoc();
    $recordStmt->close();

    if (!$record || strcasecmp((string) $record['Status'], 'Approved') !== 0) {
        return;
    }

    $siteId = (int) $record['SiteID'];
    $periodStart = (string) $record['Period_start'];
    $periodEnd = (string) $record['Period_end'];
    $insertStmt = $conn->prepare(
        "INSERT INTO payslip (PayrollID, Issue_Date, Payslip_Number)
         SELECT DISTINCT p.PayrollID, CURDATE(), CONCAT('PS-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-', p.PayrollID)
         FROM payroll p
         INNER JOIN workerassignment wa ON wa.WorkerID = p.WorkerID AND wa.SiteID = ?
         LEFT JOIN payslip existing ON existing.PayrollID = p.PayrollID
         WHERE p.Pay_Period_Start = ? AND p.Pay_Period_End = ? AND existing.PayslipID IS NULL"
    );
    if (!$insertStmt) {
        throw new RuntimeException('Unable to prepare payslip records.');
    }

    $insertStmt->bind_param('iss', $siteId, $periodStart, $periodEnd);
    if (!$insertStmt->execute()) {
        throw new RuntimeException('Unable to release payslips.');
    }
    $insertStmt->close();
}

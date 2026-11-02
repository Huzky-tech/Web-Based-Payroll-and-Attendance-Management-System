<?php
/**
 * Government deduction eligibility helpers for payroll computation.
 */

if (!defined('GOVERNMENT_DEDUCTION_WITH')) {
    define('GOVERNMENT_DEDUCTION_WITH', 'With Deductions');
}

if (!defined('GOVERNMENT_DEDUCTION_NONE')) {
    define('GOVERNMENT_DEDUCTION_NONE', 'No Deductions');
}

if (!function_exists('worker_government_deduction_column_exists')) {
    function worker_government_deduction_column_exists(mysqli $conn): bool
    {
        $result = $conn->query("SHOW COLUMNS FROM worker LIKE 'GovernmentDeductionStatus'");
        return $result !== false && $result->num_rows > 0;
    }
}

if (!function_exists('worker_government_deduction_types_column_exists')) {
    function worker_government_deduction_types_column_exists(mysqli $conn): bool
    {
        $result = $conn->query("SHOW COLUMNS FROM worker LIKE 'GovernmentDeductionTypes'");
        return $result !== false && $result->num_rows > 0;
    }
}

if (!function_exists('normalize_government_deduction_types')) {
    /** @return string[] */
    function normalize_government_deduction_types($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            $value = [];
        }

        $types = array_values(array_unique(array_intersect(
            ['sss', 'philhealth', 'pagibig'],
            array_map(static fn($type) => strtolower(trim((string) $type)), $value)
        )));

        // Missing or empty selections mean no deductions. Employees subject
        // to legacy defaults are initialized with all types when created.
        return $types;
    }
}

if (!function_exists('normalize_government_deduction_status')) {
    function normalize_government_deduction_status(?string $value): string
    {
        if (strcasecmp((string) $value, GOVERNMENT_DEDUCTION_NONE) === 0) {
            return GOVERNMENT_DEDUCTION_NONE;
        }

        return GOVERNMENT_DEDUCTION_WITH;
    }
}

if (!function_exists('worker_government_deductions_enabled')) {
    function worker_government_deductions_enabled(?string $status): bool
    {
        return normalize_government_deduction_status($status) === GOVERNMENT_DEDUCTION_WITH;
    }
}

if (!function_exists('government_deduction_status_label')) {
    function government_deduction_status_label(?string $status): string
    {
        return worker_government_deductions_enabled($status)
            ? 'With Government Deductions'
            : 'No Government Deductions';
    }
}

if (!function_exists('calculate_sss_employee_employer_shares')) {
    /**
     * Calculate the weekly SSS employee and employer shares from the official
     * 2025 business-employer schedule, prorated for a 7-day payroll week.
     *
     * @return array{employee: float, employer: float, msc: float, ec: float}
     */
    function calculate_sss_employee_employer_shares(float $monthlyCompensation): array
    {
        $compensation = max(0, $monthlyCompensation);
        $msc = min(35000, max(5000, ceil($compensation / 500) * 500));
        $employee = $msc * 0.05;
        $employer = $msc * 0.10;
        $ec = $msc <= 14500 ? 10.0 : 30.0;

        return [
            'employee' => round($employee, 2),
            'employer' => round($employer + $ec, 2),
            'msc' => round($msc, 2),
            'ec' => $ec,
        ];
    }
}

if (!function_exists('compute_worker_payroll_deductions')) {
    /**
     * @param array<string, mixed> $settings payroll_settings row
     * @return array{
     *   sss: float,
     *   sss_employer: float,
     *   sss_msc: float,
     *   philhealth: float,
     *   pagibig: float,
     *   total: float,
     *   government_deduction_status: string,
     *   government_deduction_label: string
     * }
     */
    function compute_worker_payroll_deductions(float $grossPay, ?string $governmentStatus, array $settings, $governmentTypes = null, ?float $monthlyCompensation = null): array
    {
        $status = normalize_government_deduction_status($governmentStatus);

        if (!worker_government_deductions_enabled($status)) {
            return [
                'sss' => 0.0,
                'sss_employer' => 0.0,
                'sss_msc' => 0.0,
                'philhealth' => 0.0,
                'pagibig' => 0.0,
                'total' => 0.0,
                'government_deduction_status' => GOVERNMENT_DEDUCTION_NONE,
                'government_deduction_label' => government_deduction_status_label(GOVERNMENT_DEDUCTION_NONE),
            ];
        }

        // SSS uses the official contribution schedule; PhilHealth and Pag-IBIG
        // settings are percentage rates applied to this weekly gross pay.
        $selectedTypes = normalize_government_deduction_types($governmentTypes);
        $weeklyCompensation = $monthlyCompensation !== null ? $monthlyCompensation / 4.3333333333 : $grossPay;
        $sssShares = calculate_sss_employee_employer_shares($weeklyCompensation * 4.3333333333);
        $weeklyShareFactor = 12 / 52;
        $sss = in_array('sss', $selectedTypes, true) ? round($sssShares['employee'] * $weeklyShareFactor, 2) : 0.0;
        $sssEmployer = in_array('sss', $selectedTypes, true) ? round($sssShares['employer'] * $weeklyShareFactor, 2) : 0.0;
        $philhealth = in_array('philhealth', $selectedTypes, true)
            ? round($grossPay * max(0, (float) ($settings['philhealth_rate'] ?? 0)) / 100, 2)
            : 0.0;
        $pagibig = in_array('pagibig', $selectedTypes, true)
            ? round($grossPay * max(0, (float) ($settings['pagibig_rate'] ?? 0)) / 100, 2)
            : 0.0;
        $total = round($sss + $philhealth + $pagibig, 2);

        return [
            'sss' => $sss,
            'sss_employer' => $sssEmployer,
            'sss_msc' => $sssShares['msc'],
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'total' => $total,
            'government_deduction_status' => GOVERNMENT_DEDUCTION_WITH,
            'government_deduction_label' => 'With Government Deductions (' . implode(', ', array_map(
                static fn($type) => $type === 'philhealth' ? 'PhilHealth' : ($type === 'pagibig' ? 'Pag-IBIG' : 'SSS'),
                $selectedTypes
            )) . ')',
        ];
    }
}

if (!function_exists('get_position_default_deductions')) {
    /**
     * @return array<string, float>
     */
    function get_position_default_deductions(array $settings): array
    {
        $raw = $settings['position_default_deductions'] ?? '';
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $deductions = [];
        foreach ($decoded as $position => $amount) {
            $positionName = trim((string) $position);
            if ($positionName === '') {
                continue;
            }
            $deductions[strtolower($positionName)] = max(0, round((float) $amount, 2));
        }

        return $deductions;
    }
}

if (!function_exists('compute_fixed_payroll_deductions')) {
    /**
     * @return array{late: float, position: float, total: float}
     */
    function compute_fixed_payroll_deductions(?string $position, int $lateDays, array $settings): array
    {
        // Position deductions are deprecated. Keep only late deductions.
        $lateDeduction = max(0, (float) ($settings['late_worker_deduction'] ?? 0));
        $lateTotal = round(max(0, $lateDays) * $lateDeduction, 2);

        return [
            'late' => $lateTotal,
            'position' => 0.0,
            'total' => round($lateTotal, 2),
        ];
    }
}

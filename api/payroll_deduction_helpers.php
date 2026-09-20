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

if (!function_exists('compute_worker_payroll_deductions')) {
    /**
     * @param array<string, mixed> $settings payroll_settings row
     * @return array{
     *   sss: float,
     *   philhealth: float,
     *   pagibig: float,
     *   tax: float,
     *   total: float,
     *   government_deduction_status: string,
     *   government_deduction_label: string
     * }
     */
    function compute_worker_payroll_deductions(float $grossPay, ?string $governmentStatus, array $settings): array
    {
        $status = normalize_government_deduction_status($governmentStatus);

        if (!worker_government_deductions_enabled($status)) {
            return [
                'sss' => 0.0,
                'philhealth' => 0.0,
                'pagibig' => 0.0,
                'tax' => 0.0,
                'total' => 0.0,
                'government_deduction_status' => GOVERNMENT_DEDUCTION_NONE,
                'government_deduction_label' => government_deduction_status_label(GOVERNMENT_DEDUCTION_NONE),
            ];
        }

        $sssRate = (float) ($settings['sss_rate'] ?? 0);
        $philhealthRate = (float) ($settings['philhealth_rate'] ?? 0);
        $pagibigRate = (float) ($settings['pagibig_rate'] ?? 0);

        $sss = round($grossPay * ($sssRate / 100), 2);
        $philhealth = round($grossPay * ($philhealthRate / 100), 2);
        $pagibig = round($grossPay * ($pagibigRate / 100), 2);
        $tax = 0.0;
        $total = round($sss + $philhealth + $pagibig + $tax, 2);

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'tax' => $tax,
            'total' => $total,
            'government_deduction_status' => GOVERNMENT_DEDUCTION_WITH,
            'government_deduction_label' => government_deduction_status_label(GOVERNMENT_DEDUCTION_WITH),
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


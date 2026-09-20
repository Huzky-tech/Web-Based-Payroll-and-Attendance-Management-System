<?php
// HR uses the exact same visual dashboard shell as Payroll Staff, while the
// HR mode in the shared template changes its content, navigation, and access.
$hrDashboardMode = true;

// Resolve supported clean HR routes when Apache reaches this wrapper without
// forwarding the page query parameter.
if (!isset($_GET['page'])) {
    $hrRequestPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if (preg_match('#/hr/(worker|employee|active_site|attendance|overtime_requests|timekeeper_reports|payroll|payroll_status|reports|setting)$#i', $hrRequestPath, $matches)) {
        // Keep the old /hr/employee bookmark working, but render the canonical
        // Workers module used by the HR sidebar.
        $_GET['page'] = strtolower($matches[1]) === 'employee' ? 'worker' : strtolower($matches[1]);
    }
}
require __DIR__ . '/payroll_dashboard.php';

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/connection/db_config.php';
$name = 'Philippians CDO';
$tables = ['company_settings', 'company_info', 'organization_settings', 'system_settings', 'settings'];
$columns = ['company_name', 'CompanyName', 'organization_name', 'business_name', 'name'];
foreach ($tables as $table) foreach ($columns as $column) {
    $t = $conn->real_escape_string($table); $c = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    if (!$check || $check->num_rows === 0) continue;
    $result = $conn->query("SELECT `{$c}` AS company_name FROM `{$t}` LIMIT 1");
    $candidate = trim((string) (($result ? $result->fetch_assoc() : null)['company_name'] ?? ''));
    if ($candidate !== '') { $name = $candidate; break 2; }
}
echo json_encode(['status' => 'success', 'company_name' => $name]);

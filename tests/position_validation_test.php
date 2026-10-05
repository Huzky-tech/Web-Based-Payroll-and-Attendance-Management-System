<?php
require_once __DIR__ . '/../includes/position_validation.php';
$base = ['position_name' => 'Lead Man', 'hourly_rate' => '87.50', 'salary_rate' => '20000', 'weekly_rate' => '5000'];
$cases = [
    [$base, null],
    [array_replace($base, ['weekly_rate' => '']), 'weekly_rate'],
    [array_replace($base, ['weekly_rate' => null]), 'weekly_rate'],
    [array_diff_key($base, ['weekly_rate' => true]), 'weekly_rate'],
    [array_replace($base, ['position_name' => '']), 'position_name'],
    [array_replace($base, ['position_name' => '<script>']), 'position_name'],
    [array_replace($base, ['position_name' => str_repeat('A', 101)]), 'position_name'],
    [array_replace($base, ['hourly_rate' => '12abc']), 'hourly_rate'],
    [array_replace($base, ['hourly_rate' => '-1']), 'hourly_rate'],
    [array_replace($base, ['salary_rate' => '100000000']), 'salary_rate'],
    [array_replace($base, ['salary_rate' => '0']), 'salary_rate'],
    [array_replace($base, ['weekly_rate' => '1.234']), 'weekly_rate'],
    [array_replace($base, ['weekly_rate' => ['100']]), 'weekly_rate'],
    [array_replace($base, ['weekly_rate' => '1e3']), 'weekly_rate'],
];
foreach ($cases as [$data, $expected]) {
    $result = position_validate($data);
    if ($expected === null ? !empty($result['errors']) : !isset($result['errors'][$expected])) {
        throw new RuntimeException('Unexpected validation result: ' . json_encode($data));
    }
}
echo count($cases) . " position validation cases passed.\n";

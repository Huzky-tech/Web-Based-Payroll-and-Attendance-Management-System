<?php

function position_validate(array $data): array
{
    $errors = [];
    $rawName = $data['position_name'] ?? '';
    $name = is_string($rawName) ? preg_replace('/\s+/u', ' ', trim($rawName)) : '';
    if (!$name || !preg_match('/^[\p{L} ]{1,100}$/u', $name)) {
        $errors['position_name'] = 'Enter a position name of 1–100 letters and spaces.';
    }
    $values = ['position_name' => $name];
    foreach (['hourly_rate' => 'Hourly rate', 'weekly_rate' => 'Weekly salary', 'salary_rate' => 'Monthly salary'] as $key => $label) {
        $raw = $data[$key] ?? '';
        if ((!is_string($raw) && !is_int($raw) && !is_float($raw))
            || !preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $raw)
            || (float) $raw < 0.01 || (float) $raw > 99999999.99) {
            $errors[$key] = "$label must be PHP 0.01–99,999,999.99, with up to two decimal places.";
        }
        $values[$key] = is_scalar($raw) ? (float) $raw : 0;
    }
    return ['values' => $values, 'errors' => $errors];
}

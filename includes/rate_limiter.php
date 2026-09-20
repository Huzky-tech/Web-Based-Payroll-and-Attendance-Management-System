<?php
declare(strict_types=1);

if (!function_exists('security_client_ip')) {
    function security_client_ip(): string
    {
        // Do not trust forwarding headers unless a trusted reverse proxy is
        // configured. REMOTE_ADDR cannot be supplied directly by the client.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}

if (!function_exists('security_rate_limit')) {
    function security_rate_limit(string $action, int $maximum, int $windowSeconds): array
    {
        $maximum = max(1, $maximum);
        $windowSeconds = max(1, $windowSeconds);
        $key = hash('sha256', $action . '|' . security_client_ip());
        $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'capstone-rate-limits';
        if (!is_dir($directory)) {
            @mkdir($directory, 0700, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $key . '.json';
        $handle = @fopen($path, 'c+');
        if (!$handle) {
            return ['allowed' => true, 'remaining' => $maximum, 'retry_after' => 0];
        }

        flock($handle, LOCK_EX);
        $raw = stream_get_contents($handle);
        $state = json_decode($raw ?: '', true);
        $now = time();
        if (!is_array($state) || ($now - (int) ($state['started_at'] ?? 0)) >= $windowSeconds) {
            $state = ['started_at' => $now, 'attempts' => 0];
        }

        $allowed = (int) $state['attempts'] < $maximum;
        if ($allowed) {
            $state['attempts'] = (int) $state['attempts'] + 1;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $retryAfter = max(0, $windowSeconds - ($now - (int) $state['started_at']));
        return [
            'allowed' => $allowed,
            'remaining' => max(0, $maximum - (int) $state['attempts']),
            'retry_after' => $allowed ? 0 : $retryAfter,
        ];
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';

$GLOBALS['app_email_last_error'] = '';

if (!function_exists('app_email_last_error')) {
    function app_email_last_error(): string
    {
        return (string) ($GLOBALS['app_email_last_error'] ?? '');
    }
}

if (!function_exists('app_smtp_read')) {
    function app_smtp_read($socket, array $expectedCodes): bool
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            $GLOBALS['app_email_last_error'] = trim(preg_replace('/[\r\n]+/', ' ', $response));
        }
        return in_array($code, $expectedCodes, true);
    }
}

if (!function_exists('app_smtp_command')) {
    function app_smtp_command($socket, string $command, array $expectedCodes): bool
    {
        if (fwrite($socket, $command . "\r\n") === false) return false;
        return app_smtp_read($socket, $expectedCodes);
    }
}

if (!function_exists('app_send_smtp_email')) {
    function app_send_smtp_email(string $recipient, string $subject, string $plainText): bool
    {
        $host = trim((string) getenv('SMTP_HOST'));
        $port = (int) (getenv('SMTP_PORT') ?: 587);
        $username = trim((string) getenv('SMTP_USERNAME'));
        $password = (string) getenv('SMTP_PASSWORD');
        $encryption = strtolower(trim((string) (getenv('SMTP_ENCRYPTION') ?: 'tls')));
        $from = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: $username));
        $fromName = preg_replace('/[\r\n]+/', ' ', trim((string) (getenv('MAIL_FROM_NAME') ?: 'Philippians CDO Construction Company')));

        if ($host === '' || $username === '' || $password === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $GLOBALS['app_email_last_error'] = 'SMTP configuration is incomplete.';
            return false;
        }
        if (str_contains(strtolower($host), 'gmail.com')) $password = str_replace(' ', '', $password);

        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errorNumber, $errorMessage, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $GLOBALS['app_email_last_error'] = "SMTP connection failed ({$errorNumber}): {$errorMessage}";
            return false;
        }
        stream_set_timeout($socket, 15);

        $ok = app_smtp_read($socket, [220]);
        $clientName = preg_replace('/[^A-Za-z0-9.-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
        $ok = $ok && app_smtp_command($socket, 'EHLO ' . $clientName, [250]);

        if ($ok && $encryption === 'tls') {
            $ok = app_smtp_command($socket, 'STARTTLS', [220]);
            if ($ok) {
                $ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) === true;
                if (!$ok) $GLOBALS['app_email_last_error'] = 'Unable to establish an encrypted SMTP connection.';
                $ok = $ok && app_smtp_command($socket, 'EHLO ' . $clientName, [250]);
            }
        }

        $ok = $ok && app_smtp_command($socket, 'AUTH LOGIN', [334]);
        $ok = $ok && app_smtp_command($socket, base64_encode($username), [334]);
        $ok = $ok && app_smtp_command($socket, base64_encode($password), [235]);
        $ok = $ok && app_smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250]);
        $ok = $ok && app_smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        $ok = $ok && app_smtp_command($socket, 'DATA', [354]);

        if ($ok) {
            $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject) ?: 'Account verification';
            $safeName = $fromName ?: 'Phil CDO';
            $body = str_replace(["\r\n", "\r"], "\n", $plainText);
            $body = preg_replace('/^\./m', '..', $body);
            $message = 'Date: ' . date(DATE_RFC2822) . "\r\n"
                . 'From: ' . $safeName . ' <' . $from . ">\r\n"
                . 'To: <' . $recipient . ">\r\n"
                . 'Subject: ' . $safeSubject . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . str_replace("\n", "\r\n", $body) . "\r\n.";
            $ok = app_smtp_command($socket, $message, [250]);
        }

        if ($ok) app_smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return $ok;
    }
}

if (!function_exists('app_send_email')) {
    function app_send_email(string $recipient, string $subject, string $plainText): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) return false;
        if (trim((string) getenv('SMTP_HOST')) !== '') {
            return app_send_smtp_email($recipient, $subject, $plainText);
        }

        $from = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@philippianscdo.local'));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return false;
        $fromName = preg_replace('/[\r\n]+/', ' ', trim((string) (getenv('MAIL_FROM_NAME') ?: 'Philippians CDO Construction Company')));
        $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject);
        $headers = [
            'From: ' . ($fromName ?: 'Philippians CDO') . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        return @mail($recipient, $safeSubject ?: 'Account verification', $plainText, implode("\r\n", $headers));
    }
}

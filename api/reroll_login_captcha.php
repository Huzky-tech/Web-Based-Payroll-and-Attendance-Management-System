<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!security_verify_csrf()) {
    security_reject_invalid_csrf();
}

$left = random_int(1, 9);
$right = random_int(1, 9);
$_SESSION['login_captcha_left'] = $left;
$_SESSION['login_captcha_right'] = $right;
$_SESSION['login_captcha_answer'] = (string) ($left + $right);

echo json_encode([
    'success' => true,
    'question' => "{$left} + {$right} = ?",
    // The answer is returned only for the page's existing client-side feedback;
    // the submitted answer remains verified against the server-side session value.
    'answer' => $_SESSION['login_captcha_answer'],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

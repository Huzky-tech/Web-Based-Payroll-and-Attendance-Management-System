<?php
$p='api/check_user_email.php';$s=file_get_contents($p);$s=str_replace("require_once __DIR__ . '/connection/db_config.php';", "require_once __DIR__ . '/connection/db_config.php';\nrequire_once __DIR__ . '/../includes/user_identity.php';", $s);$a=strpos($s,'// Include inactive accounts:');$s=substr($s,0,$a). <<<'CODE'
// Use the same checks as saving, including archived accounts and employees.
try {
    $fullName = trim((string) ($_GET['full_name'] ?? ''));
    $emailAvailable = !user_identity_email_exists($conn, $email, $excludeId);
    $nameAvailable = $fullName === '' || !user_identity_full_name_exists($conn, $fullName, $excludeId);
    echo json_encode(['success' => true, 'available' => $emailAvailable, 'name_available' => $nameAvailable]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify account availability. Please retry.']);
}
CODE;
file_put_contents($p,$s);
foreach (['api/add_user.php','api/edit_user.php'] as $p) {
 $s=file_get_contents($p);
 $needle='    if (user_identity_full_name_exists';
 $a=strpos($s,$needle);
 $exclude=$p==='api/add_user.php'?'0':'$user_id';
 $extra="    if (user_identity_email_exists(\$conn, \$email, {$exclude})) {\n        throw new RuntimeException('This email address is already registered.');\n    }\n";
 $s=substr_replace($s,$extra,$a,0);file_put_contents($p,$s);
}

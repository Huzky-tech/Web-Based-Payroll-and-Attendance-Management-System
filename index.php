<?php
require_once __DIR__ . '/includes/security.php';

// Database connection
$servername = "localhost";
$username = "root"; // Adjust as needed
$password = ""; // Adjust as needed
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection is currently unavailable.');
}

// Get company name from settings
$companyName = 'Philippians CDO';
$companyStmt = $conn->prepare("SELECT company_name FROM company_settings WHERE id = 1");
if ($companyStmt) {
    $companyStmt->execute();
    $companyResult = $companyStmt->get_result();
    if ($companyRow = $companyResult->fetch_assoc()) {
        $companyName = $companyRow['company_name'] ?: 'Philippians CDO';
    }
    $companyStmt->close();
}

require_once 'includes/auth.php';
require_once 'includes/login_security.php';
require_once 'includes/password_policy.php';
require_once 'includes/remember_login.php';

login_security_ensure_columns($conn);
remember_login_ensure_table($conn);
$loginPasswordPolicy = password_policy_settings($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_SESSION['user_id'])) {
    $rememberedUser = remember_login_user($conn);
    if ($rememberedUser) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $rememberedUser['user_id'];
        $_SESSION['full_name'] = (string) $rememberedUser['full_name'];
        $_SESSION['email'] = (string) $rememberedUser['email'];
        $_SESSION['role'] = auth_get_user_role($conn, (int) $rememberedUser['user_id']);
        $_SESSION['last_activity'] = time();
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" && !empty($_SESSION['user_id'])) {
    $role = auth_get_user_role($conn, (int) $_SESSION['user_id']);
    $_SESSION['role'] = $role;
    $redirect = preg_replace('#^\.\./#', '', auth_get_redirect_path($role));
    header("Location: " . $redirect);
    exit();
}

$unknown_email = !empty($_SESSION['login_unknown_email']);
$loginEmailValue = (string) ($_SESSION['login_email_value'] ?? ($_POST['email'] ?? ''));
$rememberChecked = !empty($_POST['remember_me']);
$loginWasPosted = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($unknown_email) {
    $error = "Email not found.";
    $email_field_error = "This email is not registered.";
    unset($_SESSION['login_unknown_email'], $_SESSION['login_email_value']);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && !security_verify_csrf()) {
    http_response_code(419);
    $error = 'Your security token is invalid or expired. Refresh the page and try again.';
    $login_alert = $error;
    $_SERVER["REQUEST_METHOD"] = "GET";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $captchaAnswer = trim((string) ($_POST['captcha_answer'] ?? ''));
    $expectedCaptcha = (string) ($_SESSION['login_captcha_answer'] ?? '');
    if ($captchaAnswer === '' || $expectedCaptcha === '' || !hash_equals($expectedCaptcha, $captchaAnswer)) {
        $error = 'Incorrect CAPTCHA answer. Please try the new question.';
        $captcha_field_error = $error;
        $login_alert = $error;
        $_SESSION['login_captcha_left'] = random_int(1, 9);
        $_SESSION['login_captcha_right'] = random_int(1, 9);
        $_SESSION['login_captcha_answer'] = (string) ($_SESSION['login_captcha_left'] + $_SESSION['login_captcha_right']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT id, full_name, email, password, status, failed_login_attempts, account_locked_at, admin_login_cooldown_until, password_last_set_at, must_change_password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $full_name, $email, $hashed_password, $status, $failedLoginAttempts, $accountLockedAt, $adminLoginCooldownUntil, $passwordLastSetAt, $mustChangePassword);
        $stmt->fetch();

        // Admin accounts never use account_locked_at. Convert any legacy Admin
        // lock into the separate five-minute login cooldown.
        $role = auth_get_user_role($conn, (int) $id);
        if (!empty($accountLockedAt) && $role === 'Admin') {
            $legacyUntil = (strtotime((string) $accountLockedAt) ?: time()) + 300;
            $adminLoginCooldownUntil = date('Y-m-d H:i:s', max(time(), $legacyUntil));
            $convertStmt = $conn->prepare('UPDATE users SET account_locked_at = NULL, admin_login_cooldown_until = ? WHERE id = ?');
            $convertStmt->bind_param('si', $adminLoginCooldownUntil, $id);
            $convertStmt->execute();
            $convertStmt->close();
            $accountLockedAt = null;
        }
        if ($role === 'Admin' && !empty($adminLoginCooldownUntil)) {
            $adminLockSecondsRemaining = max(0, (strtotime((string) $adminLoginCooldownUntil) ?: 0) - time());
            if ($adminLockSecondsRemaining === 0) {
                login_security_reset_attempts($conn, (int) $id, false);
                $failedLoginAttempts = 0;
                $adminLoginCooldownUntil = null;
            } else {
                $admin_lockout_until = time() + $adminLockSecondsRemaining;
            }
        }

        if ($role === 'Admin' && !empty($adminLoginCooldownUntil)) {
            $minutes = intdiv($adminLockSecondsRemaining, 60);
            $seconds = $adminLockSecondsRemaining % 60;
            $error = sprintf('Too many Admin login attempts. Please wait %02d:%02d.', $minutes, $seconds);
            $password_field_error = $error;
            $login_alert = $error;
        } elseif (!empty($accountLockedAt)) {
            $error = 'This account is locked after too many incorrect password attempts. Please contact an administrator.';
            $password_field_error = $error;
            $login_alert = $error;
        } elseif (strcasecmp((string) $status, 'Active') !== 0) {
            $error = 'This account is not active.';
            $password_field_error = $error;
            $login_alert = $error;
        } elseif (password_verify($password, $hashed_password) && (int) $mustChangePassword === 1) {
            $_SESSION['must_change_password_user_id'] = (int) $id;
            $_SESSION['must_change_password_email'] = $email;
            unset($_SESSION['first_login_verified_until'], $_SESSION['first_login_otp_hash'], $_SESSION['first_login_otp_expires'], $_SESSION['first_login_otp_attempts']);
            login_security_reset_attempts($conn, (int) $id, false);
            $first_login = true;
        } elseif (password_verify($password, $hashed_password) && login_security_password_is_expired($conn, $passwordLastSetAt)) {
            $error = 'Your password has expired. Use Forgot password below to verify your account email and create a new password.';
            $password_field_error = $error;
            $login_alert = $error;
        } elseif (password_verify($password, $hashed_password)) {
            $redirect = preg_replace('#^\.\./#', '', auth_get_redirect_path($role));

            if ($role !== 'Admin' && auth_is_maintenance_mode($conn)) {
                auth_render_maintenance_page();
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['last_activity'] = time();

            if (!empty($_POST['remember_me'])) {
                remember_login_issue($conn, (int) $id);
            } else {
                remember_login_revoke_current($conn);
            }

            login_security_reset_attempts($conn, (int) $id, true);

            require_once 'api/record_audit_log.php';
            record_audit_log((int)$id, 'User Login', "Successful login as {$role}");

            header("Location: " . $redirect);
            exit();
        } else {
            $maxAttempts = login_security_max_attempts($conn);
            if ($role === 'Admin') {
                $attempt = login_security_record_admin_failed_attempt($conn, (int) $id, $maxAttempts);
                if (!empty($attempt['cooldown_until'])) {
                    $adminLockSecondsRemaining = 300;
                    $admin_lockout_until = time() + 300;
                    $error = "Too many Admin login attempts. Please wait 05:00.";
                } else {
                    $remaining = max(0, $maxAttempts - $attempt['attempts']);
                    $error = "Incorrect password. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . ' remaining.';
                }
            } else {
                $attempt = login_security_record_failed_attempt($conn, (int) $id, $maxAttempts);
                if ($attempt['locked']) {
                    $error = "Account locked after {$maxAttempts} incorrect password attempts. Please contact an administrator.";
                } else {
                    $remaining = max(0, $maxAttempts - $attempt['attempts']);
                    $error = "Incorrect password. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . ' remaining.';
                }
            }
            $password_field_error = $error;
            $login_alert = $error;
        }
    } else {
        $_SESSION['login_unknown_email'] = true;
        $_SESSION['login_email_value'] = $email;
        $_SESSION['login_captcha_left'] = random_int(1, 9);
        $_SESSION['login_captcha_right'] = random_int(1, 9);
        $_SESSION['login_captcha_answer'] = (string) ($_SESSION['login_captcha_left'] + $_SESSION['login_captcha_right']);
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit();
    }

    $stmt->close();
}

if ($loginWasPosted || empty($_SESSION['login_captcha_answer'])) {
    $_SESSION['login_captcha_left'] = random_int(1, 9);
    $_SESSION['login_captcha_right'] = random_int(1, 9);
    $_SESSION['login_captcha_answer'] = (string) ($_SESSION['login_captcha_left'] + $_SESSION['login_captcha_right']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippians CDO - Payroll Management System</title>
    <link rel="icon" type="image/png" href="images/company-building-logo.png?v=20260907-1">
    <script>window.CSRF_CONFIG=<?php echo json_encode(['token' => security_csrf_token()], ( JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
    <script src="js/csrf.js?v=20260820-1"></script>
    <script src="js/action_result_modal.js?v=20260920-access-1" defer></script>
    <script>
        window.FIRST_TIME_PASSWORD_FLOW=<?php echo !empty($first_login) ? 'true' : 'false'; ?>;
        window.FIRST_TIME_LOGIN_EMAIL=<?php echo json_encode(!empty($first_login) ? (string) ($_SESSION['must_change_password_email'] ?? '') : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="js/index.js?v=20260915-3" defer></script>
    <link rel="stylesheet" href="css/index.css?v=20260915-3">
</head>
<body>
<script>
    <?php if (isset($unknown_email) && $unknown_email): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('emailNotFoundModal').classList.add('active');
        });
    <?php endif; ?>
    <?php if (isset($login_alert)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            window.showCrudResultModal?.(false, <?php echo json_encode($login_alert, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 'Login');
        });
    <?php endif; ?>
    <?php if (!empty($admin_lockout_until)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const lockoutUntil = <?php echo (int) $admin_lockout_until; ?> * 1000;
            const passwordError = document.getElementById('passwordError');
            const loginButton = document.querySelector('#loginForm .login-button');
            const updateAdminLockout = function() {
                const remaining = Math.max(0, Math.ceil((lockoutUntil - Date.now()) / 1000));
                if (remaining <= 0) {
                    if (passwordError) passwordError.textContent = 'Admin lockout expired. You may log in now.';
                    if (loginButton) loginButton.disabled = false;
                    return;
                }
                const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                if (passwordError) {
                    passwordError.textContent = `Too many Admin login attempts. Please wait ${minutes}:${seconds}.`;
                    passwordError.classList.add('show');
                }
                if (loginButton) loginButton.disabled = true;
                window.setTimeout(updateAdminLockout, 1000);
            };
            updateAdminLockout();
        });
    <?php endif; ?>
    <?php if (isset($first_login) && $first_login): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('forgotPasswordModal').classList.add('active');
        });
    <?php endif; ?>
</script>

<?php if (isset($error)): ?>
    <div class="error-message" style="color: red; text-align: center; margin-bottom: 10px;">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<div class="login-container">
    <div class="header">
    <h1><?php echo htmlspecialchars($companyName); ?></h1>
        <p>Payroll Management System</p>
    </div>

    <form id="loginForm" method="post">
        <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter email address"
                value="<?php echo htmlspecialchars($loginEmailValue, ENT_QUOTES, 'UTF-8'); ?>"
                required
            >
            <div class="error-message <?php echo isset($email_field_error) ? 'show' : ''; ?>" id="emailError">
                <?php echo htmlspecialchars($email_field_error ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="login-password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >
                <button type="button" class="login-password-toggle" id="toggleLoginPassword" aria-label="Show password" title="Show password">Show</button>
            </div>
            <div class="error-message" id="passwordError">
                <?php echo htmlspecialchars($password_field_error ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>

        <div class="login-options">
            <label class="remember-me" for="rememberMe">
                <input type="checkbox" id="rememberMe" name="remember_me" value="1" <?php echo $rememberChecked ? 'checked' : ''; ?>>
                <span>Remember me?</span>
            </label>
        </div>

        <div class="form-group captcha-group">
            <label for="captchaAnswer">CAPTCHA</label>
            <div class="captcha-row">
                <div class="captcha-question" id="captchaQuestion" aria-label="CAPTCHA question" data-answer="<?php echo (int) $_SESSION['login_captcha_answer']; ?>">
                    <?php echo (int) $_SESSION['login_captcha_left']; ?> + <?php echo (int) $_SESSION['login_captcha_right']; ?> = ?
                </div>
                <button type="button" class="captcha-reroll" id="captchaReroll" aria-label="Get a new CAPTCHA question" title="Get a new question">
                    <span aria-hidden="true">↻</span>
                </button>
                <input type="number" id="captchaAnswer" name="captcha_answer" placeholder="Answer" inputmode="numeric" autocomplete="off" required>
            </div>
            <div class="error-message <?php echo isset($captcha_field_error) ? 'show' : ''; ?>" id="captchaError">
                <?php echo htmlspecialchars($captcha_field_error ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>

        <button type="submit" class="login-button">Log in</button>
    </form>

    <div class="forgot-password">
        <a href="#" id="forgotPasswordLink">Forgot password?</a>
    </div>
</div>

<!-- Email not found modal -->
<div class="modal-overlay" id="emailNotFoundModal" aria-hidden="true">
    <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="emailNotFoundTitle">
        <button type="button" class="modal-close" id="closeEmailNotFound" aria-label="Close" onclick="document.getElementById('emailNotFoundModal').classList.remove('active')">&times;</button>
        <div class="modal-icon-container orange">!</div>
        <h2 class="modal-title" id="emailNotFoundTitle">Email Not Found</h2>
        <p class="modal-description">
            The email address you entered is not registered. Please check the email or ask an administrator to create your account.
        </p>
        <button type="button" class="modal-button orange" id="emailNotFoundOk" onclick="document.getElementById('emailNotFoundModal').classList.remove('active'); document.getElementById('email').focus();">OK</button>
    </div>
</div>

<!-- Forgot password modal -->
<div class="modal-overlay" id="forgotPasswordModal" aria-hidden="true">
    <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle">
        <button type="button" class="modal-close" id="closeForgotPassword" aria-label="Close">&times;</button>
        <div class="modal-icon-container orange">&#128273;</div>
        <h2 class="modal-title" id="forgotPasswordTitle">Reset Your Password</h2>
        <p class="modal-description" id="forgotPasswordDescription">Enter the email address connected to your account.</p>

        <form class="modal-form" id="forgotPasswordForm">
            <div class="form-group">
                <label for="resetEmail">Email Address</label>
                <input type="email" id="resetEmail" placeholder="Enter email address" autocomplete="email" required>
            </div>
            <button type="submit" class="modal-button orange" id="requestResetBtn">Send Verification Code</button>
        </form>

        <form class="modal-form" id="resetCodeForm" hidden>
            <p class="reset-code-sent">We've sent a 6-digit verification code to</p>
            <p class="reset-code-destination" id="resetCodeDestination"></p>
            <div class="verification-input-group">
                <label for="resetCode">ENTER VERIFICATION CODE</label>
                <input type="text" id="resetCode" placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required>
            </div>
            <div class="reset-code-status" aria-live="polite">
                <span>&#9201; Code expires in <strong id="resetCodeTimer">10:00</strong></span>
            </div>
            <button type="submit" class="modal-button green">Verify &amp; Continue</button>
            <p class="reset-resend-prompt">Didn't receive the code?</p>
            <button type="button" class="reset-link-button" id="resendResetCode">&#10148; Resend Code</button>
            <div class="reset-step-divider"></div>
            <button type="button" class="reset-link-button muted" id="backToResetEmail">&#8592; Back</button>
        </form>

        <form class="modal-form" id="resetPasswordForm" hidden>
            <div class="form-group">
                <label for="resetNewPassword">New Password</label>
                <input type="password" id="resetNewPassword" placeholder="Enter new password" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="resetConfirmPassword">Confirm New Password</label>
                <input type="password" id="resetConfirmPassword" placeholder="Confirm new password" autocomplete="new-password" required>
            </div>
            <div class="login-password-requirements" id="resetPasswordRequirements"
                 data-min-length="<?php echo (int) $loginPasswordPolicy['min_password_length']; ?>">
                <div class="password-requirements-title">Password requirements:</div>
                <div class="requirement-item" data-reset-rule="length">At least <?php echo (int) $loginPasswordPolicy['min_password_length']; ?> characters</div>
                <?php if ((int) $loginPasswordPolicy['require_special_char'] === 1): ?>
                    <div class="requirement-item" data-reset-rule="special">One special character</div>
                <?php endif; ?>
                <?php if ((int) $loginPasswordPolicy['require_number'] === 1): ?>
                    <div class="requirement-item" data-reset-rule="number">One number (0-9)</div>
                <?php endif; ?>
                <?php if ((int) $loginPasswordPolicy['require_uppercase'] === 1): ?>
                    <div class="requirement-item" data-reset-rule="uppercase">One uppercase letter (A-Z)</div>
                <?php endif; ?>
                <div class="requirement-item" data-reset-rule="match">Passwords match</div>
            </div>
            <button type="submit" class="modal-button purple">Reset Password</button>
        </form>
    </div>
</div>

<!-- Modal 1: Verify Your Email (Initial) -->
<div class="modal-overlay" id="verifyEmailModal1">
    <div class="modal-container">
        <button type="button" class="modal-close" data-first-login-exit aria-label="Exit first-time login">&times;</button>
        <div class="modal-icon-container orange">✉</div>
        <h2 class="modal-title">Verify Your Identity</h2>
        <p class="modal-description">Before setting a password, verify that you own this account.</p>
        <div class="info-box blue">We will send a one-time password (OTP) to the email registered to this account.</div>
        <button type="button" class="modal-button orange" id="sendVerificationBtn">Send OTP</button>
    </div>
</div>

<!-- Modal 2: Verify Your Email (Code Sent) -->
<div class="modal-overlay" id="verifyEmailModal2">
    <div class="modal-container">
        <button type="button" class="modal-close" data-first-login-exit aria-label="Exit first-time login">&times;</button>
        <div class="modal-icon-container green">✉</div>
        <h2 class="modal-title">Enter Your OTP</h2>
        <p class="modal-description">Enter the one-time password sent to your registered email address.</p>
        <div class="info-box green">The OTP expires in 10 minutes and can be used once.</div>
        <div class="verification-input-group">
            <label for="verificationCode">6-Digit OTP</label>
            <input
                type="text"
                id="verificationCode"
                placeholder="000000"
                maxlength="6"
                pattern="[0-9]{6}"
            >
        </div>
        <button type="button" class="modal-button green" id="verifyEmailBtn">Verify Email</button>
        <p class="otp-resend-prompt">Didn't receive the code?</p>
        <button type="button" class="reset-link-button" id="resendVerificationCode">&#10148; Resend Code</button>
        <div class="otp-resend-status" id="resendVerificationStatus" aria-live="polite"></div>
    </div>
</div>

<!-- Modal 3: Set Your Password -->
<div class="modal-overlay" id="setPasswordModal">
    <div class="modal-container">
        <button type="button" class="modal-close" data-first-login-exit aria-label="Exit first-time login">&times;</button>
        <div class="modal-icon-container purple">
            <span class="key-icon">🔑</span>
        </div>
        <h2 class="modal-title">Set Your Password</h2>
        <p class="modal-subtitle">Create a secure password for your account</p>

        <div class="form-group">
            <label for="newPassword">New Password</label>
            <div class="password-input-wrapper">
                <span class="password-icon">🔑</span>
                <input
                    type="password"
                    id="newPassword"
                    placeholder="Create a password"
                >
                <button type="button" class="password-toggle hidden" id="toggleNewPassword" title="Show password" aria-label="Show password">
                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                        <line class="eye-slash" x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <div class="password-strength">
                <div class="password-strength-label">Password strength:</div>
                <div class="password-strength-bar">
                    <div class="password-strength-fill" id="strengthBar"></div>
                </div>
                <div class="password-strength-text" id="strengthText">Strong</div>
            </div>
        </div>

        <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <div class="password-input-wrapper">
                <span class="password-icon">🔑</span>
                <input
                    type="password"
                    id="confirmPassword"
                    placeholder="Confirm Password"
                >
                <button type="button" class="password-toggle hidden" id="toggleConfirmPassword" title="Show password" aria-label="Show password">
                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                        <line class="eye-slash" x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="password-requirements">
            <div class="password-requirements-title">Password must include:</div>
            <div class="requirement-item" data-rule="length">At least <?php echo (int) $loginPasswordPolicy['min_password_length']; ?> characters</div>
            <?php if ((int) $loginPasswordPolicy['require_uppercase'] === 1): ?><div class="requirement-item" data-rule="uppercase">One uppercase letter (A-Z)</div><?php endif; ?>
            <?php if ((int) $loginPasswordPolicy['require_number'] === 1): ?><div class="requirement-item" data-rule="number">One number (0-9)</div><?php endif; ?>
            <?php if ((int) $loginPasswordPolicy['require_special_char'] === 1): ?><div class="requirement-item" data-rule="special">One special character</div><?php endif; ?>
            <div class="requirement-item" data-rule="common">Not a common password</div>
            <div class="requirement-item" data-rule="match">Passwords match</div>
        </div>

        <button type="button" class="modal-button purple" id="setPasswordBtn">Set Password & Continue</button>
    </div>
</div>
</body>
</html>

<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Adjust as needed
$password = ""; // Adjust as needed
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Successful login
            $_SESSION['user_id'] = $id;
            $_SESSION['email'] = $email;
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippians CDO - Payroll Management System</title>
    <script src="js/index.js" defer></script>

    <link rel="stylesheet" href="css/index.css">

</head>
<body>
    <?php if (isset($error)): ?>
        <div class="error-message" style="color: red; text-align: center; margin-bottom: 10px;"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="login-container">
        <div class="header">
            <h1>Philippians CDO</h1>
            <p>Payroll Management System</p>
        </div>

        <form id="loginForm" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter email address" 
                    required
                >
                <div class="error-message" id="emailError"></div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter password" 
                    required
                >
                <div class="error-message" id="passwordError"></div>
            </div>

            <button type="submit" class="login-button">Log in</button>
        </form>

        <div class="forgot-password">
            <a href="#" id="forgotPasswordLink">Forgot password?</a>
        </div>
    </div>

    <!-- Modal 1: Verify Your Email (Initial) -->
    <div class="modal-overlay" id="verifyEmailModal1">
        <div class="modal-container">
            <div class="modal-icon-container orange">✉</div>
            <h2 class="modal-title">Verify Your Email</h2>
            <p class="modal-description">This is your first time logging in. Please verify your email address to continue.</p>
            <div class="info-box blue">We'll send a verification code to your registered email address.</div>
            <button type="button" class="modal-button orange" id="sendVerificationBtn">Send Verification Code</button>
        </div>
    </div>

    <!-- Modal 2: Verify Your Email (Code Sent) -->
    <div class="modal-overlay" id="verifyEmailModal2">
        <div class="modal-container">
            <div class="modal-icon-container green">✉</div>
            <h2 class="modal-title">Verify Your Email</h2>
            <p class="modal-description">This is your first time logging in. Please verify your email address to continue.</p>
            <div class="info-box green">Verification code sent! Check your email and enter the code below.</div>
            <div class="verification-input-group">
                <label for="verificationCode">Verification Code</label>
                <input 
                    type="text" 
                    id="verificationCode" 
                    placeholder="000000" 
                    maxlength="6"
                    pattern="[0-9]{6}"
                >
            </div>
            <button type="button" class="modal-button green" id="verifyEmailBtn">Verify Email</button>
        </div>
    </div>

    <!-- Modal 3: Set Your Password -->
    <div class="modal-overlay" id="setPasswordModal">
        <div class="modal-container">
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
                    <button type="button" class="password-toggle hidden" id="toggleNewPassword" title="Show password">
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
                        type="text" 
                        id="confirmPassword" 
                        placeholder="Confirm Password"
                    >
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" title="Hide password">
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

    <div class="requirement-item" data-rule="length">At least 8 characters</div>
    <div class="requirement-item" data-rule="uppercase">One uppercase letter (A-Z)</div>
    <div class="requirement-item" data-rule="lowercase">One lowercase letter (a-z)</div>
    <div class="requirement-item" data-rule="number">One number (0-9)</div>
    <div class="requirement-item" data-rule="special">One special character (!@#$%^&*)</div>
    <div class="requirement-item" data-rule="common">Not a common password</div>
    <div class="requirement-item" data-rule="match">Passwords match</div>
</div>

            <button type="button" class="modal-button purple" id="setPasswordBtn">Set Password & Continue</button>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippians CDO - Payroll Management System</title>

    <link rel="stylesheet" href="css/index.css">

</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>Philippians CDO</h1>
            <p>Payroll Management System</p>
        </div>

        <form id="loginForm">
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

   <script>
    // Form validation and submission
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');

    // Email validation
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function clearErrors() {
        emailError.textContent = '';
        emailError.classList.remove('show');
        passwordError.textContent = '';
        passwordError.classList.remove('show');
    }

    function showError(errorElement, message) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        let isValid = true;

        if (!email) {
            showError(emailError, 'Email address is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            showError(emailError, 'Please enter a valid email address');
            isValid = false;
        }

        if (!password) {
            showError(passwordError, 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            showError(passwordError, 'Password must be at least 6 characters');
            isValid = false;
        }

        if (isValid) {
            document.body.classList.add('modal-active');
            document.getElementById('verifyEmailModal1').classList.add('active');
        }
    });

    // ---------------- MODALS ----------------
    const verifyEmailModal1 = document.getElementById('verifyEmailModal1');
    const verifyEmailModal2 = document.getElementById('verifyEmailModal2');
    const setPasswordModal = document.getElementById('setPasswordModal');
    const sendVerificationBtn = document.getElementById('sendVerificationBtn');
    const verifyEmailBtn = document.getElementById('verifyEmailBtn');
    const verificationCodeInput = document.getElementById('verificationCode');

    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const setPasswordBtn = document.getElementById('setPasswordBtn');

    sendVerificationBtn.addEventListener('click', function() {
        verifyEmailModal1.classList.remove('active');
        verifyEmailModal2.classList.add('active');
        verificationCodeInput.focus();
    });

    verifyEmailBtn.addEventListener('click', function() {
        if (verificationCodeInput.value.trim().length === 6) {
            verifyEmailModal2.classList.remove('active');
            setPasswordModal.classList.add('active');
        } else {
            alert('Please enter a 6-digit verification code');
        }
    });

    verificationCodeInput.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    // ---------------- PASSWORD TOGGLES ----------------
    toggleNewPassword.addEventListener('click', () => {
        newPasswordInput.type =
            newPasswordInput.type === 'password' ? 'text' : 'password';
    });

    toggleConfirmPassword.addEventListener('click', () => {
        confirmPasswordInput.type =
            confirmPasswordInput.type === 'password' ? 'text' : 'password';
    });

    // ---------------- PASSWORD REQUIREMENTS ----------------
    const requirementItems = document.querySelectorAll('.requirement-item');
    const commonPasswords = ['password', '12345678', 'qwerty', 'admin', 'letmein'];

    function validatePasswordRequirements() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        const rules = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*]/.test(password),
            common: !commonPasswords.includes(password.toLowerCase()),
            match: password && password === confirmPassword
        };

        let allValid = true;

        requirementItems.forEach(item => {
            const rule = item.dataset.rule;
            if (rules[rule]) {
                item.classList.add('valid');
                item.classList.remove('invalid');
            } else {
                item.classList.add('invalid');
                item.classList.remove('valid');
                allValid = false;
            }
        });

        setPasswordBtn.disabled = !allValid;
    }

    newPasswordInput.addEventListener('input', validatePasswordRequirements);
    confirmPasswordInput.addEventListener('input', validatePasswordRequirements);

    // Disable initially
    setPasswordBtn.disabled = true;

    // ---------------- SAFETY CHECK (NO BYPASS) ----------------
    setPasswordBtn.addEventListener('click', function () {
        if (setPasswordBtn.disabled) return;

        window.location.href = 'admin/dashboard.html';
    });
</script>

</body>
</html>

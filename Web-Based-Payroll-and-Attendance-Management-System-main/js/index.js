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
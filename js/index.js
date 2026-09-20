
    // Form validation and submission
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const toggleLoginPassword = document.getElementById('toggleLoginPassword');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const captchaInput = document.getElementById('captchaAnswer');
    const captchaQuestion = document.getElementById('captchaQuestion');
    const captchaError = document.getElementById('captchaError');
    const captchaReroll = document.getElementById('captchaReroll');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const openRegisterModalLink = document.getElementById('openRegisterModal');
    const registerModal = document.getElementById('registerModal');
    const closeRegisterModalBtn = document.getElementById('closeRegisterModal');
    const registerForm = document.getElementById('registerForm');
    const registerEmailInput = document.getElementById('registerEmail');
    const emailNotFoundModal = document.getElementById('emailNotFoundModal');
    const closeEmailNotFoundBtn = document.getElementById('closeEmailNotFound');
    const emailNotFoundOkBtn = document.getElementById('emailNotFoundOk');

    function clearErrors() {
        emailError.textContent = '';
        emailError.classList.remove('show');
        passwordError.textContent = '';
        passwordError.classList.remove('show');
        captchaError?.classList.remove('show');
        captchaInput?.classList.remove('input-error', 'input-valid');
    }

    function showError(errorElement, message) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    function validateCaptcha(showEmptyError = false) {
        if (!captchaInput || !captchaQuestion || !captchaError) return true;
        const entered = captchaInput.value.trim();
        const expected = String(captchaQuestion.dataset.answer || '');

        if (!entered) {
            captchaInput.classList.remove('input-error', 'input-valid');
            captchaInput.setCustomValidity(showEmptyError ? 'Please answer the CAPTCHA.' : '');
            captchaError.textContent = showEmptyError ? 'Please answer the CAPTCHA.' : '';
            captchaError.classList.toggle('show', showEmptyError);
            return false;
        }

        const isCorrect = entered === expected;
        captchaInput.classList.toggle('input-error', !isCorrect);
        captchaInput.classList.toggle('input-valid', isCorrect);
        captchaError.textContent = isCorrect ? '' : 'Incorrect answer. Please check the math and try again.';
        captchaError.classList.toggle('show', !isCorrect);
        captchaInput.setCustomValidity(isCorrect ? '' : 'Incorrect CAPTCHA answer.');
        return isCorrect;
    }

    captchaInput?.addEventListener('input', () => validateCaptcha(false));

    captchaReroll?.addEventListener('click', async () => {
        if (!captchaQuestion || !captchaInput) return;

        captchaReroll.disabled = true;
        try {
            const response = await fetch('api/reroll_login_captcha.php', { method: 'POST' });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to get a new CAPTCHA question.');
            }

            captchaQuestion.textContent = result.question;
            captchaQuestion.dataset.answer = String(result.answer);
            captchaInput.value = '';
            captchaInput.setCustomValidity('');
            captchaInput.classList.remove('input-error', 'input-valid');
            captchaError.textContent = '';
            captchaError.classList.remove('show');
            captchaInput.focus();
        } catch (error) {
            captchaError.textContent = error.message || 'Unable to get a new CAPTCHA question.';
            captchaError.classList.add('show');
        } finally {
            captchaReroll.disabled = false;
        }
    });

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        let isValid = true;

        if (!email) {
            showError(emailError, 'Email address is required');
            isValid = false;
        }

        if (!password) {
            showError(passwordError, 'Password is required');
            isValid = false;
        }

        if (!validateCaptcha(true)) {
            isValid = false;
        }

        if (isValid) {
            // Allow form submission for PHP processing
            window.showProcessingModal?.('Processing, please wait...');
            loginForm.submit();
        }
    });

    // ---------------- MODALS ----------------
    const verifyEmailModal1 = document.getElementById('verifyEmailModal1');
    const verifyEmailModal2 = document.getElementById('verifyEmailModal2');
    const setPasswordModal = document.getElementById('setPasswordModal');
    const sendVerificationBtn = document.getElementById('sendVerificationBtn');
    const verifyEmailBtn = document.getElementById('verifyEmailBtn');
    const verificationCodeInput = document.getElementById('verificationCode');
    const resendVerificationCode = document.getElementById('resendVerificationCode');
    const resendVerificationStatus = document.getElementById('resendVerificationStatus');

    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const setPasswordBtn = document.getElementById('setPasswordBtn');
    const firstTimePasswordFlow = window.FIRST_TIME_PASSWORD_FLOW === true;

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (closeEmailNotFoundBtn) {
        closeEmailNotFoundBtn.addEventListener('click', () => closeModal(emailNotFoundModal));
    }

    if (emailNotFoundOkBtn) {
        emailNotFoundOkBtn.addEventListener('click', () => {
            closeModal(emailNotFoundModal);
            emailInput.focus();
        });
    }

    if (emailNotFoundModal) {
        emailNotFoundModal.addEventListener('click', event => {
            if (event.target === emailNotFoundModal) closeModal(emailNotFoundModal);
        });
    }

    // ---------------- FORGOT PASSWORD ----------------
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const closeForgotPasswordBtn = document.getElementById('closeForgotPassword');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const resetCodeForm = document.getElementById('resetCodeForm');
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const forgotPasswordDescription = document.getElementById('forgotPasswordDescription');
    const resetEmailInput = document.getElementById('resetEmail');
    const resetCodeInput = document.getElementById('resetCode');
    const resetCodeDestination = document.getElementById('resetCodeDestination');
    const resetCodeTimer = document.getElementById('resetCodeTimer');
    const resendResetCode = document.getElementById('resendResetCode');
    const backToResetEmail = document.getElementById('backToResetEmail');
    const resetNewPassword = document.getElementById('resetNewPassword');
    const resetConfirmPassword = document.getElementById('resetConfirmPassword');
    const resetPasswordRequirements = document.getElementById('resetPasswordRequirements');
    let resetTimerId = null;

    function validateResetPassword(showInvalid = false) {
        const password = resetNewPassword.value;
        const rules = {
            length: password.length >= Number(resetPasswordRequirements.dataset.minLength || 1),
            special: /[^A-Za-z0-9]/.test(password),
            number: /\d/.test(password),
            uppercase: /[A-Z]/.test(password),
            match: Boolean(password) && password === resetConfirmPassword.value
        };
        let valid = true;

        resetPasswordRequirements.querySelectorAll('[data-reset-rule]').forEach(item => {
            const passed = Boolean(rules[item.dataset.resetRule]);
            item.classList.toggle('valid', passed);
            item.classList.toggle('invalid', showInvalid && !passed);
            if (!passed) valid = false;
        });
        return valid;
    }

    resetNewPassword.addEventListener('input', () => validateResetPassword(false));
    resetConfirmPassword.addEventListener('input', () => validateResetPassword(false));

    function maskEmail(email) {
        const [name, domain] = email.split('@');
        return domain ? `${name.slice(0, 2)}${'*'.repeat(Math.max(2, name.length - 2))}@${domain}` : email;
    }

    function startResetTimer(seconds = 600) {
        clearInterval(resetTimerId);
        const expiresAt = Date.now() + seconds * 1000;
        const update = () => {
            const left = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
            resetCodeTimer.textContent = `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`;
            if (!left) clearInterval(resetTimerId);
        };
        update();
        resetTimerId = setInterval(update, 1000);
    }

    function enterResetCodeStep(result) {
        forgotPasswordDescription.textContent = result.message || 'Enter the code below to continue resetting your password.';
        resetCodeDestination.textContent = maskEmail(resetEmailInput.value.trim());
        resetCodeInput.value = '';
        showResetStep('code');
        startResetTimer(Number(result.expires_in || 600));
        resetCodeInput.focus();
    }

    function showResetStep(step) {
        forgotPasswordForm.hidden = step !== 'email';
        resetCodeForm.hidden = step !== 'code';
        resetPasswordForm.hidden = step !== 'password';
    }

    function exitFirstTimePasswordFlow() {
        return fetch('api/cancel_first_login.php', { method: 'POST' })
            .catch(() => null)
            .finally(() => { window.location.href = 'index.php'; });
    }

    if (firstTimePasswordFlow) {
        // Use the familiar Reset Password layout for the initial screen, but
        // lock it to the account that has just passed password authentication.
        // The server, not the displayed input, chooses the OTP destination.
        showResetStep('email');
        resetEmailInput.value = String(window.FIRST_TIME_LOGIN_EMAIL || '');
        resetEmailInput.readOnly = true;
        resetEmailInput.setAttribute('aria-readonly', 'true');
        resetEmailInput.title = 'Verification codes are sent only to this account email.';
        forgotPasswordDescription.textContent = 'Enter the email address connected to your account.';
    }

    forgotPasswordLink.addEventListener('click', function (event) {
        event.preventDefault();
        showResetStep('email');
        resetEmailInput.value = emailInput.value.trim();
        forgotPasswordDescription.textContent = 'Enter the email address connected to your account.';
        forgotPasswordModal.setAttribute('aria-hidden', 'false');
        openModal(forgotPasswordModal);
        resetEmailInput.focus();
    });

    closeForgotPasswordBtn.addEventListener('click', () => {
        if (firstTimePasswordFlow) {
            exitFirstTimePasswordFlow();
            return;
        }
        closeModal(forgotPasswordModal);
    });
    forgotPasswordModal.addEventListener('click', event => {
        if (event.target !== forgotPasswordModal) return;
        if (firstTimePasswordFlow) {
            exitFirstTimePasswordFlow();
            return;
        }
        closeModal(forgotPasswordModal);
    });

    forgotPasswordForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (firstTimePasswordFlow) {
            const result = await postJson('api/start_first_login_verification.php', {});
            if (!result.success) return showResetMessage(false, result.message);
            closeModal(forgotPasswordModal);
            verificationCodeInput.value = '';
            openModal(verifyEmailModal2);
            verificationCodeInput.focus();
            return;
        }
        const result = await postJson('api/start_password_reset.php', { email: resetEmailInput.value.trim() });
        if (!result.success) return showResetMessage(false, result.message);
        enterResetCodeStep(result);
    });

    resetCodeInput.addEventListener('input', event => {
        event.target.value = event.target.value.replace(/\D/g, '');
    });

    resetCodeForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const result = await postJson('api/verify_reset_code.php', { code: resetCodeInput.value.trim() });
        if (!result.success) return showResetMessage(false, result.message);
        clearInterval(resetTimerId);
        forgotPasswordDescription.textContent = 'Create a new secure password for your account.';
        showResetStep('password');
        document.getElementById('resetNewPassword').focus();
    });

    resendResetCode.addEventListener('click', async function () {
        resendResetCode.disabled = true;
        const result = await postJson('api/start_password_reset.php', { email: resetEmailInput.value.trim() });
        resendResetCode.disabled = false;
        if (!result.success) return showResetMessage(false, result.message);
        enterResetCodeStep(result);
        showResetMessage(true, 'A new verification code has been sent.');
    });

    backToResetEmail.addEventListener('click', function () {
        clearInterval(resetTimerId);
        forgotPasswordDescription.textContent = 'Enter the email address connected to your account.';
        showResetStep('email');
        resetEmailInput.focus();
    });

    resetPasswordForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const password = resetNewPassword.value;
        const confirmation = resetConfirmPassword.value;
        if (password !== confirmation) return showResetMessage(false, 'Passwords do not match.');
        if (!validateResetPassword(true)) return showResetMessage(false, 'Password does not meet the enabled security requirements.');
        const result = await postJson('api/reset_password.php', { password });
        if (!result.success) return showResetMessage(false, result.message);
        closeModal(forgotPasswordModal);
        resetPasswordForm.reset();
        showResetMessage(true, result.message);
    });

    function showResetMessage(success, message) {
        if (window.showCrudResultModal) window.showCrudResultModal(success, message, 'Password Reset');
        else alert(message);
    }

    async function postJson(url, body) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            return await response.json();
        } catch (error) {
            return { success: false, message: 'Something went wrong. Please try again.' };
        }
    }

    if (openRegisterModalLink && registerModal) {
        openRegisterModalLink.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(registerModal);
        });
    }

    if (closeRegisterModalBtn && registerModal) {
        closeRegisterModalBtn.addEventListener('click', function() {
            closeModal(registerModal);
        });
    }

    if (registerModal) {
        registerModal.addEventListener('click', function(e) {
            if (e.target === registerModal) {
                closeModal(registerModal);
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = registerEmailInput ? registerEmailInput.value.trim() : '';

            if (!email) {
                alert('Email address is required.');
                return;
            }

            const response = await fetch('api/start_registration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            const result = await response.json();

            if (!result.success) {
                alert(result.message);
                return;
            }

            closeModal(registerModal);
            openModal(verifyEmailModal1);
        });
    }

    document.querySelectorAll('[data-first-login-exit]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (firstTimePasswordFlow) {
                exitFirstTimePasswordFlow();
                return;
            }
            closeModal(verifyEmailModal1);
            closeModal(verifyEmailModal2);
            closeModal(setPasswordModal);
        });
    });

    async function requestVerificationCode(isResend = false) {
        const trigger = isResend ? resendVerificationCode : sendVerificationBtn;
        if (trigger) trigger.disabled = true;
        if (isResend && resendVerificationStatus) resendVerificationStatus.textContent = 'Sending a new code...';

        try {
        const response = await fetch(firstTimePasswordFlow ? 'api/start_first_login_verification.php' : 'api/send_verification.php', {
            method: firstTimePasswordFlow ? 'POST' : 'GET'
        });
        const result = await response.json();
        if (result.success) {
            if (!isResend) {
                alert(result.message);
                verifyEmailModal1.classList.remove('active');
                verifyEmailModal2.classList.add('active');
            }
            if (resendVerificationStatus) resendVerificationStatus.textContent = isResend ? 'A new code was sent. Your previous code is no longer valid.' : '';
            verificationCodeInput.value = '';
            verificationCodeInput.focus();
        } else {
            if (isResend && resendVerificationStatus) resendVerificationStatus.textContent = result.message || 'Unable to resend the code.';
            else alert(result.message);
        }
        } catch (error) {
            if (isResend && resendVerificationStatus) resendVerificationStatus.textContent = 'Unable to resend the code. Please try again.';
            else alert('Unable to send the verification code. Please try again.');
        } finally {
            if (trigger) trigger.disabled = false;
        }
    }

    sendVerificationBtn.addEventListener('click', () => requestVerificationCode());
    resendVerificationCode?.addEventListener('click', () => requestVerificationCode(true));

    verifyEmailBtn.addEventListener('click', async function() {
        const code = verificationCodeInput.value.trim();
        if (code.length !== 6) {
            alert('Please enter a 6-digit verification code');
            return;
        }
        const response = await fetch(firstTimePasswordFlow ? 'api/verify_first_login_otp.php' : 'api/verify_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code })
        });
        const result = await response.json();
        if (result.success) {
            verifyEmailModal2.classList.remove('active');
            setPasswordModal.classList.add('active');
        } else {
            alert(result.message);
        }
    });

    verificationCodeInput.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    // ---------------- PASSWORD TOGGLES ----------------
    function togglePasswordVisibility(input, button) {
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.classList.toggle('hidden', !showing);
        button.title = showing ? 'Hide password' : 'Show password';
        button.setAttribute('aria-label', button.title);
    }

    toggleLoginPassword?.addEventListener('click', () => {
        const showing = passwordInput.type === 'password';
        passwordInput.type = showing ? 'text' : 'password';
        toggleLoginPassword.textContent = showing ? 'Hide' : 'Show';
        toggleLoginPassword.title = showing ? 'Hide password' : 'Show password';
        toggleLoginPassword.setAttribute('aria-label', toggleLoginPassword.title);
        passwordInput.focus();
    });

    toggleNewPassword.addEventListener('click', () => togglePasswordVisibility(newPasswordInput, toggleNewPassword));
    toggleConfirmPassword.addEventListener('click', () => togglePasswordVisibility(confirmPasswordInput, toggleConfirmPassword));

    // ---------------- PASSWORD REQUIREMENTS ----------------
    const requirementItems = document.querySelectorAll('#setPasswordModal [data-rule]');
    const commonPasswords = ['password', '12345678', 'qwerty', 'admin', 'letmein'];
    const permanentPasswordMinimum = Number(document.getElementById('resetPasswordRequirements')?.dataset.minLength || 8);
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    function validatePasswordRequirements() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        const rules = {
            length: password.length >= permanentPasswordMinimum,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
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

        const passedCount = Array.from(requirementItems).filter(item => rules[item.dataset.rule]).length;
        const strength = requirementItems.length ? Math.round((passedCount / requirementItems.length) * 100) : 0;
        strengthBar.style.width = strength + '%';
        strengthText.textContent = strength === 100 ? 'Strong' : (strength >= 60 ? 'Medium' : 'Weak');
        strengthBar.style.backgroundColor = strength === 100 ? '#28a745' : (strength >= 60 ? '#f59e0b' : '#dc3545');
        strengthText.style.color = strengthBar.style.backgroundColor;

        setPasswordBtn.disabled = !allValid;
    }

    newPasswordInput.addEventListener('input', validatePasswordRequirements);
    confirmPasswordInput.addEventListener('input', validatePasswordRequirements);

    // Disable initially
    setPasswordBtn.disabled = true;
    validatePasswordRequirements();

    // ---------------- SAFETY CHECK (NO BYPASS) ----------------
    setPasswordBtn.addEventListener('click', async function () {
        if (setPasswordBtn.disabled) return;

        const password = newPasswordInput.value;
        setPasswordBtn.disabled = true;
        setPasswordBtn.textContent = 'Saving...';
        try {
            const response = await fetch('api/set_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password })
            });
            const result = await response.json();
            if (result.success) {
                window.location.href = result.redirect || 'index.php';
                return;
            }
            alert(result.message || 'Unable to set the password.');
        } catch (error) {
            alert('Unable to set the password. Please try again.');
        } finally {
            setPasswordBtn.textContent = 'Set Password & Continue';
            validatePasswordRequirements();
        }
    });

(() => {
    const checks = new Map();
    window.resetUserEmailAvailability = mode => {
        checks.get(mode)?.controller?.abort();
        checks.delete(mode);
    };
    window.checkUserEmailAvailability = function (mode) {
        const prefix = mode === 'edit' ? 'editUser' : 'newUser';
        const field = document.getElementById(`${prefix}Email`);
        const message = document.getElementById(`${prefix}EmailError`);
        const first = document.getElementById(`${prefix}FirstName`);
        const last = document.getElementById(`${prefix}LastName`);
        if (!field) return false;
        const email = field.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            window.resetUserEmailAvailability(mode);
            field.setCustomValidity('Enter a valid email address.');
            return false;
        }
        const fullName = `${first?.value.trim() || ''} ${last?.value.trim() || ''}`.trim();
        const excludeId = mode === 'edit' ? document.getElementById('editUserId')?.value || '0' : '0';
        const key = JSON.stringify([excludeId, email.toLowerCase(), fullName.toLowerCase()]);
        let state = checks.get(mode);
        if (!state || state.key !== key || (state.status === 'done' && Date.now() - state.checkedAt > 15000)) {
            window.resetUserEmailAvailability(mode);
            state = { key, status: 'pending' };
            checks.set(mode, state);
            const current = state;
            setTimeout(async () => {
                if (checks.get(mode) !== current) return;
                current.controller = new AbortController();
                const timeout = setTimeout(() => current.controller.abort(), 8000);
                try {
                    const params = new URLSearchParams({ email, full_name: fullName, exclude_id: excludeId });
                    const response = await fetch(`../api/check_user_email.php?${params}`, {
                        signal: current.controller.signal, cache: 'no-store', headers: { Accept: 'application/json' }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success || typeof data.available !== 'boolean' || typeof data.name_available !== 'boolean') throw new Error('Check failed');
                    current.status = 'done';
                    current.emailAvailable = data.available;
                    current.nameAvailable = data.name_available;
                    current.checkedAt = Date.now();
                } catch {
                    current.status = 'error';
                } finally {
                    clearTimeout(timeout);
                }
                if (checks.get(mode) === current) window.validateUserIdentityFields(mode);
            }, 250);
        }
        const emailValid = state.status === 'done' && state.emailAvailable;
        const text = state.status === 'pending' ? 'Checking account availability...'
            : state.status === 'error' ? 'Could not verify availability. Please retry.'
            : emailValid ? 'Email is available.' : 'This email address is already registered.';
        field.classList.toggle('field-valid', emailValid);
        field.classList.toggle('field-invalid', state.status === 'error' || (state.status === 'done' && !emailValid));
        field.setCustomValidity(emailValid ? '' : text);
        field.setAttribute('aria-invalid', emailValid ? 'false' : 'true');
        if (message) {
            message.textContent = text;
            message.classList.toggle('valid', emailValid);
            message.setAttribute('aria-live', 'polite');
            if (state.status === 'error') {
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.textContent = 'Retry check';
                retry.onclick = () => { window.resetUserEmailAvailability(mode); window.validateUserIdentityFields(mode); };
                message.append(' ', retry);
            }
        }
        if (state.status !== 'done' || !state.nameAvailable) {
            [first, last].forEach(input => {
                input?.classList.remove('field-valid');
                input?.classList.toggle('field-invalid', state.status === 'done' && !state.nameAvailable);
            });
            const nameMessage = document.getElementById(`${prefix}LastNameError`);
            if (nameMessage) {
                nameMessage.classList.remove('valid');
                nameMessage.textContent = state.status === 'done'
                    ? 'This first and last name combination is already registered.'
                    : state.status === 'pending' ? 'Checking full name...' : 'Full name could not be verified.';
            }
        }
        return Boolean(emailValid && state.nameAvailable);
    };
})();

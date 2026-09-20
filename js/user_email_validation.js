(() => {
    const checks = new Map();
    window.checkUserEmailAvailability = function (mode) {
        const prefix = mode === 'edit' ? 'editUser' : 'newUser';
        const field = document.getElementById(`${prefix}Email`);
        const message = document.getElementById(`${prefix}EmailError`);
        if (!field) return false;
        const email = field.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            field.setCustomValidity('');
            checks.delete(mode);
            return false;
        }
        const excludeId = mode === 'edit' ? document.getElementById('editUserId')?.value || '0' : '0';
        const key = `${excludeId}:${email.toLowerCase()}`;
        let state = checks.get(mode);
        if (!state || state.key !== key) {
            state = { key, status: 'pending' };
            checks.set(mode, state);
            const current = state;
            setTimeout(async () => {
                if (checks.get(mode) !== current) return;
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 8000);
                try {
                    const params = new URLSearchParams({ email, exclude_id: excludeId });
                    const response = await fetch(`../api/check_user_email.php?${params}`, { signal: controller.signal, cache: 'no-store', headers: { Accept: 'application/json' } });
                    const data = await response.json();
                    if (!response.ok || !data.success || typeof data.available !== 'boolean') throw new Error('Check failed');
                    current.status = data.available ? 'available' : 'duplicate';
                } catch {
                    current.status = 'error';
                } finally {
                    clearTimeout(timeout);
                }
                if (checks.get(mode) === current) window.validateUserIdentityFields(mode);
            }, 250);
        }
        // The save endpoint remains authoritative if the advisory check fails.
        const valid = state.status === 'available' || state.status === 'error';
        const text = state.status === 'error' ? 'Email will be checked when saving.' : valid ? 'Email is available.' : state.status === 'duplicate'
            ? 'This email address is already registered.' : state.status === 'pending'
                ? 'Checking email availability...' : '';
        field.classList.toggle('field-valid', valid);
        field.classList.toggle('field-invalid', state.status === 'duplicate');
        field.setCustomValidity(valid ? '' : text);
        field.setAttribute('aria-invalid', valid ? 'false' : 'true');
        if (message) {
            message.textContent = text;
            message.classList.toggle('valid', valid);
            message.setAttribute('aria-live', 'polite');
        }
        return valid;
    };
    window.resetUserEmailAvailability = mode => checks.delete(mode);
})();

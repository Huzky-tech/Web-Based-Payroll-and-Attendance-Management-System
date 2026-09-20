document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.body.querySelector(':scope > .sidebar');
    const header = document.querySelector('.main-content > .top-header');
    if (!sidebar || !header) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'mobile-nav-toggle';
    button.setAttribute('aria-label', 'Open navigation menu');
    button.setAttribute('aria-expanded', 'false');
    button.innerHTML = '<i class="fas fa-bars"></i>';

    const backdrop = document.createElement('div');
    backdrop.className = 'mobile-nav-backdrop';
    header.prepend(button);
    document.body.append(backdrop);

    const closeMenu = () => {
        document.body.classList.remove('mobile-nav-open');
        button.setAttribute('aria-expanded', 'false');
    };

    button.addEventListener('click', () => {
        const open = !document.body.classList.contains('mobile-nav-open');
        document.body.classList.toggle('mobile-nav-open', open);
        button.setAttribute('aria-expanded', String(open));
    });
    backdrop.addEventListener('click', closeMenu);
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMenu();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const fieldSelector = 'input:not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]), select, textarea';
    const ignoredTypes = new Set(['checkbox', 'radio', 'file', 'image', 'range', 'color']);

    function shouldValidateField(field) {
        if (!field || field.disabled || field.readOnly || field.dataset.liveValidation === 'off') {
            return false;
        }

        if (field.matches('[data-no-live-validation], .no-live-validation')) {
            return false;
        }

        const type = String(field.type || '').toLowerCase();
        return !ignoredTypes.has(type) && typeof field.checkValidity === 'function';
    }

    function getFieldLabel(field) {
        const escapedId = field.id && window.CSS?.escape ? CSS.escape(field.id) : '';
        const explicitLabel = escapedId ? document.querySelector(`label[for="${escapedId}"]`) : null;
        const wrappedLabel = field.closest('label');
        const ariaLabel = field.getAttribute('aria-label');
        const placeholder = field.getAttribute('placeholder');
        const name = field.getAttribute('name') || field.id || 'This field';
        const nearbyLabel = field.closest('.form-group, .profile-field')?.querySelector('label, .profile-field-label');
        const labelText = explicitLabel?.textContent || wrappedLabel?.textContent || ariaLabel || nearbyLabel?.textContent || placeholder || name.replace(/_/g, ' ').replace(/^./, letter => letter.toUpperCase());

        return labelText.replace(/\s+/g, ' ').replace(/[:*]$/, '').trim() || 'This field';
    }

    function getErrorMessage(field) {
        const validity = field.validity;
        const label = getFieldLabel(field);

        if (field.dataset.errorMessage) {
            return field.dataset.errorMessage;
        }
        if (validity.valueMissing) {
            return `${label} is required.`;
        }
        if (validity.typeMismatch) {
            return `Enter a valid ${String(field.type || 'value').toLowerCase()}.`;
        }
        if (validity.tooShort) {
            return `${label} must be at least ${field.minLength} characters.`;
        }
        if (validity.tooLong) {
            return `${label} must be ${field.maxLength} characters or less.`;
        }
        if (validity.rangeUnderflow) {
            return `${label} must be at least ${field.min}.`;
        }
        if (validity.rangeOverflow) {
            return `${label} must be ${field.max} or less.`;
        }
        if (validity.stepMismatch) {
            return `${label} must use a valid increment.`;
        }
        if (validity.patternMismatch) {
            return field.title || `${label} has an invalid format.`;
        }
        if (validity.badInput) {
            return `${label} has an invalid value.`;
        }

        return field.validationMessage || `${label} is invalid.`;
    }

    function getErrorElement(field) {
        const existingNamedError = document.getElementById(`${field.id}Error`);
        if (existingNamedError) {
            existingNamedError.classList.add('field-live-error');
            return existingNamedError;
        }

        const next = field.nextElementSibling;
        if (next?.classList.contains('field-live-error')) {
            return next;
        }

        const error = document.createElement('div');
        error.className = 'field-live-error';
        error.setAttribute('aria-live', 'polite');
        field.insertAdjacentElement('afterend', error);
        return error;
    }

    function setFieldState(field, forceShow = false) {
        if (!shouldValidateField(field)) {
            return true;
        }

        const touched = field.dataset.liveValidationTouched === '1';
        const hasValue = String(field.value || '').trim() !== '';
        const shouldShow = forceShow || touched || hasValue;
        const isValid = field.checkValidity();
        const error = getErrorElement(field);

        field.classList.toggle('field-live-invalid', shouldShow && !isValid);
        field.classList.toggle('field-live-valid', shouldShow && isValid && hasValue);
        field.setAttribute('aria-invalid', shouldShow && !isValid ? 'true' : 'false');

        if (shouldShow && !isValid) {
            error.textContent = getErrorMessage(field);
            error.classList.add('active');
        } else {
            error.textContent = '';
            error.classList.remove('active');
        }

        return isValid;
    }

    function validateForm(form) {
        let firstInvalid = null;
        form.querySelectorAll(fieldSelector).forEach((field) => {
            field.dataset.liveValidationTouched = '1';
            if (!setFieldState(field, true) && !firstInvalid) {
                firstInvalid = field;
            }
        });

        if (firstInvalid) {
            firstInvalid.focus({ preventScroll: false });
        }
    }

    document.addEventListener('input', (event) => {
        const field = event.target.closest?.(fieldSelector);
        if (!field) return;
        field.dataset.liveValidationTouched = '1';
        setFieldState(field);
    });

    document.addEventListener('change', (event) => {
        const field = event.target.closest?.(fieldSelector);
        if (!field) return;
        field.dataset.liveValidationTouched = '1';
        setFieldState(field);
    });

    document.addEventListener('blur', (event) => {
        const field = event.target.closest?.(fieldSelector);
        if (!field) return;
        field.dataset.liveValidationTouched = '1';
        setFieldState(field);
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        validateForm(form);
    }, true);
});

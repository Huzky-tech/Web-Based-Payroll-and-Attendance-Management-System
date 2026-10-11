(function () {
    const STYLE_ID = 'actionResultModalStyles';
    const MODAL_ID = 'actionResultModal';
    const nativeAlert = window.alert ? window.alert.bind(window) : null;
    const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
    let autoCloseTimer = null;
    let activeProcesses = 0;
    const lockedControls = new Map();
    let lastActionControl = null;

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .action-result-modal {
                position: fixed;
                inset: 0;
                z-index: 30000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: rgba(15, 23, 42, 0.55);
            }

            .action-result-modal.active {
                display: flex;
            }

            .action-result-modal__dialog {
                width: min(520px, 100%);
                border-radius: 12px;
                background: #ffffff;
                box-shadow: 0 30px 90px rgba(2, 6, 23, 0.35);
                border: 1px solid rgba(15, 23, 42, 0.08);
                padding: 22px 22px 18px;
                text-align: left;
                color: #0f172a;
                overflow: hidden;
            }

            .action-result-modal__header {
                display: flex;
                gap: 14px;
                align-items: flex-start;
            }

            .action-result-modal__icon {
                width: 54px;
                height: 54px;
                border-radius: 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                flex: 0 0 auto;
            }

            .action-result-modal__icon.success {
                color: #166534;
                background: #dcfce7;
            }

            .action-result-modal__icon.error {
                color: #b91c1c;
                background: #fee2e2;
            }

            .action-result-modal__icon.processing { color: #2563eb; background: #dbeafe; }
            .action-result-modal__spinner {
                width: 25px; height: 25px; border: 3px solid rgba(37, 99, 235, 0.22);
                border-top-color: #2563eb; border-radius: 50%;
                animation: action-result-spin 0.75s linear infinite;
            }
            @keyframes action-result-spin { to { transform: rotate(360deg); } }

            .action-result-modal__title {
                margin: 0;
                font-size: 1.1rem;
                line-height: 1.25;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .action-result-modal__message {
                margin: 10px 0 0;
                color: #475569;
                line-height: 1.55;
                white-space: pre-line;
                font-size: 0.98rem;
            }

            .action-result-modal__close {
                margin-left: auto;
                border: 0;
                background: transparent;
                color: #64748b;
                cursor: pointer;
                padding: 6px;
                border-radius: 8px;
                line-height: 1;
            }

            .action-result-modal__close:hover,
            .action-result-modal__close:focus {
                background: #f1f5f9;
                color: #0f172a;
                outline: none;
            }

            .action-result-modal__actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 18px;
                padding-top: 14px;
                border-top: 1px solid rgba(15, 23, 42, 0.08);
            }

            .action-result-modal__cancel {
                min-width: 110px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 10px 16px;
                background: #ffffff;
                color: #334155;
                font-weight: 800;
                cursor: pointer;
            }

            .action-result-modal__cancel:hover,
            .action-result-modal__cancel:focus {
                background: #f8fafc;
                outline: none;
            }

            .action-result-modal__ok {
                min-width: 150px;
                border: 0;
                border-radius: 10px;
                padding: 10px 16px;
                font-weight: 900;
                cursor: pointer;
                background: #2563eb;
                color: #ffffff;
            }

            .action-result-modal__ok:hover,
            .action-result-modal__ok:focus {
                background: #1d4ed8;
                outline: none;
            }

            .action-result-modal__ok.destructive {
                background: #dc2626;
            }

            .action-result-modal__ok.destructive:hover,
            .action-result-modal__ok.destructive:focus {
                background: #b91c1c;
                outline: none;
            }
        `;
        document.head.appendChild(style);
    }

    function ensureModal() {
        if (!document.body) {
            return null;
        }

        ensureStyles();

        let modal = document.getElementById(MODAL_ID);
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = MODAL_ID;
        modal.className = 'action-result-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'actionResultModalTitle');
        modal.setAttribute('aria-describedby', 'actionResultModalMessage');
        modal.innerHTML = `
            <div class="action-result-modal__dialog">
                <div class="action-result-modal__header">
                    <div class="action-result-modal__icon success" data-action-result-icon>
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h2 class="action-result-modal__title" id="actionResultModalTitle"></h2>
                        <p class="action-result-modal__message" id="actionResultModalMessage"></p>
                    </div>
                    <button type="button" class="action-result-modal__close" data-action-result-close aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="action-result-modal__actions">
                    <button class="action-result-modal__cancel" type="button" data-action-result-cancel style="display:none;">Cancel</button>
                    <button class="action-result-modal__ok" type="button" data-action-result-ok>OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        return modal;
    }

    function closeActionResultModal() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return;
        if (modal.dataset.processing === 'true') return;

        window.clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
        modal.classList.remove('active');
        document.body.classList.remove('action-result-modal-open');
        const onClose = modal._actionResultOnClose;
        modal._actionResultOnClose = null;
        modal._actionResultResolver = null;
        if (typeof onClose === 'function') {
            onClose();
        }
    }

    function normalizeType(type) {
        return type === 'error' || type === 'danger' || type === 'failed' ? 'error' : 'success';
    }

    function normalizeOptions(options, fallbackType = 'error', fallbackTitle = '') {
        if (typeof options === 'string' || options === null || options === undefined) {
            return {
                message: String(options ?? ''),
                type: fallbackType,
                title: fallbackTitle
            };
        }

        return {
            ...options,
            type: options.type || options.status || fallbackType,
            title: options.title || fallbackTitle
        };
    }

    function showActionResultModal(options) {
        const config = normalizeOptions(options, 'success');
        const type = normalizeType(config.type || config.status);
        const modal = ensureModal();
        if (!modal) {
            if (nativeAlert) {
                nativeAlert(config.message || '');
            }
            return;
        }
        window.clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
        delete modal.dataset.processing;
        const icon = modal.querySelector('[data-action-result-icon]');
        const iconClass = type === 'success' ? 'fa-check' : 'fa-exclamation';
        const title = config.title || (type === 'success' ? 'Action Completed' : 'Action Failed');

        icon.className = `action-result-modal__icon ${type}`;
        icon.innerHTML = `<i class="fas ${iconClass}"></i>`;
        modal.querySelector('#actionResultModalTitle').textContent = title;
        modal.querySelector('#actionResultModalMessage').textContent = config.message || (type === 'success'
            ? 'The operation was completed successfully.'
            : 'The operation could not be completed. Please try again.');
        const okButton = modal.querySelector('[data-action-result-ok]');
        const cancelButton = modal.querySelector('[data-action-result-cancel]');
        const closeBtn = modal.querySelector('[data-action-result-close]');

        okButton.style.display = '';
        if (closeBtn) closeBtn.style.display = '';
        okButton.textContent = config.okText || 'OK';

        // Style destructive actions
        okButton.classList.remove('destructive');
        if (config?.type === 'error' && config?.destructive === true) {
            okButton.classList.add('destructive');
        }

        okButton.onclick = closeActionResultModal;
        cancelButton.style.display = 'none';
        cancelButton.onclick = closeActionResultModal;

        if (closeBtn) {
            closeBtn.onclick = closeActionResultModal;
        }

        modal.onclick = (event) => {
            if (event.target === modal) closeActionResultModal();
        };
        modal._actionResultOnClose = typeof config.onClose === 'function' ? config.onClose : null;

        modal.classList.add('active');
        document.body.classList.add('action-result-modal-open');
        okButton.focus();

        if (type === 'success' && config.autoClose !== false) {
            autoCloseTimer = window.setTimeout(closeActionResultModal, 2000);
        }

    }

    function showAccessDeniedModal(message) {
        showActionResultModal({
            type: 'error',
            title: 'Access Restricted',
            message: message || 'You cannot access this page.',
            okText: 'OK',
            autoClose: false
        });
    }

    function setProcessingControls(modal) {
        modal.dataset.processing = 'true';
        modal.querySelector('[data-action-result-close]').style.display = 'none';
        modal.querySelector('[data-action-result-cancel]').style.display = 'none';
        modal.querySelector('[data-action-result-ok]').style.display = 'none';
        modal.onclick = null;
        modal.classList.add('active');
        document.body.classList.add('action-result-modal-open');
    }

    function showProcessingModal(message = 'Processing, please wait...') {
        const modal = ensureModal();
        if (!modal) return;
        window.clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
        const icon = modal.querySelector('[data-action-result-icon]');
        icon.className = 'action-result-modal__icon processing';
        icon.innerHTML = '<span class=action-result-modal__spinner aria-hidden=true></span>';
        modal.querySelector('#actionResultModalTitle').textContent = 'Processing...';
        modal.querySelector('#actionResultModalMessage').textContent = message || 'Please wait while we complete your request.';
        setProcessingControls(modal);
    }

    function completeProcessingModal(message = 'Completed successfully.') {
        activeProcesses = 0;
        showActionResultModal({
            type: 'success',
            title: 'Success',
            message,
            autoClose: false
        });
    }

    function showErrorModal(message = 'The request could not be completed. Please try again.', title = 'Something went wrong') {
        activeProcesses = 0;
        showActionResultModal({ type: 'error', title, message, autoClose: false, okText: 'Close' });
    }

    function lockActionControl(control) {
        if (!(control instanceof HTMLElement)) return;
        const count = lockedControls.get(control) || 0;
        if (count === 0) {
            control.dataset.actionResultWasDisabled = control.disabled ? '1' : '0';
            if ('disabled' in control) control.disabled = true;
            control.setAttribute('aria-busy', 'true');
        }
        lockedControls.set(control, count + 1);
    }

    function unlockActionControl(control) {
        if (!(control instanceof HTMLElement) || !lockedControls.has(control)) return;
        const remaining = (lockedControls.get(control) || 1) - 1;
        if (remaining > 0) {
            lockedControls.set(control, remaining);
            return;
        }
        lockedControls.delete(control);
        if ('disabled' in control && control.dataset.actionResultWasDisabled !== '1') control.disabled = false;
        delete control.dataset.actionResultWasDisabled;
        control.removeAttribute('aria-busy');
    }

    async function runWithProcessingModal(operation, options = {}) {
        const control = options.button instanceof HTMLElement ? options.button : lastActionControl;
        lockActionControl(control);
        showProcessingModal(options.loadingMessage || 'Please wait while we complete your request.');
        try {
            const result = await operation();
            if (result && typeof result === 'object' && result.success === false) {
                throw new Error(result.message || 'The request could not be completed.');
            }
            completeProcessingModal(options.successMessage || result?.message || 'The request was completed successfully.');
            return result;
        } catch (error) {
            showErrorModal(error?.message || 'The request could not be completed. Please try again.', options.errorTitle);
            throw error;
        } finally {
            unlockActionControl(control);
        }
    }

    window.closeActionResultModal = closeActionResultModal;
    window.showActionResultModal = showActionResultModal;
    window.showAccessDeniedModal = showAccessDeniedModal;
    window.showProcessingModal = showProcessingModal;
    window.completeProcessingModal = completeProcessingModal;
    window.showLoadingModal = showProcessingModal;
    window.showSuccessModal = completeProcessingModal;
    window.showErrorModal = showErrorModal;
    window.runWithProcessingModal = runWithProcessingModal;
    window.showCrudResultModal = function (success, message, actionLabel, onClose) {
        showActionResultModal({
            type: success ? 'success' : 'error',
            title: `${actionLabel || 'Action'} ${success ? 'Successful' : 'Unsuccessful'}`,
            message,
            onClose,
            autoClose: false,
            okText: success ? 'OK' : 'Close'
        });
    };

    window.showActionNoticeModal = function (message, type = 'error', title = '') {
        const normalizedType = normalizeType(type);
        showActionResultModal({
            type: normalizedType,
            title: title || (normalizedType === 'success' ? 'Action Successful' : 'Action Unsuccessful'),
            message: String(message || '')
        });
    };

    window.showConfirmModal = function (message, options = {}) {
        const config = normalizeOptions(
            typeof message === 'object' && message !== null ? message : { ...options, message: String(message ?? '') },
            options.type || options.status || 'error',
            options.title || 'Confirm Action'
        );

        const modal = ensureModal();
        // Reset stale visuals/handlers before opening (UI consistency)
        modal?.classList?.remove('active');
        if (!modal) {
            return Promise.resolve(false);
        }

        const type = normalizeType(config.type || config.status);
        const icon = modal.querySelector('[data-action-result-icon]');
        const okButton = modal.querySelector('[data-action-result-ok]');
        const cancelButton = modal.querySelector('[data-action-result-cancel]');
        const closeBtn = modal.querySelector('[data-action-result-close]');
        const iconClass = type === 'success' ? 'fa-check' : 'fa-exclamation';

        okButton.style.display = '';
        if (closeBtn) closeBtn.style.display = '';
        icon.className = `action-result-modal__icon ${type}`;
        icon.innerHTML = `<i class="fas ${iconClass}"></i>`;
        modal.querySelector('#actionResultModalTitle').textContent = config.title || 'Confirm Action';

        // Provide a brief description if caller didn't include one.
        modal.querySelector('#actionResultModalMessage').textContent = config.message || 'Are you sure you want to continue?';

        okButton.textContent = config.confirmText || config.okText || 'Confirm';
        cancelButton.textContent = config.cancelText || 'Cancel';
        cancelButton.style.display = '';

        // Destructive confirm: if type is error/danger and confirmText matches common destructive cases.
        okButton.classList.remove('destructive');
        const destructive = type === 'error' && (config.confirmText === 'Delete' || config.confirmText === 'Deactivate' || config.confirmText === 'Reset' || config.confirmText === 'Terminate');
        if (destructive) okButton.classList.add('destructive');

        return new Promise((resolve) => {
            let isResolved = false;
            const cleanup = (value) => {
                if (isResolved) return;
                isResolved = true;

                // Remove handlers to avoid double-resolve
                okButton.onclick = null;
                cancelButton.onclick = null;
                modal.onclick = null;
                if (closeBtn) {
                    closeBtn.onclick = null;
                }
                document.removeEventListener('keydown', onEscape);

                modal._actionResultOnClose = null;
                closeActionResultModal();
                resolve(value);
            };

            if (closeBtn) {
                closeBtn.onclick = () => cleanup(false);
            }



            const onConfirm = (event) => {
                event.preventDefault();
                cleanup(true);
            };
            const onCancel = (event) => {
                event.preventDefault();
                cleanup(false);
            };
            const onBackdrop = (event) => {
                if (event.target === modal) {
                    cleanup(false);
                }
            };
            const onEscape = (event) => {
                if (event.key === 'Escape') {
                    cleanup(false);
                }
            };

            // Ensure we don't keep stale close handlers from previous modals
            modal._actionResultOnClose = null;
            okButton.onclick = onConfirm;
            cancelButton.onclick = onCancel;
            modal.onclick = onBackdrop;
            document.addEventListener('keydown', onEscape);

            modal.classList.add('active');
            document.body.classList.add('action-result-modal-open');
            okButton.focus();
        });
    };


    window.showSayModal = function (message, options = {}) {
        const config = normalizeOptions(
            typeof message === 'object' && message !== null ? message : { ...options, message: String(message ?? '') },
            options.type || options.status || 'error',
            options.title || ''
        );
        showActionResultModal(config);
    };

    function inferTypeFromMessage(message) {
        const text = String(message || '').toLowerCase();
        return /\b(success|successful|completed|saved|updated|added|deleted|removed|restored|approved|submitted|processed|recorded)\b/.test(text)
            && !/\b(failed|error|unable|could not|invalid|required|not found|unsuccessful)\b/.test(text)
            ? 'success'
            : 'error';
    }

    window.alert = function (message) {
        const type = inferTypeFromMessage(message);
        showActionResultModal({
            type,
            title: type === 'success' ? 'Action Successful' : 'Action Unsuccessful',
            message: String(message || '')
        });
    };

    window.say = function (message, options = {}) {
        window.showSayModal(message, options);
    };

    function isBackgroundRequest(input, init) {
        if (init.background === true || init.showProcessing === false) return true;
        const url = typeof input === 'string' ? input : (input?.url || '');
        return /(?:session_activity|mark_admin_notification_read|admin_notifications)/i.test(url);
    }

    if (nativeFetch) {
        window.fetch = async function (input, init = {}) {
            const method = String(init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            // Server mutations triggered from the UI all use the shared lifecycle.
            // Background heartbeats and explicit opt-outs remain silent.
            const tracked = !['GET', 'HEAD', 'OPTIONS'].includes(method) && !isBackgroundRequest(input, init);
            if (!tracked) {
                const response = await nativeFetch(input, init);
                if (response.status === 403) {
                    response.clone().json()
                        .then((data) => showAccessDeniedModal(data?.message || 'You cannot perform this action.'))
                        .catch(() => showAccessDeniedModal('You cannot perform this action.'));
                }
                return response;
            }
            activeProcesses += 1;
            const control = lastActionControl;
            lockActionControl(control);
            showProcessingModal('Please wait while we complete your request.');
            try {
                const response = await nativeFetch(input, init);
                activeProcesses = Math.max(0, activeProcesses - 1);
                let body = null;
                try {
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) body = await response.clone().json();
                } catch (_) {
                    body = null;
                }
                if (!response.ok || body?.success === false) {
                    if (response.status === 403) {
                        showAccessDeniedModal(body?.message || 'You cannot perform this action.');
                    } else if (activeProcesses === 0) {
                        showErrorModal(body?.message || `Request failed: ${response.status}`);
                    }
                } else if (activeProcesses === 0) {
                    completeProcessingModal(body?.message || 'The request was completed successfully.');
                }
                return response;
            } catch (error) {
                activeProcesses = Math.max(0, activeProcesses - 1);
                if (activeProcesses === 0) showErrorModal('Unable to reach the server. Please try again.');
                throw error;
            } finally {
                unlockActionControl(control);
            }
        };
    }

    document.addEventListener('click', (event) => {
        const control = event.target instanceof Element
            ? event.target.closest('button, input[type="submit"], input[type="button"]')
            : null;
        if (control && !control.closest(`#${MODAL_ID}`)) lastActionControl = control;
    }, true);

    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search || '');
        if (!params.has('access_denied')) return;

        params.delete('access_denied');
        const cleanQuery = params.toString();
        const cleanUrl = `${window.location.pathname}${cleanQuery ? `?${cleanQuery}` : ''}${window.location.hash || ''}`;
        window.history.replaceState({}, document.title, cleanUrl);
        showAccessDeniedModal('You cannot access this page.');
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noProcessing !== undefined) return;
        if (!form.checkValidity()) return;
        window.setTimeout(() => {
            if (!event.defaultPrevented) showProcessingModal();
        }, 0);
    });
})();

// All pages using the shared modal UI also retain cancelled input drafts.
(() => {
    const source = document.currentScript?.src;
    if (!source || document.querySelector('script[data-modal-drafts]')) return;
    const script = document.createElement('script');
    script.dataset.modalDrafts = 'true';
    script.src = new URL('modal_drafts.js?v=20260912-1', source).href;
    document.head.appendChild(script);
})();

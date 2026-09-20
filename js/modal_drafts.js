// Keep cancelled modal entries in memory for the lifetime of this page.
(() => {
    if (window.modalDraftsInstalled) return;
    window.modalDraftsInstalled = true;
    const selector = '[id].modal, [id][class*="modal-overlay"], [id$="Modal"][class*="overlay"], [id][role="dialog"], [id].modal-backdrop';
    const states = new Map();
    const drafts = new Map();
    const fields = modal => Array.from(modal.querySelectorAll('input, select, textarea'));
    const visible = modal => !modal.hidden && modal.getAttribute('aria-hidden') !== 'true'
        && getComputedStyle(modal).display !== 'none' && getComputedStyle(modal).visibility !== 'hidden';
    const keyFor = modal => modal.id + ':' + (/^(add|new|create)/i.test(modal.id) ? '' : fields(modal)
        .filter(field => field.type === 'hidden' && /(?:id|_id)$/i.test(field.name || field.id))
        .map(field => `${field.name || field.id}=${field.value}`).join('&'));
    const fieldKey = (field, index) => `${field.id || field.name || index}:${field.type}:${field.type === 'checkbox' || field.type === 'radio' ? field.value : ''}`;

    function snapshot(modal) {
        return fields(modal).map((field, index) => ({
            key: fieldKey(field, index), value: field.value, checked: field.checked,
            selected: field.multiple ? Array.from(field.selectedOptions, option => option.value) : null,
            files: field.type === 'file' ? field.files : null
        }));
    }

    function restore(modal, saved) {
        const entries = new Map(saved.map(entry => [entry.key, entry]));
        const changed = [];
        fields(modal).forEach((field, index) => {
            const entry = entries.get(fieldKey(field, index));
            // Record identifiers and server tokens belong to the freshly opened form.
            const serverField = field.type === 'hidden' && /(?:id|_id|token|csrf)$/i.test(field.name || field.id);
            if (!entry || serverField || field.readOnly || field.disabled) return;
            if (field.type === 'file') {
                try { if (entry.files) field.files = entry.files; } catch { /* Browser may reject restoring files. */ }
            } else if (entry.selected) {
                Array.from(field.options).forEach(option => { option.selected = entry.selected.includes(option.value); });
            } else {
                field.value = entry.value;
                if (field.type === 'checkbox' || field.type === 'radio') field.checked = entry.checked;
            }
            changed.push(field);
        });
        // Recompute existing validation and previews after all fields are restored.
        changed.forEach(field => {
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function scan() {
        document.querySelectorAll(selector).forEach(modal => {
            if (!fields(modal).length) return;
            const open = visible(modal);
            let state = states.get(modal);
            if (!state) {
                state = { open: false, key: '', cancelled: false };
                states.set(modal, state);
            }
            if (open && !state.open) {
                state.open = true;
                state.cancelled = false;
                state.key = keyFor(modal);
                const saved = drafts.get(state.key);
                if (saved) restore(modal, saved);
            } else if (!open && state.open) {
                // A normal save closes the modal without a cancellation event.
                if (!state.cancelled) drafts.delete(state.key);
                state.open = false;
            }
        });
    }

    function keepDraft(modal) {
        const state = states.get(modal);
        if (!state || !state.open) return;
        state.key = keyFor(modal);
        drafts.set(state.key, snapshot(modal));
        state.cancelled = true;
    }

    document.addEventListener('click', event => {
        const target = event.target instanceof Element ? event.target : null;
        const modal = target?.closest(selector);
        if (!modal) return;
        const button = target.closest('button, a, [role="button"], .close, .modal-close');
        const label = button ? `${button.id} ${button.className} ${button.getAttribute('aria-label') || ''} ${button.textContent}` : '';
        if (target === modal || /cancel|close|dismiss|[×✕✖]/i.test(label)) keepDraft(modal);
        else if (button) {
            const state = states.get(modal);
            if (state) state.cancelled = false;
        }
    }, true);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') states.forEach((state, modal) => {
            if (state.open) keepDraft(modal);
        });
    }, true);

    function start() {
        scan();
        new MutationObserver(scan).observe(document.body, {
            subtree: true, childList: true, attributes: true,
            attributeFilter: ['class', 'style', 'hidden', 'aria-hidden']
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();

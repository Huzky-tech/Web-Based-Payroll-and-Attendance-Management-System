(function () {
    'use strict';
    let catalogPositions = [];

    const escapeHtml = (value) => {
        const element = document.createElement('div');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    };

    async function fetchJson(url, options = {}) {
        const separator = url.includes('?') ? '&' : '?';
        const response = await fetch(`${url}${separator}_t=${Date.now()}`, {
            credentials: 'same-origin',
            // Save and remove explicitly use the shared lifecycle below so the
            // resulting message remains specific to the position action.
            showProcessing: false,
            ...options
        });
        const data = await response.json().catch(() => ({ success: false, message: 'Invalid server response.' }));
        if (!response.ok && data.success !== false) {
            data.success = false;
        }
        if (data.errors && url.includes('save_position_catalog.php')) {
            Object.entries(data.errors).forEach(([field, message]) => {
                const input = document.getElementById(`catalog_${field}`);
                if (input) validateField(input, document.getElementById(`${input.id}_error`), message);
            });
        }
        return data;
    }

    function showResult(success, message) {
        if (typeof window.showCrudResultModal === 'function') {
            window.showCrudResultModal(success, message, 'Position');
        } else {
            window.alert(message || (success ? 'Position saved.' : 'Unable to complete the action.'));
        }
    }

    function resetForm() {
        const form = document.getElementById('positionCatalogForm');
        form?.reset();
        document.getElementById('catalog_position_id').value = '';
        document.getElementById('positionCatalogSubmit').innerHTML = '<i class="fas fa-plus"></i>Add Position';
        document.querySelectorAll('.position-field-error').forEach((element) => { element.textContent = ''; });
        document.querySelectorAll('.position-form-field input').forEach((input) => { input.classList.remove('field-invalid'); input.removeAttribute('aria-invalid'); });
    }

    function validateField(input, errorElement, message) {
        const invalid = Boolean(message);
        input.classList.toggle('field-invalid', invalid);
        input.setAttribute('aria-invalid', String(invalid));
        input.setAttribute('aria-describedby', errorElement.id);
        errorElement.textContent = message || '';
        return !invalid;
    }

    function validateInput(input) {
        let message = '';
        const value = input.value;
        if (input.id === 'catalog_position_name') {
            const name = value.trim().replace(/\s+/g, ' ');
            const id = Number(document.getElementById('catalog_position_id').value || 0);
            if (!name) message = 'Position name is required.';
            else if (!/^[\p{L} ]{1,100}$/u.test(name)) message = 'Use 1–100 letters and spaces only.';
            else if (catalogPositions.some((position) => Number(position.id) !== id && position.position_name.trim().replace(/\s+/g, ' ').toLowerCase() === name.toLowerCase())) {
                message = 'A position with that name already exists.';
            }
        } else {
            if (input.validity.badInput || !/^\d+(?:\.\d{1,2})?$/.test(value) || Number(value) < 0.01 || Number(value) > 99999999.99) {
                message = 'Enter PHP 0.01–99,999,999.99, with up to two decimal places.';
            }
        }
        return validateField(input, document.getElementById(`${input.id}_error`), message);
    }

    function validateForm() {
        const inputs = Array.from(document.querySelectorAll('#positionCatalogForm .position-form-field input'));
        const valid = inputs.map(validateInput).every(Boolean);
        if (!valid) inputs.find((input) => input.classList.contains('field-invalid'))?.focus();
        return valid;
    }
    async function loadPositions() {
        const body = document.getElementById('positionCatalogTableBody');
        if (!body) return;
        try {
            const data = await fetchJson('../api/get_position_catalog.php');
            if (!data.success) throw new Error(data.message || 'Unable to load positions.');
            const positions = Array.isArray(data.positions) ? data.positions : [];
            catalogPositions = positions;
            body.innerHTML = positions.length ? positions.map((position) => `
                <tr>
                    <td>${escapeHtml(position.position_name)}</td>
                    <td>PHP ${Number(position.hourly_rate).toFixed(2)}</td>
                    <td>${position.weekly_rate == null ? 'Not set' : `PHP ${Number(position.weekly_rate).toFixed(2)}`}</td>
                    <td>PHP ${Number(position.salary_rate).toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn-secondary position-catalog-edit"
                            data-position-id="${Number(position.id)}"
                            data-position-name="${escapeHtml(position.position_name)}"
                            data-hourly-rate="${Number(position.hourly_rate)}"
                            data-weekly-rate="${position.weekly_rate == null ? '' : Number(position.weekly_rate)}"
                            data-salary-rate="${Number(position.salary_rate)}"><i class="fas fa-pen"></i>Edit</button>
                        <button type="button" class="btn-danger position-catalog-delete" data-position-id="${Number(position.id)}"><i class="fas fa-trash"></i>Remove</button>
                    </td>
                </tr>`).join('') : '<tr><td colspan="5">No positions added yet.</td></tr>';
        } catch (error) {
            body.innerHTML = `<tr><td colspan="5">${escapeHtml(error.message || 'Unable to load positions. Please reload the page.')}</td></tr>`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('positionCatalogForm');
        const tableBody = document.getElementById('positionCatalogTableBody');
        const modal = document.getElementById('positionCatalogModal');
        const modalTitle = document.getElementById('positionCatalogModalTitle');
        const nameInput = document.getElementById('catalog_position_name');
        const hourlyInput = document.getElementById('catalog_hourly_rate');
        const salaryInput = document.getElementById('catalog_salary_rate');
        if (!form || !tableBody) return;

        const openModal = (editing = false) => {
            modalTitle.textContent = editing ? 'Edit Position' : 'Add New Position';
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(() => nameInput.focus(), 50);
        };
        const closeModal = () => {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            resetForm();
        };

        document.getElementById('openPositionCatalogModal')?.addEventListener('click', () => {
            resetForm();
            openModal(false);
        });
        document.getElementById('closePositionCatalogModal')?.addEventListener('click', closeModal);
        document.getElementById('positionCatalogCancel')?.addEventListener('click', closeModal);
        modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal?.classList.contains('active')) closeModal();
        });

        form.querySelectorAll('.position-form-field input').forEach((input) => {
            input.addEventListener('input', () => validateInput(input));
            input.addEventListener('blur', () => validateInput(input));
        });
        loadPositions();
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateForm()) return;
            const submitButton = document.getElementById('positionCatalogSubmit');
            submitButton.disabled = true;
            try {
                const result = await window.runWithProcessingModal(
                    () => fetchJson('../api/save_position_catalog.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: Number(document.getElementById('catalog_position_id').value || 0),
                            position_name: document.getElementById('catalog_position_name').value.trim(),
                            hourly_rate: document.getElementById('catalog_hourly_rate').value,
                            weekly_rate: document.getElementById('catalog_weekly_rate').value,
                            salary_rate: document.getElementById('catalog_salary_rate').value
                        })
                    }),
                    {
                        button: submitButton,
                        loadingMessage: 'Please wait while we save the position.'
                    }
                );
                if (result.success) {
                    closeModal();
                    await loadPositions();
                }
            } catch (error) {
                // The shared processing modal already shows the actual error.
            } finally {
                submitButton.disabled = false;
            }
        });

        tableBody.addEventListener('click', async (event) => {
            const editButton = event.target.closest('.position-catalog-edit');
            if (editButton) {
                document.getElementById('catalog_position_id').value = editButton.dataset.positionId;
                document.getElementById('catalog_position_name').value = editButton.dataset.positionName;
                document.getElementById('catalog_hourly_rate').value = editButton.dataset.hourlyRate;
                document.getElementById('catalog_weekly_rate').value = editButton.dataset.weeklyRate || '';
                document.getElementById('catalog_salary_rate').value = editButton.dataset.salaryRate;
                document.getElementById('positionCatalogSubmit').innerHTML = '<i class="fas fa-save"></i>Update Position';
                openModal(true);
                return;
            }

            const deleteButton = event.target.closest('.position-catalog-delete');
            if (!deleteButton) return;
            const confirmed = await window.showConfirmModal?.('Remove this position from the employee position list?', {
                title: 'Remove Position',
                confirmText: 'Remove',
                cancelText: 'Cancel',
                type: 'error'
            });
            if (!confirmed) return;

            try {
                const result = await window.runWithProcessingModal(
                    () => fetchJson('../api/delete_position_catalog.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: Number(deleteButton.dataset.positionId) })
                    }),
                    {
                        button: deleteButton,
                        loadingMessage: 'Please wait while we remove the position.'
                    }
                );
                if (result.success) await loadPositions();
            } catch (error) {
                // The shared processing modal already shows the actual error.
            }
        });
    });
})();

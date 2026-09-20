(function () {
    'use strict';

    const escapeHtml = (value) => {
        const element = document.createElement('div');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    };

    async function fetchJson(url, options = {}) {
        const separator = url.includes('?') ? '&' : '?';
        const response = await fetch(`${url}${separator}_t=${Date.now()}`, {
            credentials: 'same-origin',
            ...options
        });
        const data = await response.json().catch(() => ({ success: false, message: 'Invalid server response.' }));
        if (!response.ok && data.success !== false) {
            data.success = false;
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
        document.querySelectorAll('.position-form-field input').forEach((input) => input.classList.remove('field-invalid'));
    }

    function validateField(input, errorElement, message) {
        const invalid = Boolean(message);
        input.classList.toggle('field-invalid', invalid);
        errorElement.textContent = message || '';
        return !invalid;
    }

    function validateForm() {
        const nameInput = document.getElementById('catalog_position_name');
        const hourlyInput = document.getElementById('catalog_hourly_rate');
        const salaryInput = document.getElementById('catalog_salary_rate');
        const name = nameInput.value.trim().replace(/\s+/g, ' ');
        const validName = validateField(
            nameInput,
            document.getElementById('catalog_position_name_error'),
            !name ? 'Position name is required.' : (!/^[\p{L} ]+$/u.test(name) ? 'Letters and spaces only.' : '')
        );
        const validHourly = validateField(
            hourlyInput,
            document.getElementById('catalog_hourly_rate_error'),
            Number(hourlyInput.value) <= 0 ? 'Enter an hourly rate greater than zero.' : ''
        );
        const validSalary = validateField(
            salaryInput,
            document.getElementById('catalog_salary_rate_error'),
            Number(salaryInput.value) <= 0 ? 'Enter a monthly salary greater than zero.' : ''
        );
        nameInput.value = name;
        return validName && validHourly && validSalary;
    }

    async function loadPositions() {
        const body = document.getElementById('positionCatalogTableBody');
        if (!body) return;
        try {
            const data = await fetchJson('../api/get_position_catalog.php');
            if (!data.success) throw new Error(data.message || 'Unable to load positions.');
            const positions = Array.isArray(data.positions) ? data.positions : [];
            body.innerHTML = positions.length ? positions.map((position) => `
                <tr>
                    <td>${escapeHtml(position.position_name)}</td>
                    <td>PHP ${Number(position.hourly_rate).toFixed(2)}</td>
                    <td>PHP ${Number(position.salary_rate).toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn-secondary position-catalog-edit"
                            data-position-id="${Number(position.id)}"
                            data-position-name="${escapeHtml(position.position_name)}"
                            data-hourly-rate="${Number(position.hourly_rate)}"
                            data-salary-rate="${Number(position.salary_rate)}"><i class="fas fa-pen"></i>Edit</button>
                        <button type="button" class="btn-danger position-catalog-delete" data-position-id="${Number(position.id)}"><i class="fas fa-trash"></i>Remove</button>
                    </td>
                </tr>`).join('') : '<tr><td colspan="4">No positions added yet.</td></tr>';
        } catch (error) {
            body.innerHTML = `<tr><td colspan="4">${escapeHtml(error.message || 'Unable to load positions. Please reload the page.')}</td></tr>`;
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

        nameInput.addEventListener('input', () => {
            const filtered = nameInput.value.replace(/[^\p{L} ]/gu, '');
            const removedCharacters = filtered !== nameInput.value;
            nameInput.value = filtered;
            validateField(nameInput, document.getElementById('catalog_position_name_error'), removedCharacters ? 'Special characters and numbers are not allowed.' : '');
        });
        [hourlyInput, salaryInput].forEach((input) => input.addEventListener('input', () => {
            if (Number(input.value) < 0) input.value = '';
            const error = document.getElementById(`${input.id}_error`);
            validateField(input, error, input.value !== '' && Number(input.value) <= 0 ? 'Enter an amount greater than zero.' : '');
        }));

        loadPositions();
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateForm()) return;
            const submitButton = document.getElementById('positionCatalogSubmit');
            submitButton.disabled = true;
            try {
                const result = await fetchJson('../api/save_position_catalog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: Number(document.getElementById('catalog_position_id').value || 0),
                        position_name: document.getElementById('catalog_position_name').value.trim(),
                        hourly_rate: document.getElementById('catalog_hourly_rate').value,
                        salary_rate: document.getElementById('catalog_salary_rate').value
                    })
                });
                showResult(Boolean(result.success), result.message);
                if (result.success) {
                    closeModal();
                    await loadPositions();
                }
            } catch (error) {
                showResult(false, error.message || 'Unable to save the position.');
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
                document.getElementById('catalog_salary_rate').value = editButton.dataset.salaryRate;
                document.getElementById('positionCatalogSubmit').innerHTML = '<i class="fas fa-save"></i>Update Position';
                openModal(true);
                return;
            }

            const deleteButton = event.target.closest('.position-catalog-delete');
            if (!deleteButton || !window.confirm('Remove this position from the employee position list?')) return;
            const result = await fetchJson('../api/delete_position_catalog.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: Number(deleteButton.dataset.positionId) })
            });
            showResult(Boolean(result.success), result.message);
            if (result.success) await loadPositions();
        });
    });
})();

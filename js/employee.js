function updateDateTime() {
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) {
        return;
    }

    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
    timeEl.textContent = `${hours}:${minutes} ${ampm}`;
}

function normalizeRoleLabel(role) {
    if (role === 'Assistant Admin') {
        return 'Assistant Admin';
    }

    return role;
}

function isPayrollStaffSettingsView() {
    return window.isPayrollStaffSettings === true;
}

function showSettingsActionResult(success, message, actionLabel = 'Settings Action') {
    if (typeof window.showCrudResultModal === 'function') {
        window.showCrudResultModal(success, message, actionLabel);
        return;
    }

    window.alert(message || `${actionLabel} ${success ? 'successful' : 'unsuccessful'}.`);
}

function switchTab(tab) {
    document.querySelectorAll('.pill-tab').forEach((button) => button.classList.remove('active'));
    document.querySelector(`[data-tab="${tab}"]`)?.classList.add('active');

    document.querySelectorAll('.panel').forEach((panel) => {
        if (panel.id) {
            panel.style.display = panel.id === tab ? 'block' : 'none';
        }
    });

    const saveLabel = document.getElementById('saveTabBtnLabel');
    if (saveLabel) {
        const labels = {
            company: 'Save Company',
            payroll: 'Save Payroll',
            notifications: 'Save Notifications',
            security: 'Save Security',
            system: 'Save System',
            users: 'User Actions Save Changes'
        };
        saveLabel.textContent = labels[tab] || 'Save Settings';
    }
}

function handleOvertimeToggle() {
    const overtimeToggle = document.getElementById('allow_overtime');
    const overtimeRateInput = document.getElementById('overtime_rate');
    if (!overtimeToggle || !overtimeRateInput) {
        return;
    }

    overtimeRateInput.disabled = !overtimeToggle.checked;
    overtimeRateInput.style.opacity = overtimeToggle.checked ? '1' : '0.5';
}

function validateCompanyInfo() {
    const companyName = document.getElementById('company_name')?.value.trim() || '';
    const taxId = document.getElementById('tax_id')?.value.trim() || '';
    const phone = document.getElementById('phone')?.value.trim() || '';
    const email = document.getElementById('email')?.value.trim() || '';

    const errors = [];
    if (!companyName) {
        errors.push('Company Name is required');
    }
    if (!taxId) {
        errors.push('Tax ID Number is required');
    }
    if (phone && !/^[\d\s\-()+]+$/.test(phone)) {
        errors.push('Phone Number format is invalid');
    }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Email Address format is invalid');
    }

    return errors;
}

function validatePayrollSettings() {
    const payPeriods = document.getElementById('pay_periods')?.value || '';
    const philhealthRate = (document.getElementById('philhealth_rate')?.value || '').replace('%', '');
    const pagibigRate = (document.getElementById('pagibig_rate')?.value || '').replace('%', '');
    const overtimeRate = document.getElementById('overtime_rate')?.value || '';
    const lateWorkerDeduction = document.getElementById('late_worker_deduction')?.value || '0';

    const errors = [];
    if (!payPeriods) {
        errors.push('Pay Periods is required');
    }

    const philhealth = parseFloat(philhealthRate);
    if (Number.isNaN(philhealth) || philhealth < 0 || philhealth > 100) {
        errors.push('PhilHealth Rate must be a number between 0 and 100');
    }

    const pagibig = parseFloat(pagibigRate);
    if (Number.isNaN(pagibig) || pagibig < 0 || pagibig > 100) {
        errors.push('Pag-IBIG Rate must be a number between 0 and 100');
    }

    if (document.getElementById('allow_overtime')?.checked) {
        const otRate = parseFloat(overtimeRate);
        if (Number.isNaN(otRate) || otRate <= 0) {
            errors.push('Overtime Rate must be greater than 0');
        }
    }

    const lateDeduction = parseFloat(lateWorkerDeduction);
    if (Number.isNaN(lateDeduction) || lateDeduction < 0) {
        errors.push('Late Worker Deduction must be 0 or greater');
    }

    return errors;
}

function normalizePayPeriod(value) {
    const normalized = String(value || '').trim().toLowerCase();

    if (normalized === 'weekly' || normalized === 'week') {
        return 'Weekly';
    }

    if (normalized === 'monthly' || normalized === 'month') {
        return 'Monthly';
    }

    if (normalized.includes('semi') || normalized.includes('1-15') || normalized.includes('16-end')) {
        return 'Semi-monthly (1-15, 16-end)';
    }

    return 'Semi-monthly (1-15, 16-end)';
}

function validateNotificationSettings() {
    const emailNotifications = document.getElementById('email_notifications')?.checked;
    const inSystemNotifications = document.getElementById('in_system_notifications')?.checked;
    const frequency = document.getElementById('email_digest_frequency')?.value || '';

    const errors = [];
    if (!emailNotifications && !inSystemNotifications) {
        errors.push('Please enable at least one notification channel (Email or In-System)');
    }

    if (emailNotifications && !['Instant', 'Daily', 'Weekly'].includes(frequency)) {
        errors.push('Please select a valid Email Digest Frequency');
    }

    return errors;
}

function handleLogoPreview(input) {
    if (!input.files || !input.files[0]) {
        return;
    }

    const file = input.files[0];
    const allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please select a valid image file (JPG, PNG, or SVG)');
        input.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('File size too large. Maximum size is 2MB');
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        const previewContainer = document.getElementById('logo_preview');
        if (previewContainer) {
            previewContainer.innerHTML = `<img src="${event.target.result}" style="width:70px; height:70px; border-radius:10px; object-fit:cover;">`;
        }
    };
    reader.readAsDataURL(file);
}

async function fetchJson(url, options = {}) {
    // Add cache-busting parameter to prevent stale responses
    const separator = url.includes('?') ? '&' : '?';
    const cacheUrl = url + separator + '_t=' + Date.now();
    
    const response = await fetch(cacheUrl, options);
    const contentType = response.headers.get('content-type') || '';
    
    if (!contentType.includes('application/json')) {
        // Try to parse as JSON anyway, but log if it fails
        const text = await response.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error('Non-JSON response from', url, ':', text.substring(0, 200));
            throw new Error('Server returned non-JSON response. Check server logs.');
        }
        return json;
    }
    return response.json();
}

async function loadCompanySettings() {
    const data = await fetchJson('../api/get_company_settings.php');
    if (!data.success || !data.data) {
        return;
    }

    document.getElementById('company_name').value = data.data.company_name || '';
    document.getElementById('tax_id').value = data.data.tax_id || '';
    document.getElementById('phone').value = data.data.phone || '';
    document.getElementById('email').value = data.data.email || '';
    document.getElementById('address').value = data.data.address || '';

    if (data.data.logo_path) {
        const previewContainer = document.getElementById('logo_preview');
        if (previewContainer) {
            const logo = document.createElement("img");
            logo.src = new URL("../" + data.data.logo_path, window.location.href).href;
            logo.alt = "Company logo";
            logo.style.cssText = "width:70px;height:70px;border-radius:10px;object-fit:cover";
            previewContainer.replaceChildren(logo);
        }
    }
}

async function loadPayrollSettings() {
    const data = await fetchJson('../api/get_payroll_settings.php');
    if (!data.success || !data.data) {
        return;
    }

    document.getElementById('pay_periods').value = normalizePayPeriod(data.data.pay_periods);
    document.getElementById('sss_rate').value = data.data.sss_rate || 'Standard';
    document.getElementById('philhealth_rate').value = String(data.data.philhealth_rate || '3');
    document.getElementById('pagibig_rate').value = String(data.data.pagibig_rate || '2');
    document.getElementById('tax_table').value = data.data.tax_table || 'Latest BIR Tax Table';
    document.getElementById('allow_overtime').checked = Number(data.data.allow_overtime) === 1;
    document.getElementById('overtime_rate').value = data.data.overtime_rate || '1.25';
    document.getElementById('late_worker_deduction').value = Number(data.data.late_worker_deduction || 0).toFixed(2);

    handleOvertimeToggle();
}


async function loadNotificationSettings() {
    const data = await fetchJson('../api/get_notification_settings.php');
    if (!data.success || !data.data) {
        return;
    }

    document.getElementById('email_notifications').checked = Number(data.data.email_notifications) === 1;
    document.getElementById('in_system_notifications').checked = Number(data.data.in_system_notifications) === 1;
    document.getElementById('leave_request_updates').checked = Number(data.data.leave_request_updates) === 1;
    document.getElementById('payroll_processing').checked = Number(data.data.payroll_processing) === 1;
    document.getElementById('attendance_issues').checked = Number(data.data.attendance_issues) === 1;
    document.getElementById('system_updates').checked = Number(data.data.system_updates) === 1;
    document.getElementById('daily_reports').checked = Number(data.data.daily_reports) === 1;
    document.getElementById('email_digest_frequency').value = data.data.email_digest_frequency || 'Daily';
}

async function loadSecuritySettings() {
    const data = await fetchJson('../api/get_security_settings.php');
    if (!data.success || !data.data) {
        return;
    }

    document.getElementById('password_expiry_days').value = data.data.password_expiry_days || 90;
    document.getElementById('min_password_length').value = data.data.min_password_length || 8;
    document.getElementById('require_special_char').checked = Number(data.data.require_special_char) === 1;
    document.getElementById('require_number').checked = Number(data.data.require_number) === 1;
    document.getElementById('require_uppercase').checked = Number(data.data.require_uppercase) === 1;
    document.getElementById('max_login_attempts').value = data.data.max_login_attempts || 5;
    document.getElementById('session_timeout_minutes').value = data.data.session_timeout_minutes || 30;
}

async function loadSystemSettings() {
    const data = await fetchJson('../api/get_system_settings.php');
    if (!data.success || !data.data) {
        return;
    }

    // Some deployments/HTML variants may not include every control.
    // Avoid crashing when an element is missing.
    const maintenanceEl = document.getElementById('maintenance_mode');
    if (maintenanceEl) {
        maintenanceEl.checked = Number(data.data.maintenance_mode) === 1;
    }

    const debugEl = document.getElementById('debug_mode');
    if (debugEl) {
        debugEl.checked = Number(data.data.debug_mode) === 1;
    }

    const retentionValueEl = document.getElementById('data_retention_value');
    const retentionUnitEl = document.getElementById('data_retention_unit');
    if (retentionValueEl && retentionUnitEl) {
        // Keep the database representation in days while presenting the most
        // natural exact unit to the administrator.
        const retentionDays = Math.max(1, Number(data.data.data_retention_days) || 365);
        const units = [
            ['years', 365],
            ['months', 30],
            ['weeks', 7],
            ['days', 1]
        ];
        const selected = units.find(([, multiplier]) => retentionDays % multiplier === 0) || units[3];
        retentionUnitEl.value = selected[0];
        retentionValueEl.value = Math.max(1, retentionDays / selected[1]);
    }

    const backupEl = document.getElementById('backup_schedule');
    if (backupEl) {
        backupEl.value = data.data.backup_schedule || 'Daily';
    }

    const timezoneEl = document.getElementById('timezone');
    if (timezoneEl) {
        timezoneEl.value = data.data.timezone || 'Asia/Manila (GMT+8)';
    }

    const dateFormatEl = document.getElementById('date_format');
    if (dateFormatEl) {
        dateFormatEl.value = data.data.date_format || 'MM/DD/YYYY';
    }

    const timeFormatEl = document.getElementById('time_format');
    if (timeFormatEl) {
        timeFormatEl.value = data.data.time_format || '12-hour (AM/PM)';
    }

    const lastBackupEl = document.getElementById('last_backup_at');
    if (lastBackupEl) {
        lastBackupEl.textContent = data.data.last_backup_at
            ? new Date(String(data.data.last_backup_at).replace(' ', 'T')).toLocaleString()
            : 'No backup has been run yet';
    }

    const systemInfoFields = ['system_version', 'last_update', 'server_environment', 'database_size'];
    systemInfoFields.forEach((field) => {
        const element = document.getElementById(field);
        if (element) {
            element.textContent = data.data[field] || 'Not available';
        }
    });

    updateSampleDataDevToolsVisibility(data.data);
}


function updateSampleDataDevToolsVisibility(systemSettings = null) {
    const panel = document.getElementById('sampleDataDevTools');
    if (!panel) {
        return;
    }

    const debugModeEnabled = Number(systemSettings?.debug_mode ?? document.getElementById('debug_mode')?.checked) === 1;
    const host = window.location.hostname.toLowerCase();
    const isLocalHost = host === 'localhost' || host === '127.0.0.1';
    panel.style.display = debugModeEnabled || isLocalHost ? 'block' : 'none';
}

async function loadAllSettings() {
    try {
        if (window.currentUserRole === 'Assistant Admin') {
            await loadSystemSettings();
            return;
        }
        await Promise.all([
            loadCompanySettings(),
            loadPayrollSettings(),
            loadNotificationSettings(),
            loadSecuritySettings(),
            loadSystemSettings()
        ]);
    } catch (error) {
        console.error('Error loading settings:', error);
        alert('Some settings failed to load. Please refresh and try again.');
    }
}

async function saveCompanySettings() {
    const companyForm = new FormData();
    companyForm.append('company_name', document.getElementById('company_name').value.trim());
    companyForm.append('tax_id', document.getElementById('tax_id').value.trim());
    companyForm.append('phone', document.getElementById('phone').value.trim());
    companyForm.append('email', document.getElementById('email').value.trim());
    companyForm.append('address', document.getElementById('address').value.trim());

    return fetchJson('../api/update_company_settings.php', {
        method: 'POST',
        body: companyForm
    });
}

async function savePayrollSettings() {
    const payrollForm = new FormData();
    payrollForm.append('pay_periods', normalizePayPeriod(document.getElementById('pay_periods').value));
    payrollForm.append('sss_rate', document.getElementById('sss_rate').value);
    payrollForm.append('philhealth_rate', (document.getElementById('philhealth_rate').value || '').replace('%', '') || '3');
    payrollForm.append('pagibig_rate', (document.getElementById('pagibig_rate').value || '').replace('%', '') || '2');
    payrollForm.append('tax_table', document.getElementById('tax_table').value);
    payrollForm.append('allow_overtime', document.getElementById('allow_overtime').checked ? '1' : '0');
    payrollForm.append('overtime_rate', document.getElementById('overtime_rate').value || '1.25');
    payrollForm.append('late_worker_deduction', document.getElementById('late_worker_deduction')?.value || '0');

    return fetchJson('../api/update_payroll_settings.php', {
        method: 'POST',
        body: payrollForm
    });
}


async function saveNotificationSettings() {
    const notificationErrors = validateNotificationSettings();
    if (notificationErrors.length > 0) {
        return { success: false, message: notificationErrors.join(', ') };
    }

    const notificationForm = new FormData();
    notificationForm.append('email_notifications', document.getElementById('email_notifications').checked ? '1' : '0');
    notificationForm.append('in_system_notifications', document.getElementById('in_system_notifications').checked ? '1' : '0');
    notificationForm.append('leave_request_updates', document.getElementById('leave_request_updates').checked ? '1' : '0');
    notificationForm.append('payroll_processing', document.getElementById('payroll_processing').checked ? '1' : '0');
    notificationForm.append('attendance_issues', document.getElementById('attendance_issues').checked ? '1' : '0');
    notificationForm.append('system_updates', document.getElementById('system_updates').checked ? '1' : '0');
    notificationForm.append('daily_reports', document.getElementById('daily_reports').checked ? '1' : '0');
    notificationForm.append('email_digest_frequency', document.getElementById('email_digest_frequency').value);

    return fetchJson('../api/update_notification_settings.php', {
        method: 'POST',
        body: notificationForm
    });
}

async function saveSecuritySettings() {
    const securityForm = new FormData();
    securityForm.append('password_expiry_days', document.getElementById('password_expiry_days').value || '90');
    securityForm.append('min_password_length', document.getElementById('min_password_length').value || '8');
    securityForm.append('require_special_char', document.getElementById('require_special_char').checked ? '1' : '0');
    securityForm.append('require_number', document.getElementById('require_number').checked ? '1' : '0');
    securityForm.append('require_uppercase', document.getElementById('require_uppercase').checked ? '1' : '0');
    securityForm.append('max_login_attempts', document.getElementById('max_login_attempts').value || '5');
    securityForm.append('session_timeout_minutes', document.getElementById('session_timeout_minutes').value || '30');
    // These advanced controls are not exposed in the current Settings interface.
    securityForm.append('enable_2fa', '0');
    securityForm.append('enable_ip_restriction', '0');

    return fetchJson('../api/update_security_settings.php', {
        method: 'POST',
        body: securityForm
    });
}

async function saveSystemSettings() {
    const systemForm = new FormData();

    const maintenanceEl = document.getElementById('maintenance_mode');
    systemForm.append('maintenance_mode', maintenanceEl?.checked ? '1' : '0');

    const debugEl = document.getElementById('debug_mode');
    systemForm.append('debug_mode', debugEl?.checked ? '1' : '0');

    const retentionValueEl = document.getElementById('data_retention_value');
    const retentionUnitEl = document.getElementById('data_retention_unit');
    systemForm.append('data_retention_value', String(Math.max(1, Math.round(Number(retentionValueEl?.value) || 1))));
    systemForm.append('data_retention_unit', retentionUnitEl?.value || 'years');

    const backupEl = document.getElementById('backup_schedule');
    systemForm.append('backup_schedule', backupEl?.value || 'Daily');

    const timezoneEl = document.getElementById('timezone');
    systemForm.append('timezone', timezoneEl?.value || 'Asia/Manila (GMT+8)');

    const dateFormatEl = document.getElementById('date_format');
    systemForm.append('date_format', dateFormatEl?.value || 'MM/DD/YYYY');

    const timeFormatEl = document.getElementById('time_format');
    systemForm.append('time_format', timeFormatEl?.value || '12-hour (AM/PM)');

    return fetchJson('../api/update_system_settings.php', {
        method: 'POST',
        body: systemForm
    });
}

async function saveActiveTab() {
    if (isPayrollStaffSettingsView()) {
        return;
    }

    const activeTabButton = document.querySelector('.pill-tab.active');
    const activeTab = activeTabButton?.dataset?.tab || 'company';

    if (activeTab === 'users') {
        alert('User management changes are saved from the user action buttons.');
        return;
    }

    if (!(await window.showConfirmModal(`Save settings for ${activeTab} ?`, {
        title: 'Confirm Settings Save',
        confirmText: 'Save',
        type: 'success'
    }))) {
        return;
    }

    // Disable save button and show loading state to prevent double clicks
    const saveBtn = document.getElementById('btnSaveCompany') || document.querySelector('.btn-action');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.dataset.originalHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    // Safety timeout: auto-recover button after 20 seconds to prevent permanent lock
    const timeoutId = setTimeout(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = saveBtn.dataset.originalHtml || '<i class="fas fa-save"></i> Save Settings';
        }
    }, 20000);

    try {
        if (activeTab === 'company') {
            const errors = validateCompanyInfo();
            if (errors.length > 0) {
                clearTimeout(timeoutId);
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = saveBtn.dataset.originalHtml || '<i class="fas fa-save"></i> Save Settings';
                }
                alert('Please fix the following errors in Company tab:\n\n' + errors.join('\n'));
                switchTab('company');
                return;
            }
            const res = await saveCompanySettings();
            if (!res?.success) throw new Error(res?.message || 'Failed to save company settings');
        } else if (activeTab === 'payroll') {
            const errors = validatePayrollSettings();
            if (errors.length > 0) {
                clearTimeout(timeoutId);
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = saveBtn.dataset.originalHtml || '<i class="fas fa-save"></i> Save Settings';
                }
                alert('Please fix the following errors in Payroll tab:\n\n' + errors.join('\n'));
                switchTab('payroll');
                return;
            }
            const res = await savePayrollSettings();
            if (!res?.success) throw new Error(res?.message || 'Failed to save payroll settings');
        } else if (activeTab === 'notifications') {
            const errors = validateNotificationSettings();
            if (errors.length > 0) {
                clearTimeout(timeoutId);
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = saveBtn.dataset.originalHtml || '<i class="fas fa-save"></i> Save Settings';
                }
                alert('Please fix the following errors in Notifications tab:\n\n' + errors.join('\n'));
                switchTab('notifications');
                return;
            }
            const res = await saveNotificationSettings();
            if (!res?.success) throw new Error(res?.message || 'Failed to save notification settings');
        } else if (activeTab === 'security') {
            const res = await saveSecuritySettings();
            if (!res?.success) throw new Error(res?.message || 'Failed to save security settings');
        } else if (activeTab === 'system') {
            const res = await saveSystemSettings();
            if (!res?.success) throw new Error(res?.message || 'Failed to save system settings');
        }

        clearTimeout(timeoutId);
        alert('Settings saved successfully.');
        await loadAllSettings();

        // Auto-update sidebar company name if on embedded dashboard page
        const sidebarHeader = document.querySelector('.sidebar-header h2');
        if (sidebarHeader) {
            const companyNameInput = document.getElementById('company_name');
            if (companyNameInput && activeTab === 'company') {
                sidebarHeader.textContent = companyNameInput.value.trim() || sidebarHeader.textContent;
            }
        }
    } catch (error) {
        clearTimeout(timeoutId);
        console.error('Error saving active tab settings:', error);
        alert(error?.message || 'Error saving settings. Please try again.');
    } finally {
        // Always re-enable save button
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = saveBtn.dataset.originalHtml || '<i class="fas fa-save"></i> Save Settings';
        }
    }
}

async function saveAll() {
    if (isPayrollStaffSettingsView()) {
        return;
    }

    const companyErrors = validateCompanyInfo();
    if (companyErrors.length > 0) {
        alert('Please fix the following errors in Company tab:\n\n' + companyErrors.join('\n'));
        switchTab('company');
        return;
    }

    const payrollErrors = validatePayrollSettings();
    if (payrollErrors.length > 0) {
        alert('Please fix the following errors in Payroll tab:\n\n' + payrollErrors.join('\n'));
        switchTab('payroll');
        return;
    }

    const notificationErrors = validateNotificationSettings();
    if (notificationErrors.length > 0) {
        alert('Please fix the following errors in Notifications tab:\n\n' + notificationErrors.join('\n'));
        switchTab('notifications');
        return;
    }

    if (!(await window.showConfirmModal('Are you sure you want to save all settings?', {
        title: 'Confirm Settings Save',
        confirmText: 'Save All',
        type: 'success'
    }))) {
        return;
    }

    try {
        const results = await Promise.all([
            saveCompanySettings(),
            savePayrollSettings(),
            saveNotificationSettings(),
            saveSecuritySettings(),
            saveSystemSettings()
        ]);

        const failed = results.filter((result) => !result.success);
        if (failed.length > 0) {
            alert('Some settings failed to save:\n\n' + failed.map((result) => result.message).join('\n'));
            return;
        }

        alert('All settings saved successfully.');
        await loadAllSettings();
    } catch (error) {
        console.error('Error saving settings:', error);
        alert('Error saving settings. Please try again.');
    }
}

async function saveMyProfile() {
    const fullName = document.getElementById('profile_full_name')?.value.trim() || '';
    const email = document.getElementById('profile_email')?.value.trim() || '';

    if (!fullName || !email) {
        alert('Full name and email are required.');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    const formData = new FormData();
    formData.append('full_name', fullName);
    formData.append('email', email);
    const photoInput = document.getElementById('profile_photo');
    if (photoInput?.files?.[0]) {
        formData.append('profile_photo', photoInput.files[0]);
    }

    try {
        const data = await fetchJson('../api/update_my_profile.php', {
            method: 'POST',
            body: formData
        });

        alert(data.message || 'Profile updated.');
        if (data.success) {
            const headerName = document.querySelector('.user-name');
            if (headerName) {
                headerName.textContent = fullName;
            }
            if (data.data?.profile_photo_url) {
                document.querySelectorAll('.user-profile .user-avatar').forEach((avatar) => {
                    const photo = document.createElement("img");
                    const photoUrl = new URL(data.data.profile_photo_url, window.location.href);
                    if (!["http:", "https:"].includes(photoUrl.protocol)) return;
                    photo.src = photoUrl.href;
                    photo.alt = fullName;
                    photo.style.cssText = "width:100%;height:100%;object-fit:cover;border-radius:50%";
                    avatar.replaceChildren(photo);
                });
            }
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('Unable to update profile right now.');
    }
}

function previewProfilePhoto(input) {
    const file = input?.files?.[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        alert('Profile photo must be 5 MB or smaller.');
        input.value = '';
        return;
    }

    const preview = document.getElementById('profile_photo_preview');
    const placeholder = document.getElementById('profile_photo_placeholder');
    if (preview) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    }
    if (placeholder) placeholder.style.display = 'none';
}

async function changeMyPassword() {
    const currentPassword = document.getElementById('current_password')?.value || '';
    const newPassword = document.getElementById('new_password')?.value || '';
    const confirmPassword = document.getElementById('confirm_new_password')?.value || '';

    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('Please fill in all password fields.');
        return;
    }

    if (newPassword.length < 8) {
        alert('New password must be at least 8 characters long.');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('New passwords do not match.');
        return;
    }

    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    try {
        const data = await fetchJson('../api/update_my_password.php', {
            method: 'POST',
            body: formData
        });

        alert(data.message || 'Password updated.');
        if (data.success) {
            const fields = ['current_password', 'new_password', 'confirm_new_password'];
            fields.forEach((fieldId) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = '';
                    field.type = 'password';
                }
            });

            document.querySelectorAll('#password .password-toggle i').forEach((icon) => {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            });
        }
    } catch (error) {
        console.error('Error updating password:', error);
        alert('Unable to update password right now.');
    }
}

let addUserBackgroundState = null;

function trapAddUserFocus(event) {
    if (event.key !== 'Tab' || document.getElementById('actionResultModal')?.classList.contains('active')) return;
    const modal = document.getElementById('addUserModal');
    const controls = Array.from(modal.querySelectorAll('input, select, textarea, button, [tabindex]'))
        .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length);
    const first = controls[0];
    const last = controls[controls.length - 1];
    if (!first) return;
    if (event.shiftKey && (document.activeElement === first || !modal.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !modal.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
    }
}

function openAddUserModal() {
    const search = document.getElementById('userSearchInput');
    const panel = document.getElementById('users');
    if (!addUserBackgroundState) {
        addUserBackgroundState = {
            searchValue: search?.value || '', searchDisabled: search?.disabled || false,
            panelInert: panel?.inert || false, opener: document.activeElement
        };
    }
    if (search) {
        search.blur();
        search.disabled = true;
    }
    if (panel) panel.inert = true;
    document.addEventListener('keydown', trapAddUserFocus);
    window.resetUserEmailAvailability?.('new');
    const roleSelect = document.getElementById('newUserRole');
    if (roleSelect) {
        const adminExists = userAllUsersData.some(user => user.role === 'Admin');
        const adminOption = Array.from(roleSelect.options).find(option => option.value === 'Admin');
        if (adminOption) adminOption.disabled = adminExists;
        if (roleSelect.value === 'Admin') roleSelect.value = 'Payroll Staff';
    }
    document.getElementById('addUserModal').style.display = 'flex';
    validateUserIdentityFields('new');
    document.getElementById('userSearchInput')?.blur();
    window.setTimeout(() => document.getElementById('newUserFirstName')?.focus(), 0);
}

function splitUserName(fullName) {
    const parts = String(fullName || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length < 2) return { firstName: parts[0] || '', lastName: '' };
    return { firstName: parts.slice(0, -1).join(' '), lastName: parts.at(-1) };
}

function handleUserNameInput(event, mode) {
    const field = event?.target;
    if (!field) return;

    const originalValue = field.value;
    const hasNumber = /\d/u.test(originalValue);
    const hasSpecialCharacter = /[^\p{L}\s\d]/u.test(originalValue);

    // Remove invalid characters immediately, including pasted or dropped text.
    field.value = originalValue.replace(/\d/gu, '').replace(/[^\p{L}\s]/gu, '');
    validateUserIdentityFields(mode);

    if (hasNumber || hasSpecialCharacter) {
        const prefix = mode === 'edit' ? 'editUser' : 'newUser';
        const isFirstName = field.id === `${prefix}FirstName`;
        const label = isFirstName ? 'First name' : 'Last name';
        const errorId = `${prefix}${isFirstName ? 'FirstName' : 'LastName'}Error`;
        const message = document.getElementById(errorId);
        const button = document.getElementById(mode === 'edit' ? 'editUserSubmitBtn' : 'addUserSubmitBtn');
        const reason = hasNumber && hasSpecialCharacter
            ? 'numbers or special characters'
            : (hasNumber ? 'numbers' : 'special characters');

        field.classList.remove('field-valid');
        field.classList.add('field-invalid');
        field.setAttribute('aria-invalid', 'true');
        if (message) {
            message.textContent = `${label} cannot contain ${reason}.`;
            message.classList.remove('valid');
            message.setAttribute('aria-live', 'assertive');
        }
        if (button) button.disabled = true;
    }
}

function validateUserIdentityFields(mode, showRequired = false) {
    const prefix = mode === 'edit' ? 'editUser' : 'newUser';
    const first = document.getElementById(`${prefix}FirstName`);
    const last = document.getElementById(`${prefix}LastName`);
    const email = document.getElementById(`${prefix}Email`);
    const button = document.getElementById(mode === 'edit' ? 'editUserSubmitBtn' : 'addUserSubmitBtn');
    const namePattern = /^[\p{L}]+(?: [\p{L}]+)*$/u;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const validate = (field, errorId, pattern, label) => {
        const message = document.getElementById(errorId);
        const value = field?.value.trim() || '';
        const valid = pattern.test(value);
        field?.classList.toggle('field-valid', valid);
        field?.classList.toggle('field-invalid', !valid && (showRequired || value !== ''));
        if (message) {
            message.textContent = valid ? 'Looks good.' : ((showRequired || value) ? `Enter a valid ${label}.` : '');
            message.classList.toggle('valid', valid);
        }
        return valid;
    };

    const validateName = (field, errorId, label) => {
        const message = document.getElementById(errorId);
        const value = field?.value || '';
        const trimmed = value.trim();
        let error = '';

        if (!trimmed) {
            if (showRequired) error = `${label} is required.`;
        } else if (/\d/u.test(value)) {
            error = `${label} cannot contain numbers.`;
        } else if (/[^\p{L}\s]/u.test(value)) {
            error = `${label} cannot contain special characters.`;
        } else if (/^\s|\s$|\s{2,}/u.test(value)) {
            error = `${label} cannot start, end, or contain repeated spaces.`;
        } else if (trimmed.length > 50) {
            error = `${label} must not exceed 50 characters.`;
        } else if (!namePattern.test(trimmed)) {
            error = `Enter a valid ${label.toLowerCase()}.`;
        }

        const valid = trimmed !== '' && error === '';
        field?.classList.toggle('field-valid', valid);
        field?.classList.toggle('field-invalid', error !== '');
        field?.setAttribute('aria-invalid', error ? 'true' : 'false');
        if (message) {
            message.textContent = valid ? 'Looks good.' : error;
            message.classList.toggle('valid', valid);
            message.setAttribute('aria-live', 'polite');
        }
        return valid;
    };

    let firstValid = validateName(first, `${prefix}FirstNameError`, 'First name');
    let lastValid = validateName(last, `${prefix}LastNameError`, 'Last name');

    // Full names are unique across user accounts; ignore the account being edited.
    if (firstValid && lastValid) {
        const normalizedFullName = `${first.value.trim()} ${last.value.trim()}`
            .replace(/\s+/gu, ' ')
            .toLocaleLowerCase();
        const currentUserId = mode === 'edit'
            ? Number(document.getElementById('editUserId')?.value || 0)
            : 0;
        const duplicateName = userAllUsersData.some((user) => (
            Number(user.id) !== currentUserId
            && String(user.full_name || '').trim().replace(/\s+/gu, ' ').toLocaleLowerCase() === normalizedFullName
        ));

        if (duplicateName) {
            const duplicateMessage = 'This first and last name combination is already registered.';
            [first, last].forEach((field) => {
                field?.classList.remove('field-valid');
                field?.classList.add('field-invalid');
                field?.setAttribute('aria-invalid', 'true');
            });
            [`${prefix}FirstNameError`, `${prefix}LastNameError`].forEach((errorId) => {
                const message = document.getElementById(errorId);
                if (message) {
                    message.textContent = duplicateMessage;
                    message.classList.remove('valid');
                    message.setAttribute('aria-live', 'polite');
                }
            });
            firstValid = false;
        }
    }
    let emailValid = validate(email, `${prefix}EmailError`, emailPattern, 'email address');

    if (emailValid) {
        const normalizedEmail = email.value.trim().toLocaleLowerCase();
        const currentUserId = mode === 'edit'
            ? Number(document.getElementById('editUserId')?.value || 0)
            : 0;
        const duplicateEmail = userAllUsersData.some((user) => (
            Number(user.id) !== currentUserId
            && String(user.email || '').trim().toLocaleLowerCase() === normalizedEmail
        ));

        if (duplicateEmail) {
            const message = document.getElementById(`${prefix}EmailError`);
            email.classList.remove('field-valid');
            email.classList.add('field-invalid');
            email.setAttribute('aria-invalid', 'true');
            if (message) {
                message.textContent = 'This email address is already registered.';
                message.classList.remove('valid');
                message.setAttribute('aria-live', 'polite');
            }
            emailValid = false;
        }
    }
    if (!emailValid) {
        window.resetUserEmailAvailability?.(mode);
        email?.setCustomValidity('Enter an available email address.');
    }
    const emailAvailable = emailValid && (window.checkUserEmailAvailability?.(mode) ?? false);
    const isValid = firstValid && lastValid && emailValid && emailAvailable;
    if (button) button.disabled = button.dataset.saving === 'true' || !Boolean(isValid);
    return Boolean(isValid);
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    document.removeEventListener('keydown', trapAddUserFocus);
    if (addUserBackgroundState) {
        const search = document.getElementById('userSearchInput');
        const panel = document.getElementById('users');
        if (search) {
            search.value = addUserBackgroundState.searchValue;
            search.disabled = addUserBackgroundState.searchDisabled;
        }
        if (panel) panel.inert = addUserBackgroundState.panelInert;
        addUserBackgroundState.opener?.focus();
        addUserBackgroundState = null;
    }
    document.getElementById('newUserFirstName').value = '';
    document.getElementById('newUserLastName').value = '';
    document.getElementById('newUserEmail').value = '';
    document.getElementById('newUserRole').value = 'Payroll Staff';
    validateUserIdentityFields('new');
}

function togglePassword(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    if (!field || !icon) {
        return;
    }

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function validatePasswords() {
    const password = document.getElementById('newUserPassword').value;
    const confirmPassword = document.getElementById('newUserPasswordConfirm').value;
    const passwordField = document.getElementById('newUserPassword');
    const confirmField = document.getElementById('newUserPasswordConfirm');
    const passwordError = document.getElementById('passwordError');
    const confirmError = document.getElementById('confirmPasswordError');

    passwordField.classList.remove('password-error', 'password-success');
    confirmField.classList.remove('password-error', 'password-success');
    passwordError.classList.remove('show');
    confirmError.classList.remove('show');

    if (password && password.length < 8) {
        passwordField.classList.add('password-error');
        passwordError.textContent = 'Password must be at least 8 characters long.';
        passwordError.classList.add('show');
    } else if (password) {
        passwordField.classList.add('password-success');
    }

    if (confirmPassword) {
        if (password !== confirmPassword) {
            confirmField.classList.add('password-error');
            confirmError.textContent = 'Passwords do not match.';
            confirmError.classList.add('show');
        } else if (password.length >= 8) {
            confirmField.classList.add('password-success');
        }
    }
}

async function handleAddUser() {
    const button = document.getElementById('addUserSubmitBtn');
    if (button?.dataset.saving === 'true') return;
    const firstName = document.getElementById('newUserFirstName').value.trim();
    const lastName = document.getElementById('newUserLastName').value.trim();
    const email = document.getElementById('newUserEmail').value.trim();
    const role = document.getElementById('newUserRole').value;
    if (!validateUserIdentityFields('new', true) || !role) {
        showSettingsActionResult(false, 'Please correct the highlighted fields and wait for the availability check to finish.', 'User Creation');
        return;
    }

    const formData = new FormData();
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('email', email);
    formData.append('role', role);

    const originalText = button?.textContent;
    if (button) {
        button.dataset.saving = 'true';
        button.disabled = true;
        button.textContent = 'Creating account...';
    }
    window.showProcessingModal?.('Creating account and sending the temporary password...');
    try {
        const data = await fetchJson('../api/add_user.php', { method: 'POST', body: formData });
        if (data.success) {
            closeAddUserModal();
        }
        showSettingsActionResult(data.success, data.message, 'User Creation');
        window.resetUserEmailAvailability?.('new');
        if (data.success) await loadUsers(true);
    } catch (error) {
        showSettingsActionResult(false, 'Unable to confirm account creation. Refresh the user list before retrying.', 'User Creation');
    } finally {
        if (button) {
            delete button.dataset.saving;
            button.textContent = originalText;
        }
        validateUserIdentityFields('new');
    }
}

let userLoadPromise = null;
async function loadUsers(force = false) {
    if (force && userLoadPromise) await userLoadPromise;
    if (!userLoadPromise) {
        userLoadPromise = fetchUsersList().finally(() => { userLoadPromise = null; });
    }
    return userLoadPromise;
}

async function fetchUsersList() {
    try {
        const data = await fetchJson('../api/get_users.php');
        if (!data.success || !data.data) {
            console.error('loadUsers failed - API returned:', data?.message || 'no message');
            return false;
        }

        userAllUsersData = data.data;
        userCurrentPage = 1;
        applyUserFilters();
        
        return true;
    } catch (error) {
        console.error('Error loading users:', error);
        return false;
    }
}

function openEditUserModal(userId, userName, userEmail, userRole, userStatus) {
    window.resetUserEmailAvailability?.('edit');
    const idField = document.getElementById('editUserId');
    const firstNameField = document.getElementById('editUserFirstName');
    const lastNameField = document.getElementById('editUserLastName');
    const emailField = document.getElementById('editUserEmail');
    const roleField = document.getElementById('editUserRole');
    const statusField = document.getElementById('editUserStatus');
    const editModal = document.getElementById('editUserModal');

    if (!idField || !firstNameField || !lastNameField || !emailField || !roleField || !statusField || !editModal) {
        console.error('Edit user modal elements not found in DOM');
        alert('Unable to open edit form. Some form elements are missing. Please refresh the page.');
        return;
    }

    idField.value = userId;
    const storedUser = userAllUsersData.find(user => String(user.id) === String(userId));
    const parsedName = storedUser?.first_name != null
        ? { firstName: storedUser.first_name, lastName: storedUser.last_name || '' }
        : splitUserName(userName);
    firstNameField.value = parsedName.firstName;
    lastNameField.value = parsedName.lastName;
    emailField.value = userEmail;
    roleField.value = userRole;
    statusField.value = userStatus;
    editModal.style.display = 'flex';
    editModal.setAttribute('aria-hidden', 'false');

    // Disable role dropdown for Assistant Admin
    roleField.disabled = (window.currentUserRole === 'Assistant Admin');
    roleField.style.opacity = roleField.disabled ? '0.5' : '1';

    validateUserIdentityFields('edit');
    firstNameField.focus();
}

function closeEditUserModal() {
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.style.display = 'none';
        editModal.setAttribute('aria-hidden', 'true');
    }
    const idField = document.getElementById('editUserId');
    if (idField) idField.value = '';
    const firstNameField = document.getElementById('editUserFirstName');
    if (firstNameField) firstNameField.value = '';
    const lastNameField = document.getElementById('editUserLastName');
    if (lastNameField) lastNameField.value = '';
    const emailField = document.getElementById('editUserEmail');
    if (emailField) emailField.value = '';
    const roleSelect = document.getElementById('editUserRole');
    if (roleSelect) {
        roleSelect.value = 'Payroll Staff';
        roleSelect.disabled = false;
        roleSelect.style.opacity = '1';
    }
    const statusField = document.getElementById('editUserStatus');
    if (statusField) statusField.value = 'Active';
}

async function handleEditUser() {
    const button = document.getElementById('editUserSubmitBtn');
    if (button?.dataset.saving === 'true') return;
    const userIdInput = document.getElementById('editUserId');
    const firstNameInput = document.getElementById('editUserFirstName');
    const lastNameInput = document.getElementById('editUserLastName');
    const emailInput = document.getElementById('editUserEmail');
    const roleInput = document.getElementById('editUserRole');
    const statusInput = document.getElementById('editUserStatus');

    if (!userIdInput || !firstNameInput || !lastNameInput || !emailInput || !roleInput || !statusInput) {
        alert('Form elements not found. Please refresh the page.');
        return;
    }

    const userId = userIdInput.value;
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const role = roleInput.value;
    const status = statusInput.value;

    if (!userId || !validateUserIdentityFields('edit', true) || !role || !status) {
        alert('Please fill in all required fields.');
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('email', email);
    formData.append('role', role);
    formData.append('status', status);

    const originalText = button?.textContent;
    if (button) {
        button.dataset.saving = 'true';
        button.disabled = true;
        button.textContent = 'Saving...';
    }
    try {
        const data = await fetchJson('../api/edit_user.php', {
            method: 'POST',
            body: formData
        });

        if (data.success) {
            closeEditUserModal();
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            await loadUsers(true);
            showSettingsActionResult(true, data.message || 'User updated successfully', 'User Update');
        } else {
            showSettingsActionResult(false, data.message || 'Failed to update user', 'User Update');
        }
    } catch (error) {
        console.error('Error editing user:', error);
        showSettingsActionResult(false, 'Unable to update user due to a network or server error. Please try again.', 'User Update');
    } finally {
        if (button) {
            delete button.dataset.saving;
            button.textContent = originalText;
        }
        validateUserIdentityFields('edit');
    }
}

async function copyUser(userId) {
    try {
        const user = userAllUsersData.find((entry) => Number(entry.id) === Number(userId));
        if (!user) {
            alert('User not found.');
            return;
        }

        await navigator.clipboard.writeText(user.full_name + ' <' + user.email + '>');

    } catch (error) {
        console.error('Copy user failed:', error);
        alert('Unable to copy user details.');
    }
}

function openResetUserPasswordModal(userId, userName) {
    document.getElementById('resetPasswordUserId').value = userId;
    document.getElementById('resetPasswordUserName').value = userName || 'User';
    document.getElementById('resetUserPassword').value = '';
    document.getElementById('resetUserPasswordConfirm').value = '';
    document.getElementById('resetUserPassword').type = 'password';
    document.getElementById('resetUserPasswordConfirm').type = 'password';
    document.getElementById('resetUserPassword').classList.remove('password-error', 'password-success');
    document.getElementById('resetUserPasswordConfirm').classList.remove('password-error', 'password-success');
    document.getElementById('resetUserPasswordError').classList.remove('show');
    document.getElementById('resetUserPasswordConfirmError').classList.remove('show');
    document.querySelectorAll('#resetUserPasswordModal .password-toggle i').forEach((icon) => {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    });
    document.getElementById('resetUserPasswordModal').style.display = 'flex';
}

function closeResetUserPasswordModal() {
    document.getElementById('resetUserPasswordModal').style.display = 'none';
    document.getElementById('resetPasswordUserId').value = '';
    document.getElementById('resetPasswordUserName').value = '';
    document.getElementById('resetUserPassword').value = '';
    document.getElementById('resetUserPasswordConfirm').value = '';
}

function validateResetUserPassword() {
    const password = document.getElementById('resetUserPassword').value;
    const confirmPassword = document.getElementById('resetUserPasswordConfirm').value;
    const passwordField = document.getElementById('resetUserPassword');
    const confirmField = document.getElementById('resetUserPasswordConfirm');
    const passwordError = document.getElementById('resetUserPasswordError');
    const confirmError = document.getElementById('resetUserPasswordConfirmError');

    passwordField.classList.remove('password-error', 'password-success');
    confirmField.classList.remove('password-error', 'password-success');
    passwordError.classList.remove('show');
    confirmError.classList.remove('show');

    if (password && password.length < 8) {
        passwordField.classList.add('password-error');
        passwordError.textContent = 'Password must be at least 8 characters long.';
        passwordError.classList.add('show');
    } else if (password) {
        passwordField.classList.add('password-success');
    }

    if (confirmPassword) {
        if (password !== confirmPassword) {
            confirmField.classList.add('password-error');
            confirmError.textContent = 'Passwords do not match.';
            confirmError.classList.add('show');
        } else if (password.length >= 8) {
            confirmField.classList.add('password-success');
        }
    }
}

async function handleResetUserPassword() {
    const userId = document.getElementById('resetPasswordUserId').value;
    const password = document.getElementById('resetUserPassword').value;
    const confirmPassword = document.getElementById('resetUserPasswordConfirm').value;

    if (!userId || !password || !confirmPassword) {
        alert('Please fill in all password fields.');
        return;
    }

    if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        validateResetUserPassword();
        return;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        validateResetUserPassword();
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('new_password', password);
    formData.append('confirm_password', confirmPassword);

    const data = await fetchJson('../api/update_user_password.php', {
        method: 'POST',
        body: formData
    });

    showSettingsActionResult(data.success, data.message, 'Password Reset');
    if (data.success) {
        closeResetUserPasswordModal();
    }
}

async function unlockUser(userId, userName) {
    if (!(await window.showConfirmModal(`Unlock ${userName}'s account?`, {
        title: 'Confirm Account Unlock',
        confirmText: 'Unlock',
        type: 'success'
    }))) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        const data = await fetchJson('../api/unlock_user.php', { method: 'POST', body: formData });
        showSettingsActionResult(data.success, data.message, 'Account Unlock');
        if (data.success) await loadUsers(true);
    } catch (error) {
        showSettingsActionResult(false, error.message || 'Unable to unlock the account.', 'Account Unlock');
    }
}

async function deleteUser(userId) {
    if (!(await window.showConfirmModal('Are you sure you want to deactivate this user?', {
        title: 'Confirm User Deactivation',
        confirmText: 'Deactivate',
        type: 'error'
    }))) {
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('current_user_id', 0);

    try {
        const data = await fetchJson('../api/delete_user.php', {
            method: 'POST',
            body: formData
        });

        if (data.success) {
            await loadUsers(true);
            showSettingsActionResult(true, data.message || 'User deactivated successfully', 'User Deactivation');
        } else {
            showSettingsActionResult(false, data.message || 'Failed to deactivate user', 'User Deactivation');
        }
    } catch (error) {
        console.error('Error deactivating user:', error);
        showSettingsActionResult(false, 'Unable to deactivate user due to a network or server error. Please try again.', 'User Deactivation');
    }
}

async function runBackup() {
    try {
        const data = await fetchJson('../api/backup_system.php', { method: 'POST' });
        showSettingsActionResult(data.success, data.message, 'System Backup');
        if (data.success) await loadSystemSettings();
    } catch (error) {
        showSettingsActionResult(false, error.message || 'Unable to run the system backup.', 'System Backup');
    }
}

async function clearSystemCache() {
    try {
        const data = await fetchJson('../api/clear_cache.php', { method: 'POST' });
        showSettingsActionResult(data.success, data.message, 'Cache Clear');
    } catch (error) {
        showSettingsActionResult(false, error.message || 'Unable to clear the system cache.', 'Cache Clear');
    }
}

async function generateSampleData() {
    if (!(await window.showConfirmModal('Generate sample data for demonstration?\n\nThis will create 3 sites, 30 workers, 7 days of attendance, payroll, overtime, and timekeeper reports.\nExisting records will not be overwritten.', {
        title: 'Confirm Sample Data',
        confirmText: 'Generate',
        type: 'success'
    }))) {
        return;
    }

    const data = await fetchJson('../api/seed_sample_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ confirm: true })
    });

    if (!data.success) {
        showSettingsActionResult(false, data.message || 'Sample data generation failed.', 'Sample Data Generation');
        return;
    }

    const counts = data.counts || {};
    const lines = [
        data.message,
        '',
        'Sites: ' + (counts.sites ?? 0),
        'Workers: ' + (counts.workers ?? 0),
        'Attendance records: ' + (counts.attendance ?? 0),
        'Overtime requests: ' + (counts.overtime ?? 0),
        'Timekeeper reports: ' + (counts.reports ?? 0),
        'Payroll batches: ' + (counts.payroll_batches ?? 0),
        'Payroll worker rows: ' + (counts.payroll_workers ?? 0),
        '',
        'Pay period: ' + (data.period_start || '-') + ' to ' + (data.period_end || '-'),
    ];

    if (data.demo_accounts?.payroll_staff) {
        lines.push('', 'Demo payroll staff login:', data.demo_accounts.payroll_staff.email + ' / ' + data.demo_accounts.payroll_staff.password);
    }

    showSettingsActionResult(true, lines.join('\n'), 'Sample Data Generation');
}

async function resetAllSettings() {
    if (!(await window.showConfirmModal('Reset all settings to defaults?', {
        title: 'Confirm Settings Reset',
        confirmText: 'Reset',
        type: 'error'
    }))) {
        return;
    }

    const data = await fetchJson('../api/reset_settings.php', { method: 'POST' });
    showSettingsActionResult(data.success, data.message, 'Settings Reset');
    if (data.success) {
        await loadAllSettings();
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    updateDateTime();
    setInterval(updateDateTime, 60000);
    await loadUsers();
});

window.switchTab = switchTab;
window.handleOvertimeToggle = handleOvertimeToggle;
window.handleLogoPreview = handleLogoPreview;
window.saveAll = saveAll;
window.saveActiveTab = saveActiveTab;
window.openAddUserModal = openAddUserModal;
window.closeAddUserModal = closeAddUserModal;
window.togglePassword = togglePassword;
window.validatePasswords = validatePasswords;
window.handleAddUser = handleAddUser;
window.openEditUserModal = openEditUserModal;
window.closeEditUserModal = closeEditUserModal;
window.handleEditUser = handleEditUser;
window.openResetUserPasswordModal = openResetUserPasswordModal;
window.closeResetUserPasswordModal = closeResetUserPasswordModal;
window.validateResetUserPassword = validateResetUserPassword;
window.handleUserNameInput = handleUserNameInput;
window.handleResetUserPassword = handleResetUserPassword;
window.copyUser = copyUser;
window.deleteUser = deleteUser;
window.unlockUser = unlockUser;
window.runBackup = runBackup;
window.clearSystemCache = clearSystemCache;
window.resetAllSettings = resetAllSettings;
window.generateSampleData = generateSampleData;
async function cleanupNullUsers() {
    if (!(await window.showConfirmModal(
        'Remove all users with missing name or email?\n\nThis will permanently delete incomplete user records from the database.',
        { title: 'Clean Null Users', confirmText: 'Clean', type: 'error' }
    ))) {
        return;
    }

    try {
        const response = await fetch('../api/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cleanup_null' })
        });
        const data = await response.json();

        if (data.success) {
            await loadUsers();
            showSettingsActionResult(true, data.message || 'Null users cleaned successfully.', 'Clean Null Users');
        } else {
            showSettingsActionResult(false, data.message || 'Failed to clean null users.', 'Clean Null Users');
        }
    } catch (error) {
        console.error('Error cleaning null users:', error);
        showSettingsActionResult(false, 'Unable to clean null users due to a network or server error.', 'Clean Null Users');
    }
}

// ============ User Filter & Pagination State ============
let userCurrentPage = 1;
let userCurrentLimit = 20;
let userAllUsersData = [];
let userTotalFiltered = 0;

function onUserSearch() {
    if (addUserBackgroundState) {
        document.getElementById('userSearchInput').value = addUserBackgroundState.searchValue;
        return;
    }
    clearTimeout(window._userSearchTimer);
    window._userSearchTimer = setTimeout(() => {
        userCurrentPage = 1;
        applyUserFilters();
    }, 400);
}

function onUserFilterChange() {
    userCurrentPage = 1;
    applyUserFilters();
}

function onUserLimitChange() {
    const limitSelect = document.getElementById('userLimitSelect');
    if (limitSelect) {
        userCurrentLimit = parseInt(limitSelect.value) || 20;
    }
    userCurrentPage = 1;
    applyUserFilters();
}

function applyUserFilters() {
    const searchVal = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
    const roleVal = document.getElementById('userRoleFilter')?.value || '';
    const statusVal = document.getElementById('userStatusFilter')?.value || '';

    let filtered = userAllUsersData.filter(user => {
        if (searchVal) {
            const name = (user.full_name || '').toLowerCase();
            const email = (user.email || '').toLowerCase();
            if (!name.includes(searchVal) && !email.includes(searchVal)) {
                return false;
            }
        }
        if (roleVal) {
            const userRole = normalizeRoleLabel(user.role || '');
            if (userRole !== roleVal) return false;
        }
        if (statusVal) {
            if ((user.status || '') !== statusVal) return false;
        }
        return true;
    });

    userTotalFiltered = filtered.length;
    renderUserTable(filtered);
    renderUserPagination();
}

function renderUserTable(filteredData) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;

    const start = (userCurrentPage - 1) * userCurrentLimit;
    const end = Math.min(start + userCurrentLimit, filteredData.length);
    const pageData = filteredData.slice(start, end);

    tbody.innerHTML = '';

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af;">No users found matching your filters.</td></tr>';
        return;
    }

    pageData.forEach((user) => {
        const tr = document.createElement('tr');

        const userCell = document.createElement('td');
        const name = document.createElement('div');
        name.textContent = user.full_name || 'Unnamed user';
        const email = document.createElement('div');
        email.className = 'muted';
        email.textContent = user.email || 'No email address';
        userCell.append(name, email);

        const roleCell = document.createElement('td');
        roleCell.textContent = normalizeRoleLabel(user.role || 'User');

        const statusCell = document.createElement('td');
        const status = document.createElement('span');
        const isActive = (user.status || '').toLowerCase() === 'active';
        const accountPending = Boolean(user.account_pending);
        status.className = 'badge ' + (isActive ? 'green' : (accountPending ? '' : 'red'));
        if (accountPending) {
            status.style.cssText = 'background:#fff7ed;color:#c2410c;';
        }
        status.textContent = user.status || 'Inactive';
        statusCell.appendChild(status);

        const isLocked = Boolean(user.account_locked_at);
        if (isLocked && isActive) {
            const lockBadge = document.createElement('span');
            lockBadge.className = 'badge';
            lockBadge.innerHTML = '<i class="fas fa-lock"></i> Locked';
            lockBadge.style.cssText = 'margin-left:6px; background:#fef2f2; color:#dc2626;';
            statusCell.appendChild(lockBadge);
        }

        const loginCell = document.createElement('td');
        loginCell.textContent = user.last_login || 'Never';
        if (isLocked && isActive) {
            const lockIcon = document.createElement('i');
            lockIcon.className = 'fas fa-lock';
            lockIcon.style.cssText = 'margin-left:6px; color:#dc2626; font-size:12px;';
            lockIcon.title = 'Account is locked due to failed login attempts';
            loginCell.appendChild(lockIcon);
        }

        const actionsCell = document.createElement('td');
        const actionGroup = document.createElement('div');
        actionGroup.className = 'actions';
        if (window.canEditUsers && !accountPending) {
            actionGroup.appendChild(createUserActionButton('edit', user.id, 'Edit user', 'fas fa-edit'));
            actionGroup.appendChild(createUserActionButton('password', user.id, 'Reset password', 'fas fa-key'));

            // Always show unlock button, disabled when account is NOT locked
            var unlockBtn = createUserActionButton('unlock', user.id, isLocked ? 'Unlock account' : 'Account not locked', 'fas fa-unlock');
            if (!isLocked) {
                unlockBtn.disabled = true;
                unlockBtn.style.opacity = '0.4';
                unlockBtn.style.cursor = 'not-allowed';
            }
            actionGroup.appendChild(unlockBtn);
        }
        if (window.canAddDeleteUsers && isActive && !accountPending) {
            actionGroup.appendChild(createUserActionButton('deactivate', user.id, 'Deactivate user', 'fas fa-user-slash'));
        }
        actionsCell.appendChild(actionGroup);

        tr.append(userCell, roleCell, statusCell, loginCell, actionsCell);
        tbody.appendChild(tr);
    });

    // Re-attach action listeners
    tbody.querySelectorAll('[data-user-action]').forEach((button) => {
        button.addEventListener('click', function() {
            try {
                if (button.disabled) return;

                var userId = Number(button.dataset.userId);
                var user = userAllUsersData.find(function(entry) { return Number(entry.id) === userId; });
                if (!user) {
                    console.warn('User not found for edit action, userId:', button.dataset.userId);
                    alert('User data not found. Please reload the page and try again.');
                    return;
                }

                if (button.dataset.userAction === 'edit') {
                    openEditUserModal(user.id, user.full_name || '', user.email || '', normalizeRoleLabel(user.role || 'User'), user.status || 'Active');
                } else if (button.dataset.userAction === 'password') {
                    openResetUserPasswordModal(user.id, user.full_name || 'User');
                } else if (button.dataset.userAction === 'unlock') {
                    unlockUser(user.id, user.full_name || 'User');
                } else if (button.dataset.userAction === 'deactivate') {
                    deleteUser(user.id);
                }
            } catch (err) {
                console.error('Error handling user action:', err);
                alert('An unexpected error occurred. Please try again.');
            }
        });
    });
}

function createUserActionButton(action, userId, title, iconClass) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'icon-btn' + (action === 'deactivate' ? ' delete' : '');
    button.dataset.userAction = action;
    button.dataset.userId = String(userId);
    button.title = title;

    const icon = document.createElement('i');
    icon.className = iconClass;
    button.appendChild(icon);
    return button;
}

function renderUserPagination() {
    const infoEl = document.getElementById('userPaginationInfo');
    const prevBtn = document.getElementById('userPrevPage');
    const nextBtn = document.getElementById('userNextPage');
    const pageNumbersEl = document.getElementById('userPageNumbers');

    if (!infoEl || !prevBtn || !nextBtn || !pageNumbersEl) return;

    const totalPages = Math.max(1, Math.ceil(userTotalFiltered / userCurrentLimit));
    const start = userTotalFiltered === 0 ? 0 : (userCurrentPage - 1) * userCurrentLimit + 1;
    const end = Math.min(userCurrentPage * userCurrentLimit, userTotalFiltered);

    infoEl.textContent = `Showing ${start}-${end} of ${userTotalFiltered}`;
    prevBtn.disabled = userCurrentPage <= 1;
    nextBtn.disabled = userCurrentPage >= totalPages;

    pageNumbersEl.innerHTML = '';
    const maxVisible = 5;
    let startPage = Math.max(1, userCurrentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        const firstBtn = document.createElement('button');
        firstBtn.className = 'user-page-num';
        firstBtn.textContent = '1';
        firstBtn.onclick = () => userGoToPage(1);
        pageNumbersEl.appendChild(firstBtn);
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'user-page-ellipsis';
            ellipsis.textContent = '...';
            pageNumbersEl.appendChild(ellipsis);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.className = 'user-page-num' + (i === userCurrentPage ? ' active' : '');
        btn.textContent = i;
        btn.onclick = () => userGoToPage(i);
        pageNumbersEl.appendChild(btn);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'user-page-ellipsis';
            ellipsis.textContent = '...';
            pageNumbersEl.appendChild(ellipsis);
        }
        const lastBtn = document.createElement('button');
        lastBtn.className = 'user-page-num';
        lastBtn.textContent = totalPages;
        lastBtn.onclick = () => userGoToPage(totalPages);
        pageNumbersEl.appendChild(lastBtn);
    }
}

function userChangePage(delta) {
    userGoToPage(userCurrentPage + delta);
}

function userGoToPage(page) {
    const totalPages = Math.max(1, Math.ceil(userTotalFiltered / userCurrentLimit));
    if (page < 1 || page > totalPages || page === userCurrentPage) return;
    userCurrentPage = page;
    applyUserFilters();
    const tableContainer = document.querySelector('#users .table-container');
    if (tableContainer) tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

window.onUserSearch = onUserSearch;
window.onUserFilterChange = onUserFilterChange;
window.onUserLimitChange = onUserLimitChange;
window.userChangePage = userChangePage;
function escapeSettingHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

async function loadPositionCatalogSettings() {
    const body = document.getElementById('positionCatalogTableBody');
    if (!body) return;
    try {
        const data = await fetchJson('../api/get_position_catalog.php');
        const positions = data.success ? (data.positions || []) : [];
        body.innerHTML = positions.length ? positions.map((position) => `
            <tr>
                <td>${escapeSettingHtml(position.position_name)}</td>
                <td>PHP ${Number(position.hourly_rate).toFixed(2)}</td>
                <td>PHP ${Number(position.salary_rate).toFixed(2)}</td>
                <td>
                    <button type="button" class="btn-secondary position-catalog-edit"
                        data-position-id="${Number(position.id)}"
                        data-position-name="${escapeSettingHtml(position.position_name)}"
                        data-hourly-rate="${Number(position.hourly_rate)}"
                        data-salary-rate="${Number(position.salary_rate)}"><i class="fas fa-pen"></i> Edit</button>
                    <button type="button" class="btn-danger position-catalog-delete" data-position-id="${Number(position.id)}"><i class="fas fa-trash"></i> Remove</button>
                </td>
            </tr>`).join('') : '<tr><td colspan="4">No positions added yet.</td></tr>';
    } catch (error) {
        body.innerHTML = '<tr><td colspan="4">Unable to load positions. Please reload the page.</td></tr>';
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const resetPositionCatalogForm = () => {
        document.getElementById('positionCatalogForm')?.reset();
        document.getElementById('catalog_position_id').value = '';
        document.getElementById('positionCatalogSubmit').innerHTML = '<i class="fas fa-plus"></i>Add Position';
        document.getElementById('positionCatalogCancel').style.display = 'none';
    };
    loadPositionCatalogSettings().catch(console.error);
    document.getElementById('positionCatalogForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
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
        window.showCrudResultModal?.(result.success, result.message, 'Position Settings');
        if (result.success) {
            resetPositionCatalogForm();
            await loadPositionCatalogSettings();
        }
    });
    document.getElementById('positionCatalogTableBody')?.addEventListener('click', async (event) => {
        const editButton = event.target.closest('.position-catalog-edit');
        if (editButton) {
            document.getElementById('catalog_position_id').value = editButton.dataset.positionId;
            document.getElementById('catalog_position_name').value = editButton.dataset.positionName;
            document.getElementById('catalog_hourly_rate').value = editButton.dataset.hourlyRate;
            document.getElementById('catalog_salary_rate').value = editButton.dataset.salaryRate;
            document.getElementById('positionCatalogSubmit').innerHTML = '<i class="fas fa-save"></i>Update Position';
            document.getElementById('positionCatalogCancel').style.display = '';
            document.getElementById('catalog_position_name').focus();
            return;
        }
        const button = event.target.closest('.position-catalog-delete');
        if (!button || !confirm('Remove this position from the employee position list?')) return;
        const result = await fetchJson('../api/delete_position_catalog.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(button.dataset.positionId) })
        });
        window.showCrudResultModal?.(result.success, result.message, 'Position Settings');
        if (result.success) await loadPositionCatalogSettings();
    });
    document.getElementById('positionCatalogCancel')?.addEventListener('click', resetPositionCatalogForm);
});

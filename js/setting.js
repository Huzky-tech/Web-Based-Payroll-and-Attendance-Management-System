function updateDateTime() {
    const now = new Date();
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12; hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;
    document.getElementById('currentDate').textContent = day + ', ' + month + ' ' + date + ', ' + year;
    document.getElementById('currentTime').textContent = hours + ':' + minutesStr + ' ' + ampm;
}

function switchTab(tab) {
    var tabs = document.querySelectorAll('.pill-tab');
    tabs.forEach(function(p) { p.classList.remove('active'); });
    document.querySelector('[data-tab="' + tab + '"]').classList.add('active');
    var panels = document.querySelectorAll('.panel');
    panels.forEach(function(panel) {
        if (panel.id) {
            panel.style.display = panel.id === tab ? 'block' : 'none';
        }
    });
}

// Handle overtime and night differential toggle
function handleOvertimeToggle() {
    var overtimeToggle = document.getElementById('allow_overtime');
    var overtimeRateInput = document.getElementById('overtime_rate');
    if (overtimeToggle && overtimeRateInput) {
        overtimeRateInput.disabled = !overtimeToggle.checked;
        overtimeRateInput.style.opacity = overtimeToggle.checked ? '1' : '0.5';
    }
}

function handleNightDiffToggle() {
    var nightDiffToggle = document.getElementById('allow_night_diff');
    var nightDiffRateInput = document.getElementById('night_diff_rate');
    if (nightDiffToggle && nightDiffRateInput) {
        nightDiffRateInput.disabled = !nightDiffToggle.checked;
        nightDiffRateInput.style.opacity = nightDiffToggle.checked ? '1' : '0.5';
    }
}

// Validation functions
function validateCompanyInfo() {
    var companyName = document.getElementById('company_name').value.trim();
    var taxId = document.getElementById('tax_id').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var email = document.getElementById('email').value.trim();
    
    var errors = [];
    
    if (!companyName) errors.push('Company Name is required');
    if (!taxId) errors.push('Tax ID Number is required');
    if (phone && !/^[\d\s\-\(\)]+$/.test(phone)) errors.push('Phone Number format is invalid');
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Email Address format is invalid');
    
    return errors;
}

function validatePayrollSettings() {
    var payPeriods = document.getElementById('pay_periods').value;
    var philhealthRate = document.getElementById('philhealth_rate').value.replace('%', '');
    var pagibigRate = document.getElementById('pagibig_rate').value.replace('%', '');
    var overtimeRate = document.getElementById('overtime_rate').value;
    var nightDiffRate = document.getElementById('night_diff_rate').value;
    
    var errors = [];
    
    if (!payPeriods) {
        errors.push('Pay Periods is required');
    }
    
    var philhealth = parseFloat(philhealthRate);
    if (isNaN(philhealth) || philhealth < 0 || philhealth > 100) {
        errors.push('PhilHealth Rate must be a number between 0 and 100');
    }
    
    var pagibig = parseFloat(pagibigRate);
    if (isNaN(pagibig) || pagibig < 0 || pagibig > 100) {
        errors.push('Pag-IBIG Rate must be a number between 0 and 100');
    }
    
    var allowOvertime = document.getElementById('allow_overtime');
    if (allowOvertime && allowOvertime.checked) {
        var otRate = parseFloat(overtimeRate);
        if (isNaN(otRate) || otRate <= 0) {
            errors.push('Overtime Rate must be greater than 0');
        }
    }
    
    var allowNightDiff = document.getElementById('allow_night_diff');
    if (allowNightDiff && allowNightDiff.checked) {
        var ndRate = parseFloat(nightDiffRate);
        if (isNaN(ndRate) || ndRate <= 0) {
            errors.push('Night Differential Rate must be greater than 0');
        }
    }
    
    return errors;
}

// Logo upload preview
function handleLogoPreview(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];
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
        var reader = new FileReader();
        reader.onload = function(e) {
            var previewContainer = document.getElementById('logo_preview');
            if (previewContainer) {
                previewContainer.innerHTML = '<img src="' + e.target.result + '" style="width:70px; height:70px; border-radius:10px; object-fit:cover;">';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Load settings functions
function loadCompanySettings() {
    fetch('../api/get_company_settings.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.data) {
            document.getElementById('company_name').value = data.data.company_name || '';
            document.getElementById('tax_id').value = data.data.tax_id || '';
            document.getElementById('phone').value = data.data.phone || '';
            document.getElementById('email').value = data.data.email || '';
            document.getElementById('address').value = data.data.address || '';
            if (data.data.logo_path) {
                var previewContainer = document.getElementById('logo_preview');
                if (previewContainer) {
                    previewContainer.innerHTML = '<img src="../' + data.data.logo_path + '" style="width:70px; height:70px; border-radius:10px; object-fit:cover;">';
                }
            }
        }
    })
    .catch(function(error) { console.error('Error loading company settings:', error); });
}

function loadPayrollSettings() {
    fetch('../api/get_payroll_settings.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.data) {
            document.getElementById('pay_periods').value = data.data.pay_periods || 'Semi-monthly (1-15, 16-end)';
            document.getElementById('sss_rate').value = data.data.sss_rate || 'Standard';
            document.getElementById('philhealth_rate').value = data.data.philhealth_rate || '3';
            document.getElementById('pagibig_rate').value = data.data.pagibig_rate || '2';
            document.getElementById('tax_table').value = data.data.tax_table || 'Latest BIR Tax Table';
            
            var allowOvertime = data.data.allow_overtime == 1;
            var allowNightDiff = data.data.allow_night_diff == 1;
            document.getElementById('allow_overtime').checked = allowOvertime;
            document.getElementById('allow_night_diff').checked = allowNightDiff;
            
            document.getElementById('overtime_rate').value = data.data.overtime_rate || '1.25';
            document.getElementById('night_diff_rate').value = data.data.night_diff_rate || '1.1';
            
            handleOvertimeToggle();
            handleNightDiffToggle();
        }
    })
    .catch(function(error) { console.error('Error loading payroll settings:', error); });
}

function loadNotificationSettings() {
    fetch('../api/get_notification_settings.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.data) {
            document.getElementById('email_notifications').checked = data.data.email_notifications == 1;
            document.getElementById('in_system_notifications').checked = data.data.in_system_notifications == 1;
            document.getElementById('leave_request_updates').checked = data.data.leave_request_updates == 1;
            document.getElementById('payroll_processing').checked = data.data.payroll_processing == 1;
            document.getElementById('attendance_issues').checked = data.data.attendance_issues == 1;
            document.getElementById('system_updates').checked = data.data.system_updates == 1;
            document.getElementById('daily_reports').checked = data.data.daily_reports == 1;
            document.getElementById('email_digest_frequency').value = data.data.email_digest_frequency || 'Daily';
        }
    })
    .catch(function(error) { console.error('Error loading notification settings:', error); });
}

function loadSecuritySettings() {
    fetch('../api/get_security_settings.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.data) {
            document.getElementById('password_expiry_days').value = data.data.password_expiry_days || 90;
            document.getElementById('min_password_length').value = data.data.min_password_length || 8;
            document.getElementById('require_special_char').checked = data.data.require_special_char == 1;
            document.getElementById('require_number').checked = data.data.require_number == 1;
            document.getElementById('require_uppercase').checked = data.data.require_uppercase == 1;
            document.getElementById('max_login_attempts').value = data.data.max_login_attempts || 5;
            document.getElementById('session_timeout_minutes').value = data.data.session_timeout_minutes || 30;
            document.getElementById('enable_2fa').checked = data.data.enable_2fa == 1;
            document.getElementById('enable_ip_restriction').checked = data.data.enable_ip_restriction == 1;
        }
    })
    .catch(function(error) { console.error('Error loading security settings:', error); });
}

function loadSystemSettings() {
    fetch('../api/get_system_settings.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.data) {
            document.getElementById('maintenance_mode').checked = data.data.maintenance_mode == 1;
            document.getElementById('debug_mode').checked = data.data.debug_mode == 1;
            document.getElementById('data_retention_days').value = data.data.data_retention_days || 365;
            document.getElementById('backup_schedule').value = data.data.backup_schedule || 'Daily';
            document.getElementById('timezone').value = data.data.timezone || 'Asia/Manila (GMT+8)';
            document.getElementById('date_format').value = data.data.date_format || 'MM/DD/YYYY';
            document.getElementById('time_format').value = data.data.time_format || '12-hour (AM/PM)';
        }
    })
    .catch(function(error) { console.error('Error loading system settings:', error); });
}

function loadAllSettings() {
    loadCompanySettings();
    loadPayrollSettings();
    loadNotificationSettings();
    loadSecuritySettings();
    loadSystemSettings();
}

function saveAll() {
    var companyErrors = validateCompanyInfo();
    if (companyErrors.length > 0) {
        alert('Please fix the following errors in Company tab:\n\n' + companyErrors.join('\n'));
        switchTab('company');
        return;
    }

    var payrollErrors = validatePayrollSettings();
    if (payrollErrors.length > 0) {
        alert('Please fix the following errors in Payroll tab:\n\n' + payrollErrors.join('\n'));
        switchTab('payroll');
        return;
    }

    var notificationErrors = validateNotificationSettings();
    if (notificationErrors.length > 0) {
        alert('Please fix the following errors in Notifications tab:\n\n' + notificationErrors.join('\n'));
        switchTab('notifications');
        return;
    }

    if (!confirm('Are you sure you want to save all settings?')) {
        return;
    }

    var companyForm = new FormData();
    companyForm.append('company_name', document.getElementById('company_name').value.trim());
    companyForm.append('tax_id', document.getElementById('tax_id').value.trim());
    companyForm.append('phone', document.getElementById('phone').value.trim());
    companyForm.append('email', document.getElementById('email').value.trim());
    companyForm.append('address', document.getElementById('address').value.trim());
    
    var logoInput = document.getElementById('company_logo');
    if (logoInput && logoInput.files && logoInput.files[0]) {
        companyForm.append('logo', logoInput.files[0]);
    }

    fetch('../api/update_company_settings.php', {
        method: 'POST',
        body: companyForm
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Company settings error: ' + data.message);
            return;
        }
        
        savePayrollSettings();
        saveSecuritySettings();
        saveSystemSettings();
        
        saveNotificationSettings().then(function(notifResult) {
            if (!notifResult || !notifResult.success) {
                console.error('Notification settings error');
            }
        });
        
        alert('Settings saved successfully!\n\nNotification settings have been updated.');
    })
    .catch(function(error) {
        console.error('Error saving company settings:', error);
        alert('Error saving company settings. Please try again.');
    });
}

function savePayrollSettings() {
    var payrollForm = new FormData();
    payrollForm.append('pay_periods', document.getElementById('pay_periods').value);
    payrollForm.append('sss_rate', document.getElementById('sss_rate').value);
    payrollForm.append('philhealth_rate', document.getElementById('philhealth_rate').value.replace('%', '') || '3');
    payrollForm.append('pagibig_rate', document.getElementById('pagibig_rate').value.replace('%', '') || '2');
    payrollForm.append('tax_table', document.getElementById('tax_table').value);
    payrollForm.append('allow_overtime', document.getElementById('allow_overtime').checked ? '1' : '0');
    payrollForm.append('allow_night_diff', document.getElementById('allow_night_diff').checked ? '1' : '0');
    payrollForm.append('overtime_rate', document.getElementById('overtime_rate').value || '1.25');
    payrollForm.append('night_diff_rate', document.getElementById('night_diff_rate').value || '1.1');

    fetch('../api/update_payroll_settings.php', {
        method: 'POST',
        body: payrollForm
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (!data.success) {
            console.error('Payroll settings error: ' + data.message);
        }
    })
    .catch(function(error) { console.error('Error saving payroll settings:', error); });
}

function validateNotificationSettings() {
    var emailNotifications = document.getElementById('email_notifications').checked;
    var inSystemNotifications = document.getElementById('in_system_notifications').checked;
    var emailDigestFrequency = document.getElementById('email_digest_frequency').value;
    
    var errors = [];
    
    // Check if at least one notification channel is enabled
    if (!emailNotifications && !inSystemNotifications) {
        errors.push('Please enable at least one notification channel (Email or In-System)');
    }
    
    // Validate email digest frequency if email is enabled
    if (emailNotifications) {
        var validFrequencies = ['Instant', 'Daily', 'Weekly'];
        if (!validFrequencies.includes(emailDigestFrequency)) {
            errors.push('Please select a valid Email Digest Frequency');
        }
    }
    
    return errors;
}

function saveNotificationSettings() {
    // Validate notification settings first
    var notificationErrors = validateNotificationSettings();
    if (notificationErrors.length > 0) {
        // Return a resolved Promise with the error object
        return Promise.resolve({ 
            success: false, 
            errors: notificationErrors,
            message: notificationErrors.join(', ')
        });
    }
    
    var notificationForm = new FormData();
    notificationForm.append('email_notifications', document.getElementById('email_notifications').checked ? '1' : '0');
    notificationForm.append('in_system_notifications', document.getElementById('in_system_notifications').checked ? '1' : '0');
    notificationForm.append('leave_request_updates', document.getElementById('leave_request_updates').checked ? '1' : '0');
    notificationForm.append('payroll_processing', document.getElementById('payroll_processing').checked ? '1' : '0');
    notificationForm.append('attendance_issues', document.getElementById('attendance_issues').checked ? '1' : '0');
    notificationForm.append('system_updates', document.getElementById('system_updates').checked ? '1' : '0');
    notificationForm.append('daily_reports', document.getElementById('daily_reports').checked ? '1' : '0');
    notificationForm.append('email_digest_frequency', document.getElementById('email_digest_frequency').value);

    return fetch('../api/update_notification_settings.php', {
        method: 'POST',
        body: notificationForm
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (!data.success) {
            console.error('Notification settings error: ' + data.message);
            return { success: false, message: data.message };
        }
        return { success: true, message: 'Notification settings updated successfully!' };
    })
    .catch(function(error) { 
        console.error('Error saving notification settings:', error); 
        return { success: false, message: 'Error saving notification settings. Please try again.' };
    });
}

function saveSecuritySettings() {
    var securityForm = new FormData();
    securityForm.append('password_expiry_days', document.getElementById('password_expiry_days').value || '90');
    securityForm.append('min_password_length', document.getElementById('min_password_length').value || '8');
    securityForm.append('require_special_char', document.getElementById('require_special_char').checked ? '1' : '0');
    securityForm.append('require_number', document.getElementById('require_number').checked ? '1' : '0');
    securityForm.append('require_uppercase', document.getElementById('require_uppercase').checked ? '1' : '0');
    securityForm.append('max_login_attempts', document.getElementById('max_login_attempts').value || '5');
    securityForm.append('session_timeout_minutes', document.getElementById('session_timeout_minutes').value || '30');
    securityForm.append('enable_2fa', document.getElementById('enable_2fa').checked ? '1' : '0');
    securityForm.append('enable_ip_restriction', document.getElementById('enable_ip_restriction').checked ? '1' : '0');

    fetch('../api/update_security_settings.php', {
        method: 'POST',
        body: securityForm
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (!data.success) {
            console.error('Security settings error: ' + data.message);
        }
    })
    .catch(function(error) { console.error('Error saving security settings:', error); });
}

function saveSystemSettings() {
    var systemForm = new FormData();
    systemForm.append('maintenance_mode', document.getElementById('maintenance_mode').checked ? '1' : '0');
    systemForm.append('debug_mode', document.getElementById('debug_mode').checked ? '1' : '0');
    systemForm.append('data_retention_days', document.getElementById('data_retention_days').value || '365');
    systemForm.append('backup_schedule', document.getElementById('backup_schedule').value);
    systemForm.append('timezone', document.getElementById('timezone').value);
    systemForm.append('date_format', document.getElementById('date_format').value);
    systemForm.append('time_format', document.getElementById('time_format').value);

    fetch('../api/update_system_settings.php', {
        method: 'POST',
        body: systemForm
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (!data.success) {
            console.error('System settings error: ' + data.message);
        }
    })
    .catch(function(error) { console.error('Error saving system settings:', error); });
}

function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    document.getElementById('newUserName').value = '';
    document.getElementById('newUserEmail').value = '';
    document.getElementById('newUserRole').value = 'Worker';
    document.getElementById('newUserPassword').value = '';
    document.getElementById('newUserPasswordConfirm').value = '';
    document.getElementById('newUserPassword').classList.remove('password-error', 'password-success');
    document.getElementById('newUserPasswordConfirm').classList.remove('password-error', 'password-success');
    document.getElementById('passwordError').classList.remove('show');
    document.getElementById('confirmPasswordError').classList.remove('show');
}

function togglePassword(fieldId, button) {
    var field = document.getElementById(fieldId);
    var icon = button.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function validatePasswords() {
    var password = document.getElementById('newUserPassword').value;
    var confirmPassword = document.getElementById('newUserPasswordConfirm').value;
    var passwordField = document.getElementById('newUserPassword');
    var confirmField = document.getElementById('newUserPasswordConfirm');
    var passwordError = document.getElementById('passwordError');
    var confirmError = document.getElementById('confirmPasswordError');

    passwordField.classList.remove('password-error', 'password-success');
    confirmField.classList.remove('password-error', 'password-success');
    passwordError.classList.remove('show');
    confirmError.classList.remove('show');

    if (password.length > 0) {
        if (password.length < 8) {
            passwordField.classList.add('password-error');
            passwordError.textContent = 'Password must be at least 8 characters long.';
            passwordError.classList.add('show');
        } else {
            passwordField.classList.add('password-success');
        }
    }

    if (confirmPassword.length > 0) {
        if (password !== confirmPassword) {
            confirmField.classList.add('password-error');
            confirmError.textContent = 'Passwords do not match.';
            confirmError.classList.add('show');
        } else if (password.length >= 8) {
            confirmField.classList.add('password-success');
        }
    }

    if (password.length > 0 && confirmPassword.length > 0 && password === confirmPassword && password.length >= 8) {
        passwordField.classList.remove('password-error');
        passwordField.classList.add('password-success');
        confirmField.classList.remove('password-error');
        confirmField.classList.add('password-success');
    }
}

function handleAddUser() {
    var name = document.getElementById('newUserName').value.trim();
    var email = document.getElementById('newUserEmail').value.trim();
    var role = document.getElementById('newUserRole').value;
    var pwd = document.getElementById('newUserPassword').value;
    var confirm = document.getElementById('newUserPasswordConfirm').value;

    if (!name || !email || !role || !pwd || !confirm) {
        alert('Please fill in all fields.');
        return;
    }

    if (pwd.length < 8) {
        alert('Password must be at least 8 characters long.');
        document.getElementById('newUserPassword').focus();
        return;
    }

    if (pwd !== confirm) {
        alert('Passwords do not match. Please check and try again.');
        document.getElementById('newUserPasswordConfirm').focus();
        validatePasswords();
        return;
    }

    var formData = new FormData();
    formData.append('full_name', name);
    formData.append('email', email);
    formData.append('role', role);
    formData.append('password', pwd);

    fetch('../api/add_user.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        alert(data.message);
        if (data.success) {
            loadUsers();
            closeAddUserModal();
        }
    })
    .catch(function(error) { console.error('Error adding user:', error); });
}

function loadUsers() {
    fetch('../api/get_users.php')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var tbody = document.querySelector('#users table tbody');
            tbody.innerHTML = '';
            data.data.forEach(function(user) {
                var tr = document.createElement('tr');
                var roleLabel = user.role === 'Worker' ? 'Worker' :
                                  user.role === 'Payroll Staff' ? 'Payroll' :
                                  user.role === 'Head Manager' ? 'Head' : 'Admin';
                var statusClass = user.status === 'Active' ? 'green' : 'red';
                var lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';

                tr.innerHTML = '<td><strong>' + user.full_name + '</strong><div class="muted">' + user.email + '</div></td>' +
                    '<td><span class="badge" style="background:#fef3c7;color:#b45309;">' + roleLabel + '</span></td>' +
                    '<td><span class="badge ' + statusClass + '">' + user.status + '</span></td>' +
                    '<td>' + lastLogin + '</td>' +
                    '<td class="actions">' +
                        '<button class="icon-btn" onclick="editUser(' + user.id + ')"><i class="fas fa-pen"></i></button>' +
                        '<button class="icon-btn" onclick="copyUser(' + user.id + ')"><i class="fas fa-copy"></i></button>' +
                        '<button class="icon-btn delete" onclick="deleteUser(' + user.id + ')"><i class="fas fa-trash"></i></button>' +
                    '</td>';
                tbody.appendChild(tr);
            });
        }
    })
    .catch(function(error) { console.error('Error loading users:', error); });
}

function editUser(userId) {
    alert('Edit user functionality not implemented yet');
}

function copyUser(userId) {
    alert('Copy user functionality not implemented yet');
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to deactivate this user?')) {
        var formData = new FormData();
        formData.append('user_id', userId);

        fetch('../api/delete_user.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            alert(data.message);
            if (data.success) {
                loadUsers();
            }
        })
        .catch(function(error) { console.error('Error deleting user:', error); });
    }
}

function openEditUserModal(userId, userName, userEmail, userRole, userStatus) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserName').value = userName;
    document.getElementById('editUserEmail').value = userEmail;
    document.getElementById('editUserRole').value = userRole;
    document.getElementById('editUserStatus').value = userStatus;
    document.getElementById('editUserModal').style.display = 'flex';
}

function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
    document.getElementById('editUserId').value = '';
    document.getElementById('editUserName').value = '';
    document.getElementById('editUserEmail').value = '';
    document.getElementById('editUserRole').value = 'Worker';
    document.getElementById('editUserStatus').value = 'Active';
}

function handleEditUser() {
    var userId = document.getElementById('editUserId').value;
    var name = document.getElementById('editUserName').value.trim();
    var email = document.getElementById('editUserEmail').value.trim();
    var role = document.getElementById('editUserRole').value;
    var status = document.getElementById('editUserStatus').value;

    if (!userId || !name || !email || !role || !status) {
        alert('Please fill in all required fields.');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    var formData = new FormData();
    formData.append('user_id', userId);
    formData.append('full_name', name);
    formData.append('email', email);
    formData.append('role', role);
    formData.append('status', status);

    fetch('../api/edit_user.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        alert(data.message);
        if (data.success) {
            loadUsers();
            closeEditUserModal();
        }
    })
    .catch(function(error) { console.error('Error updating user:', error); });
}

document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    var allowOvertime = document.getElementById('allow_overtime');
    var allowNightDiff = document.getElementById('allow_night_diff');
    if (allowOvertime) {
        allowOvertime.addEventListener('change', handleOvertimeToggle);
    }
    if (allowNightDiff) {
        allowNightDiff.addEventListener('change', handleNightDiffToggle);
    }
    
    loadAllSettings();
    loadUsers();
});

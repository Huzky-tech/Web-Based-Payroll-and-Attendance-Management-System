
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
            document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
            document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
        }

        function switchTab(tab) {
            document.querySelectorAll('.pill-tab').forEach(p => p.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.querySelectorAll('.panel').forEach(panel => {
                if (panel.id) panel.style.display = panel.id === tab ? 'block' : 'none';
            });
        }

        function saveAll() {
            // Save company settings
            const companyForm = new FormData();
            companyForm.append('company_name', document.querySelector('#company input[value*="Philippians"]').value || 'Philippians CDO Construction Company');
            companyForm.append('tax_id', document.querySelector('#company input[value*="123"]').value || '123-45-6789');
            companyForm.append('phone', document.querySelector('#company input[value*="555"]').value || '(555) 123-4567');
            companyForm.append('email', document.querySelector('#company input[type="email"]').value || 'info@philippianscdo.com');
            companyForm.append('address', document.querySelector('#company textarea').value || '123 Main Street, CDO City');

            fetch('../api/update_company_settings.php', {
                method: 'POST',
                body: companyForm
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Company settings error: ' + data.message);
                }
            })
            .catch(error => console.error('Error saving company settings:', error));

            // Save payroll settings
            const payrollForm = new FormData();
            payrollForm.append('pay_periods', document.querySelector('#payroll select').value);
            payrollForm.append('philhealth_rate', document.querySelector('#payroll input[value*="3"]').value || '3');
            payrollForm.append('pagibig_rate', document.querySelector('#payroll input[value*="2"]').value || '2');
            payrollForm.append('tax_table', document.querySelector('#payroll select:last-of-type').value);
            payrollForm.append('allow_overtime', document.querySelector('#payroll input[type="checkbox"]:checked').length > 0 ? '1' : '0');
            payrollForm.append('allow_night_diff', document.querySelector('#payroll input[type="checkbox"]:checked').length > 1 ? '1' : '0');
            payrollForm.append('overtime_rate', document.querySelector('#payroll input[value*="1.25"]').value || '1.25');
            payrollForm.append('night_diff_rate', document.querySelector('#payroll input[value*="1.1"]').value || '1.1');

            fetch('../api/update_payroll_settings.php', {
                method: 'POST',
                body: payrollForm
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Payroll settings error: ' + data.message);
                }
            })
            .catch(error => console.error('Error saving payroll settings:', error));

            // Save notification settings
            const notificationForm = new FormData();
            notificationForm.append('email_notifications', document.querySelector('#notifications input[type="checkbox"]:checked').length > 0 ? '1' : '0');
            notificationForm.append('in_system_notifications', document.querySelector('#notifications input[type="checkbox"]:checked').length > 1 ? '1' : '0');
            notificationForm.append('email_digest_frequency', document.querySelector('#notifications select').value);

            fetch('../api/update_notification_settings.php', {
                method: 'POST',
                body: notificationForm
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Notification settings error: ' + data.message);
                }
            })
            .catch(error => console.error('Error saving notification settings:', error));

            // Save security settings
            const securityForm = new FormData();
            securityForm.append('password_expiry_days', document.querySelector('#security input[type="number"]:first-of-type').value || '90');
            securityForm.append('min_password_length', document.querySelector('#security input[type="number"]:nth-of-type(2)').value || '8');
            securityForm.append('max_login_attempts', document.querySelector('#security input[type="number"]:nth-of-type(3)').value || '5');
            securityForm.append('session_timeout_minutes', document.querySelector('#security input[type="number"]:nth-of-type(4)').value || '30');

            fetch('../api/update_security_settings.php', {
                method: 'POST',
                body: securityForm
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Security settings error: ' + data.message);
                }
            })
            .catch(error => console.error('Error saving security settings:', error));

            // Save system settings
            const systemForm = new FormData();
            systemForm.append('maintenance_mode', document.querySelector('#system input[type="checkbox"]:checked').length > 0 ? '1' : '0');
            systemForm.append('debug_mode', document.querySelector('#system input[type="checkbox"]:checked').length > 1 ? '1' : '0');
            systemForm.append('data_retention_days', document.querySelector('#system input[type="number"]').value || '365');
            systemForm.append('backup_schedule', document.querySelector('#system select').value);
            systemForm.append('timezone', document.querySelector('#system select:nth-of-type(2)').value);
            systemForm.append('date_format', document.querySelector('#system select:nth-of-type(3)').value);
            systemForm.append('time_format', document.querySelector('#system select:nth-of-type(4)').value);

            fetch('../api/update_system_settings.php', {
                method: 'POST',
                body: systemForm
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('System settings error: ' + data.message);
                }
            })
            .catch(error => console.error('Error saving system settings:', error));

            alert('Settings saved successfully!');
        }

        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
            // Reset form and validation
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
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
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
            const password = document.getElementById('newUserPassword').value;
            const confirmPassword = document.getElementById('newUserPasswordConfirm').value;
            const passwordField = document.getElementById('newUserPassword');
            const confirmField = document.getElementById('newUserPasswordConfirm');
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmPasswordError');

            // Reset styles
            passwordField.classList.remove('password-error', 'password-success');
            confirmField.classList.remove('password-error', 'password-success');
            passwordError.classList.remove('show');
            confirmError.classList.remove('show');

            // Validate password field
            if (password.length > 0) {
                if (password.length < 8) {
                    passwordField.classList.add('password-error');
                    passwordError.textContent = 'Password must be at least 8 characters long.';
                    passwordError.classList.add('show');
                } else {
                    passwordField.classList.add('password-success');
                }
            }

            // Validate confirm password field
            if (confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    confirmField.classList.add('password-error');
                    confirmError.textContent = 'Passwords do not match.';
                    confirmError.classList.add('show');
                } else if (password.length >= 8) {
                    confirmField.classList.add('password-success');
                }
            }

            // If both fields have values and match, show success
            if (password.length > 0 && confirmPassword.length > 0 && password === confirmPassword && password.length >= 8) {
                passwordField.classList.remove('password-error');
                passwordField.classList.add('password-success');
                confirmField.classList.remove('password-error');
                confirmField.classList.add('password-success');
            }
        }

        function handleAddUser() {
            const name = document.getElementById('newUserName').value.trim();
            const email = document.getElementById('newUserEmail').value.trim();
            const role = document.getElementById('newUserRole').value;
            const pwd = document.getElementById('newUserPassword').value;
            const confirm = document.getElementById('newUserPasswordConfirm').value;

            if (!name || !email || !role || !pwd || !confirm) {
                alert('Please fill in all fields.');
                return;
            }

            // Validate password length
            if (pwd.length < 8) {
                alert('Password must be at least 8 characters long.');
                document.getElementById('newUserPassword').focus();
                return;
            }

            // Validate password match
            if (pwd !== confirm) {
                alert('Passwords do not match. Please check and try again.');
                document.getElementById('newUserPasswordConfirm').focus();
                validatePasswords(); // Show visual feedback
                return;
            }

            const formData = new FormData();
            formData.append('full_name', name);
            formData.append('email', email);
            formData.append('role', role);
            formData.append('password', pwd);

            fetch('../api/add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    loadUsers();
                    closeAddUserModal();
                }
            })
            .catch(error => console.error('Error adding user:', error));
        }

        function loadUsers() {
            fetch('../api/get_users.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.querySelector('#users table tbody');
                    tbody.innerHTML = '';
                    data.data.forEach(user => {
                        const tr = document.createElement('tr');
                        const roleLabel = user.role === 'Worker' ? 'Worker' :
                                          user.role === 'Payroll Staff' ? 'Payroll' :
                                          user.role === 'Head Manager' ? 'Head' : 'Admin';
                        const statusClass = user.status === 'Active' ? 'green' : 'red';
                        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';

                        tr.innerHTML = `
                            <td><strong>${user.full_name}</strong><div class="muted">${user.email}</div></td>
                            <td><span class="badge" style="background:#fef3c7;color:#b45309;">${roleLabel}</span></td>
                            <td><span class="badge ${statusClass}">${user.status}</span></td>
                            <td>${lastLogin}</td>
                            <td class="actions">
                                <button class="icon-btn" onclick="editUser(${user.id})"><i class="fas fa-pen"></i></button>
                                <button class="icon-btn" onclick="copyUser(${user.id})"><i class="fas fa-copy"></i></button>
                                <button class="icon-btn delete" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i></button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(error => console.error('Error loading users:', error));
        }

        function editUser(userId) {
            alert('Edit user functionality not implemented yet');
        }

        function copyUser(userId) {
            alert('Copy user functionality not implemented yet');
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);

                fetch('../api/delete_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        loadUsers();
                    }
                })
                .catch(error => console.error('Error deleting user:', error));
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateDateTime();
            setInterval(updateDateTime, 60000);
            loadUsers(); // Load users on page load
        });
 
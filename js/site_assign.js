
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
            alert('Save Changes placeholder');
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

            const tbody = document.querySelector('#users table tbody');
            const tr = document.createElement('tr');
            const roleLabel = role === 'Worker' ? 'Worker' :
                              role === 'Payroll Staff' ? 'Payroll' :
                              role === 'Head Manager' ? 'Head' : 'Admin';

            tr.innerHTML = `
                <td><strong>${name}</strong><div class="muted">${email}</div></td>
                <td><span class="badge" style="background:#fef3c7;color:#b45309;">${roleLabel}</span></td>
                <td><span class="badge green">Active</span></td>
                <td>Just now</td>
                <td class="actions">
                    <button class="icon-btn"><i class="fas fa-pen"></i></button>
                    <button class="icon-btn"><i class="fas fa-copy"></i></button>
                    <button class="icon-btn delete"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);

            // reset fields and close modal
            document.getElementById('newUserName').value = '';
            document.getElementById('newUserEmail').value = '';
            document.getElementById('newUserRole').value = 'Worker';
            document.getElementById('newUserPassword').value = '';
            document.getElementById('newUserPasswordConfirm').value = '';

            closeAddUserModal();
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateDateTime();
            setInterval(updateDateTime, 60000);
        });
  
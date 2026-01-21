<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: #1f2937;
        }
        /* Main */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-header {
            background-color: #ffffff; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); position: sticky; top: 0; z-index: 10;
        }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .date-time { text-align: right; line-height: 1.4; }
        .date-time .date { font-size: 14px; color: #6b7280; }
        .date-time .time { font-size: 15px; color: #d97706; font-weight: 700; }
        .user-profile {
            display: flex; align-items: center; gap: 10px; padding: 6px 10px; border-radius: 12px;
            cursor: pointer; transition: background-color 0.2s ease;
        }
        .user-profile:hover { background-color: #f3f4f6; }
        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%; background-color: #e5e7eb;
            display: flex; align-items: center; justify-content: center; color: #4b5563; font-size: 16px;
        }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-size: 14px; font-weight: 700; }
        .user-role { font-size: 12px; color: #6b7280; }
        .btn-action {
            background-color: #d97706; color: #ffffff; border: none; padding: 10px 16px; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s ease;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-action:hover { background-color: #b45309; }
        /* Content */
        .content-area { padding: 24px 28px 32px; flex: 1; }
        .page-title { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 16px; }
        .section-title { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        .section-sub { font-size: 14px; color: #6b7280; margin-bottom: 18px; }
        /* Tabs */
        .pill-tabs { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
        .pill-tab {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px;
            border: 1px solid #e5e7eb; cursor: pointer; background: #fff; color: #374151; font-weight: 600;
        }
        .pill-tab.active { border-color: #d97706; color: #d97706; }
        /* Panels */
        .panel {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            padding: 20px; margin-bottom: 20px;
        }
        .form-grid {
            display: grid; grid-template-columns: repeat(2, minmax(260px, 1fr)); gap: 14px 18px;
        }
        label { font-size: 13px; font-weight: 600; color: #4b5563; }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"], select, textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;
            background: #fff;
        }
        .password-field-wrapper input[type="password"],
        .password-field-wrapper input[type="text"] {
            padding-right: 40px;
        }
        textarea { min-height: 80px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #d97706; }
        .password-field-wrapper input.password-error:focus { border-color: #ef4444; }
        .password-field-wrapper input.password-success:focus { border-color: #22c55e; }
        .full-row { grid-column: 1 / -1; }
        .muted { font-size: 12px; color: #6b7280; margin-top: 6px; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge.green { background: #e8f5e9; color: #22c55e; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; font-size: 13px; color: #4b5563; }
        th { background: #f9fafb; font-weight: 700; color: #6b7280; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .actions { display: inline-flex; gap: 10px; }
        .icon-btn { background: none; border: none; cursor: pointer; color: #d97706; font-size: 14px; }
        .icon-btn.delete { color: #ef4444; }
        .pill-switch { display: flex; flex-direction: column; gap: 10px; }
        .footer-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .btn-secondary {
            background: #fff; border: 1px solid #e5e7eb; padding: 12px 16px; border-radius: 8px; cursor: pointer;
            font-weight: 600; color: #374151; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-secondary:hover { background: #f9fafb; }
        .btn-danger {
            background: #dc2626; color: #fff; border: none; padding: 12px 16px; border-radius: 8px; cursor: pointer;
            font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-danger:hover { background: #b91c1c; }
        /* Add User Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.35);
            display: none; align-items: center; justify-content: center;
            z-index: 40;
        }
        .modal {
            background: #fff; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            width: 420px; max-width: 90%; padding: 22px 24px 20px; position: relative;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;
        }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-close {
            background: none; border: none; cursor: pointer; font-size: 16px; color: #6b7280;
        }
        .modal-body { display: flex; flex-direction: column; gap: 12px; }
        .modal-footer {
            display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;
        }
        .btn-light {
            background: #e5e7eb; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer;
            font-size: 14px; font-weight: 500; color: #374151;
        }
        .btn-light:hover { background:#d1d5db; }
        /* Password validation styles */
        .password-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2;
        }
        .password-success {
            border-color: #22c55e !important;
        }
        .error-message {
            font-size: 12px; color: #ef4444; margin-top: 4px; display: none;
        }
        .error-message.show {
            display: block;
        }
        .password-field-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #6b7280; font-size: 14px;
        }
        .password-toggle:hover { color: #374151; }
        @media (max-width: 900px) {
            .form-grid { grid-template-columns: 1fr; }
        }

    </style>
</head>
<body>


    <div class="main-content">
        <div class="top-header">
            <div class="page-title">Settings</div>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:14 PM</div>
                </div>
                <button class="btn-action" onclick="saveAll()"><i class="fas fa-save"></i>Save Changes</button>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Admin</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="section-title">System Settings</div>

            <div class="pill-tabs">
                <button class="pill-tab active" data-tab="company" onclick="switchTab('company')"><i class="fas fa-building"></i>Company</button>
                <button class="pill-tab" data-tab="payroll" onclick="switchTab('payroll')"><i class="fas fa-money-bill"></i>Payroll</button>
                <button class="pill-tab" data-tab="users" onclick="switchTab('users')"><i class="fas fa-users"></i>Users</button>
                <button class="pill-tab" data-tab="notifications" onclick="switchTab('notifications')"><i class="fas fa-bell"></i>Notifications</button>
                <button class="pill-tab" data-tab="security" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i>Security</button>
                <button class="pill-tab" data-tab="system" onclick="switchTab('system')"><i class="fas fa-server"></i>System</button>
            </div>

            <!-- Company -->
            <div id="company" class="panel">
                <div class="section-title" style="font-size:18px;">Company Information</div>
                <div class="section-sub">Update your company details and contact information.</div>
                <div class="form-grid">
                    <div class="full-row">
                        <label>Company Name</label>
                        <input type="text" value="Philippians CDO Construction Company">
                    </div>
                    <div>
                        <label>Tax ID Number</label>
                        <input type="text" value="123-45-6789">
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" value="(555) 123-4567">
                    </div>
                    <div class="full-row">
                        <label>Email Address</label>
                        <input type="email" value="info@philippianscdo.com">
                    </div>
                    <div class="full-row">
                        <label>Address</label>
                        <textarea>123 Main Street, CDO City</textarea>
                    </div>
                </div>
                <div style="margin-top:18px;">
                    <label>Company Logo</label>
                    <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
                        <div style="width:70px; height:70px; border:1px dashed #d1d5db; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#6b7280;">
                            <i class="fas fa-image"></i>
                        </div>
                        <button class="btn-secondary" onclick="alert('Upload placeholder')"><i class="fas fa-upload"></i>Upload Logo</button>
                    </div>
                    <div class="muted">Recommended: 200x200px, PNG or JPG format</div>
                </div>
            </div>

            <!-- Payroll -->
            <div id="payroll" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Payroll Settings</div>
                <div class="section-sub">Configure your payroll calculation parameters and deduction rates.</div>
                <div class="form-grid">
                    <div>
                        <label>Pay Periods</label>
                        <select>
                            <option>Semi-monthly (1-15, 16-end)</option>
                        </select>
                    </div>
                    <div>
                        <label>SSS Rate</label>
                        <select>
                            <option>Standard</option>
                        </select>
                    </div>
                    <div>
                        <label>PhilHealth Rate</label>
                        <input type="text" value="3%">
                    </div>
                    <div>
                        <label>Pag-IBIG Rate</label>
                        <input type="text" value="2%">
                    </div>
                    <div>
                        <label>Tax Table</label>
                        <select>
                            <option>Latest BIR Tax Table</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px; margin-bottom:8px;">Overtime & Special Rates</div>
                    <div class="pill-switch">
                        <label class="checkbox-row"><input type="checkbox" checked>Allow Overtime</label>
                        <label class="checkbox-row"><input type="checkbox" checked>Allow Night Differential</label>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Overtime Rate</label>
                            <input type="text" value="1.25x">
                        </div>
                        <div>
                            <label>Night Differential Rate</label>
                            <input type="text" value="1.1x">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users -->
            <div id="users" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">User Management</div>
                <div class="section-sub">Manage system users and their access permissions.</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Worker User</strong><div class="muted">worker@example.com</div></td>
                                <td><span class="badge" style="background:#fef3c7;color:#b45309;">Worker</span></td>
                                <td><span class="badge green">Active</span></td>
                                <td>2023-07-05 10:30 AM</td>
                                <td class="actions">
                                    <button class="icon-btn"><i class="fas fa-pen"></i></button>
                                    <button class="icon-btn"><i class="fas fa-copy"></i></button>
                                    <button class="icon-btn delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payroll Staff</strong><div class="muted">payroll@example.com</div></td>
                                <td><span class="badge" style="background:#fef3c7;color:#b45309;">Payroll</span></td>
                                <td><span class="badge green">Active</span></td>
                                <td>2023-07-04 09:15 AM</td>
                                <td class="actions">
                                    <button class="icon-btn"><i class="fas fa-pen"></i></button>
                                    <button class="icon-btn"><i class="fas fa-copy"></i></button>
                                    <button class="icon-btn delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Head Manager</strong><div class="muted">head@example.com</div></td>
                                <td><span class="badge" style="background:#fef3c7;color:#b45309;">Head</span></td>
                                <td><span class="badge green">Active</span></td>
                                <td>2023-07-05 08:45 AM</td>
                                <td class="actions">
                                    <button class="icon-btn"><i class="fas fa-pen"></i></button>
                                    <button class="icon-btn"><i class="fas fa-copy"></i></button>
                                    <button class="icon-btn delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Admin User</strong><div class="muted">admin@example.com</div></td>
                                <td><span class="badge" style="background:#fef3c7;color:#b45309;">Admin</span></td>
                                <td><span class="badge green">Active</span></td>
                                <td>2023-07-05 11:20 AM</td>
                                <td class="actions">
                                    <button class="icon-btn"><i class="fas fa-pen"></i></button>
                                    <button class="icon-btn"><i class="fas fa-copy"></i></button>
                                    <button class="icon-btn delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:14px;">
                    <button class="btn-action" onclick="openAddUserModal()"><i class="fas fa-plus"></i>Add User</button>
                </div>
            </div>

            <!-- Notifications -->
            <div id="notifications" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Notification Settings</div>
                <div class="section-sub">Configure system notifications and alerts.</div>
                <div class="section-title" style="font-size:16px;">Notification Channels</div>
                <div class="pill-switch">
                    <label class="checkbox-row"><input type="checkbox" checked>Email Notifications</label>
                    <label class="checkbox-row"><input type="checkbox" checked>In-System Notifications</label>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px;">Notification Types</div>
                    <div class="pill-switch">
                        <label class="checkbox-row"><input type="checkbox" checked>Leave Request Updates</label>
                        <label class="checkbox-row"><input type="checkbox" checked>Payroll Processing</label>
                        <label class="checkbox-row"><input type="checkbox">Attendance Issues</label>
                        <label class="checkbox-row"><input type="checkbox">System Updates</label>
                        <label class="checkbox-row"><input type="checkbox">Daily Reports</label>
                    </div>
                </div>
                <div style="margin-top:16px;" class="form-grid">
                    <div class="full-row">
                        <label>Email Digest Frequency</label>
                        <select>
                            <option>Daily</option>
                            <option>Weekly</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div id="security" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Security Settings</div>
                <div class="section-sub">Configure password policies and security settings.</div>
                <div class="form-grid">
                    <div>
                        <label>Password Expiry (days)</label>
                        <input type="number" value="90">
                    </div>
                    <div>
                        <label>Minimum Password Length</label>
                        <input type="number" value="8">
                    </div>
                </div>
                <div class="pill-switch" style="margin-top:14px;">
                    <label class="checkbox-row"><input type="checkbox" checked>Require Special Character</label>
                    <label class="checkbox-row"><input type="checkbox" checked>Require Number</label>
                    <label class="checkbox-row"><input type="checkbox" checked>Require Uppercase Letter</label>
                </div>
                <div class="form-grid" style="margin-top:16px;">
                    <div>
                        <label>Maximum Login Attempts</label>
                        <input type="number" value="5">
                    </div>
                    <div>
                        <label>Session Timeout (minutes)</label>
                        <input type="number" value="30">
                    </div>
                </div>
                <div class="pill-switch" style="margin-top:14px;">
                    <label class="checkbox-row"><input type="checkbox">Enable Two-Factor Authentication</label>
                    <label class="checkbox-row"><input type="checkbox">Enable IP Restriction</label>
                </div>
                <div class="footer-actions">
                    <button class="btn-secondary" onclick="alert('Force Password Reset placeholder')"><i class="fas fa-key"></i>Force Password Reset</button>
                    <button class="btn-danger" onclick="alert('Terminate Sessions placeholder')"><i class="fas fa-sign-out-alt"></i>Terminate All Sessions</button>
                </div>
            </div>

            <!-- System -->
            <div id="system" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">System Settings</div>
                <div class="section-sub">Configure system-wide settings and maintenance options.</div>

                <div class="section-title" style="font-size:16px;">System Status</div>
                <div class="pill-switch">
                    <label class="checkbox-row"><input type="checkbox">Maintenance Mode</label>
                    <label class="checkbox-row"><input type="checkbox">Debug Mode</label>
                </div>

                <div class="form-grid" style="margin-top:16px;">
                    <div>
                        <label>Data Retention Period (days)</label>
                        <input type="number" value="365">
                    </div>
                    <div>
                        <label>Backup Schedule</label>
                        <select>
                            <option>Daily</option>
                            <option>Weekly</option>
                        </select>
                    </div>
                </div>
                <div class="muted" style="margin-top:8px;">Last Backup: 2023-07-05 03:00 AM</div>
                <div class="footer-actions" style="margin-top:12px;">
                    <button class="btn-secondary" onclick="alert('Run Backup placeholder')"><i class="fas fa-database"></i>Run Backup Now</button>
                </div>

                <div class="section-title" style="font-size:16px; margin-top:18px;">System Preferences</div>
                <div class="form-grid">
                    <div>
                        <label>Timezone</label>
                        <select>
                            <option>Asia/Manila (GMT+8)</option>
                        </select>
                    </div>
                    <div>
                        <label>Date Format</label>
                        <select>
                            <option>MM/DD/YYYY</option>
                        </select>
                    </div>
                    <div class="full-row">
                        <label>Time Format</label>
                        <select>
                            <option>12-hour (AM/PM)</option>
                            <option>24-hour</option>
                        </select>
                    </div>
                </div>

                <div class="section-title" style="font-size:16px; margin-top:18px;">System Information</div>
                <div class="panel" style="padding:12px; margin-bottom:0;">
                    <div class="form-grid">
                        <div>
                            <label>System Version</label>
                            <div class="muted" style="margin-top:4px;">1.0.5</div>
                        </div>
                        <div>
                            <label>Last Update</label>
                            <div class="muted" style="margin-top:4px;">2023-07-01</div>
                        </div>
                        <div>
                            <label>Server Environment</label>
                            <div class="muted" style="margin-top:4px;">Production</div>
                        </div>
                        <div>
                            <label>Database Size</label>
                            <div class="muted" style="margin-top:4px;">125 MB</div>
                        </div>
                    </div>
                </div>

                <div class="footer-actions" style="margin-top:14px;">
                    <button class="btn-secondary" onclick="alert('Backup System placeholder')"><i class="fas fa-database"></i>Backup System</button>
                    <button class="btn-secondary" onclick="alert('Clear Cache placeholder')"><i class="fas fa-broom"></i>Clear Cache</button>
                    <button class="btn-danger" onclick="alert('Reset Settings placeholder')"><i class="fas fa-undo"></i>Reset Settings</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Add New User</div>
                <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div>
                    <label>Full Name</label>
                    <input type="text" id="newUserName" placeholder="Enter full name">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" id="newUserEmail" placeholder="user@example.com">
                </div>
                <div>
                    <label>Role</label>
                    <select id="newUserRole">
                        <option value="Worker">Worker</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="Head Manager">Head Manager</option>
                        <option value="Admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label>Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="newUserPassword" placeholder="Enter password" oninput="validatePasswords()">
                        <button type="button" class="password-toggle" onclick="togglePassword('newUserPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                </div>
                <div>
                    <label>Confirm Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="newUserPasswordConfirm" placeholder="Confirm password" oninput="validatePasswords()">
                        <button type="button" class="password-toggle" onclick="togglePassword('newUserPasswordConfirm', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-light" onclick="closeAddUserModal()">Cancel</button>
                <button class="btn-action" onclick="handleAddUser()">Add User</button>
            </div>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>

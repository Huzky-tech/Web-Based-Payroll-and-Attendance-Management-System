<?php
include '../api/connection/db_config.php';

// Load initial company settings
$company_stmt = $conn->prepare("SELECT * FROM company_settings WHERE id = 1");
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_data = $company_result->fetch_assoc();

// Load initial users
$users_stmt = $conn->prepare("SELECT id, full_name, email, status, last_login FROM users ORDER BY id");
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users_data = $users_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/setting.css">
      <script src="../js/setting.js" defer></script>
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
                        <input type="text" id="company_name" value="Philippians CDO Construction Company">
                    </div>
                    <div>
                        <label>Tax ID Number</label>
                        <input type="text" id="tax_id" value="123-45-6789">
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" id="phone" value="(555) 123-4567">
                    </div>
                    <div class="full-row">
                        <label>Email Address</label>
                        <input type="email" id="email" value="info@philippianscdo.com">
                    </div>
                    <div class="full-row">
                        <label>Address</label>
                        <textarea id="address">123 Main Street, CDO City</textarea>
                    </div>
                </div>
                <div style="margin-top:18px;">
                    <label>Company Logo</label>
                    <div style="margin-top:8px; display:flex; align-items:center; gap:12px;">
                        <div id="logo_preview" style="width:70px; height:70px; border:1px dashed #d1d5db; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#6b7280;">
                            <i class="fas fa-image"></i>
                        </div>
                        <input type="file" id="company_logo" accept="image/jpeg,image/png,image/svg+xml" style="display:none" onchange="handleLogoPreview(this)">
                        <button class="btn-secondary" onclick="document.getElementById('company_logo').click()"><i class="fas fa-upload"></i>Upload Logo</button>
                    </div>
                    <div class="muted">Recommended: 200x200px, PNG or JPG format (Max 2MB)</div>
                </div>
            </div>

            <!-- Payroll -->
            <div id="payroll" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Payroll Settings</div>
                <div class="section-sub">Configure your payroll calculation parameters and deduction rates.</div>
                <div class="form-grid">
                    <div>
                        <label>Pay Periods</label>
                        <select id="pay_periods">
                            <option value="Semi-monthly (1-15, 16-end)">Semi-monthly (1-15, 16-end)</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                    <div>
                        <label>SSS Rate</label>
                        <select id="sss_rate">
                            <option value="Standard">Standard</option>
                        </select>
                    </div>
                    <div>
                        <label>PhilHealth Rate (%)</label>
                        <input type="text" id="philhealth_rate" value="3%">
                    </div>
                    <div>
                        <label>Pag-IBIG Rate (%)</label>
                        <input type="text" id="pagibig_rate" value="2%">
                    </div>
                    <div>
                        <label>Tax Table</label>
                        <select id="tax_table">
                            <option value="Latest BIR Tax Table">Latest BIR Tax Table</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px; margin-bottom:8px;">Overtime & Special Rates</div>
                    <div class="pill-switch">
                        <label class="checkbox-row"><input type="checkbox" id="allow_overtime" checked onchange="handleOvertimeToggle()">Allow Overtime</label>
                        <label class="checkbox-row"><input type="checkbox" id="allow_night_diff" checked onchange="handleNightDiffToggle()">Allow Night Differential</label>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Overtime Rate (multiplier)</label>
                            <input type="text" id="overtime_rate" value="1.25">
                        </div>
                        <div>
                            <label>Night Differential Rate (multiplier)</label>
                            <input type="text" id="night_diff_rate" value="1.1">
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
                        <tbody id="usersTableBody">
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
                    <label class="checkbox-row"><input type="checkbox" id="email_notifications" checked>Email Notifications</label>
                    <label class="checkbox-row"><input type="checkbox" id="in_system_notifications" checked>In-System Notifications</label>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px;">Notification Types</div>
                    <div class="pill-switch">
                        <label class="checkbox-row"><input type="checkbox" id="leave_request_updates" checked>Leave Request Updates</label>
                        <label class="checkbox-row"><input type="checkbox" id="payroll_processing" checked>Payroll Processing</label>
                        <label class="checkbox-row"><input type="checkbox" id="attendance_issues">Attendance Issues</label>
                        <label class="checkbox-row"><input type="checkbox" id="system_updates">System Updates</label>
                        <label class="checkbox-row"><input type="checkbox" id="daily_reports">Daily Reports</label>
                    </div>
                </div>
                <div style="margin-top:16px;" class="form-grid">
                    <div class="full-row">
<label>Email Digest Frequency</label>
                        <select id="email_digest_frequency">
                            <option value="Instant">Instant</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
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
                        <input type="number" id="password_expiry_days" value="90">
                    </div>
                    <div>
                        <label>Minimum Password Length</label>
                        <input type="number" id="min_password_length" value="8">
                    </div>
                </div>
                <div class="pill-switch" style="margin-top:14px;">
                    <label class="checkbox-row"><input type="checkbox" id="require_special_char" checked>Require Special Character</label>
                    <label class="checkbox-row"><input type="checkbox" id="require_number" checked>Require Number</label>
                    <label class="checkbox-row"><input type="checkbox" id="require_uppercase" checked>Require Uppercase Letter</label>
                </div>
                <div class="form-grid" style="margin-top:16px;">
                    <div>
                        <label>Maximum Login Attempts</label>
                        <input type="number" id="max_login_attempts" value="5">
                    </div>
                    <div>
                        <label>Session Timeout (minutes)</label>
                        <input type="number" id="session_timeout_minutes" value="30">
                    </div>
                </div>
                <div class="pill-switch" style="margin-top:14px;">
                    <label class="checkbox-row"><input type="checkbox" id="enable_2fa">Enable Two-Factor Authentication</label>
                    <label class="checkbox-row"><input type="checkbox" id="enable_ip_restriction">Enable IP Restriction</label>
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
                    <label class="checkbox-row"><input type="checkbox" id="maintenance_mode">Maintenance Mode</label>
                    <label class="checkbox-row"><input type="checkbox" id="debug_mode">Debug Mode</label>
                </div>

                <div class="form-grid" style="margin-top:16px;">
                    <div>
                        <label>Data Retention Period (days)</label>
                        <input type="number" id="data_retention_days" value="365">
                    </div>
                    <div>
                        <label>Backup Schedule</label>
                        <select id="backup_schedule">
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
                        <select id="timezone">
                            <option>Asia/Manila (GMT+8)</option>
                        </select>
                    </div>
                    <div>
                        <label>Date Format</label>
                        <select id="date_format">
                            <option>MM/DD/YYYY</option>
                        </select>
                    </div>
                    <div class="full-row">
                        <label>Time Format</label>
                        <select id="time_format">
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

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Edit User</div>
                <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUserId">
                <div>
                    <label>Full Name</label>
                    <input type="text" id="editUserName" placeholder="Enter full name">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" id="editUserEmail" placeholder="user@example.com">
                </div>
                <div>
                    <label>Role</label>
                    <select id="editUserRole">
                        <option value="Worker">Worker</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="Head Manager">Head Manager</option>
                        <option value="Admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select id="editUserStatus">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-light" onclick="closeEditUserModal()">Cancel</button>
                <button class="btn-action" onclick="handleEditUser()">Save Changes</button>
            </div>
        </div>
    </div>
</body>
</html>

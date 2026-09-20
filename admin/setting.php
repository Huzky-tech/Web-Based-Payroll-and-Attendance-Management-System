<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/login_security.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;

// Ensure the users table has the account lock columns for unlock feature
login_security_ensure_columns($conn);

// Both roles share this page; destructive user actions remain Admin-only.
$canAccessUsers = true;
$canAddDeleteUsers = ($currentRole === 'Admin');
$canEditUsers = true;

$profileData = null;
ensure_user_profile_photo_column($conn);
if (!empty($_SESSION['user_id'])) {
    $profileStmt = $conn->prepare("SELECT id, full_name, email, status, profile_photo FROM users WHERE id = ? LIMIT 1");
    if ($profileStmt) {
        $userId = (int) $_SESSION['user_id'];
        $profileStmt->bind_param('i', $userId);
        $profileStmt->execute();
        $profileResult = $profileStmt->get_result();
        $profileData = $profileResult ? $profileResult->fetch_assoc() : null;
        $profileStmt->close();
    }
}

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
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/setting.css?v=20260916-payroll-validation-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/user_email_validation.js?v=20260920-availability-3" defer></script>
<script src="../js/setting.js?v=20260921-admin-1" defer></script>
</head>
<body>
<?php endif; ?>


    <div class="main-content">
        <div class="content-area">
            <div class="section-title">System Settings</div>

            <div class="pill-tabs">
                <button class="pill-tab active" data-tab="profile" onclick="switchTab('profile')"><i class="fas fa-user-circle"></i>Profile</button>
                <button class="pill-tab" data-tab="company" onclick="switchTab('company')"><i class="fas fa-building"></i>Company</button>
                <button class="pill-tab" data-tab="payroll" onclick="switchTab('payroll')"><i class="fas fa-money-bill"></i>Payroll</button>
                <button class="pill-tab" data-tab="notifications" onclick="switchTab('notifications')"><i class="fas fa-bell"></i>Notifications</button>
                <button class="pill-tab" data-tab="security" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i>Security</button>
                <button class="pill-tab" data-tab="system" onclick="switchTab('system')"><i class="fas fa-server"></i>System</button>
            </div>

            <script>
                window.currentUserRole = <?php echo json_encode($currentRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.canAddDeleteUsers = <?php echo json_encode($canAddDeleteUsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.canEditUsers = <?php echo json_encode($canEditUsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            </script>

            <div id="profile" class="panel">
                <div class="section-title" style="font-size:18px;"><?php echo htmlspecialchars($currentRole); ?> Profile</div>
                <div class="section-sub">Update the account information displayed in the dashboard header.</div>
                <div class="form-grid">
                    <div class="full-row">
                        <label>Profile Photo</label>
                        <div style="display:flex;align-items:center;gap:16px;margin-top:8px;">
                            <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:#eef1f5;display:grid;place-items:center;color:#64748b;font-size:25px;">
                                <img id="profile_photo_preview" src="<?php echo htmlspecialchars(user_profile_photo_url($profileData['profile_photo'] ?? '')); ?>" alt="Profile preview" style="<?php echo empty($profileData['profile_photo']) ? 'display:none;' : ''; ?>width:100%;height:100%;object-fit:cover;">
                                <i id="profile_photo_placeholder" class="fas fa-user" style="<?php echo empty($profileData['profile_photo']) ? '' : 'display:none;'; ?>"></i>
                            </div>
                            <div>
                                <input type="file" id="profile_photo" accept="image/jpeg,image/png,image/webp" onchange="previewProfilePhoto(this)">
                                <div class="muted">JPG, PNG, or WebP. Maximum 5 MB.</div>
                            </div>
                        </div>
                    </div>
                    <div class="full-row">
                        <label>Full Name</label>
                        <input type="text" id="profile_full_name" value="<?php echo htmlspecialchars($profileData['full_name'] ?? ''); ?>" autocomplete="name">
                    </div>
                    <div class="full-row">
                        <label>Email Address</label>
                        <input type="email" id="profile_email" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>" autocomplete="email">
                    </div>
                    <div>
                        <label>Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($currentRole); ?>" disabled>
                    </div>
                    <div>
                        <label>Status</label>
                        <input type="text" value="<?php echo htmlspecialchars($profileData['status'] ?? 'Active'); ?>" disabled>
                    </div>
                </div>
                <div class="footer-actions">
                    <button class="btn-action" type="button" onclick="saveMyProfile()"><i class="fas fa-save"></i>Save Profile</button>
                </div>
            </div>

         <!-- Company -->
            <div id="company" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Company Information</div>
                <div class="section-sub">Update your company details and contact information.</div>
                <div class="form-grid">
                    <div class="full-row">
                        <label>Company Name</label>
                        <input type="text" id="company_name" value="Philippians CDO Construction Company">
                    </div>
                    <div>
                        <label>Tax ID Number</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="tax_id" value="123-45-6789" autocomplete="off">
                            <button type="button" class="password-toggle" onclick="togglePassword('tax_id', this)" title="Show/hide Tax ID">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="muted">Hidden for security. Saved and masked in the system.</div>
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="tel" id="phone" value="(555) 123-4567" placeholder="e.g., (555) 123-4567">
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
                <div class="footer-actions" style="margin-top:18px;">
                    <button class="btn-action" type="button" id="btnSaveCompany" onclick="saveActiveTab()"><i class="fas fa-save"></i>Save Company Settings</button>
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
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Overtime Rate (multiplier)</label>
                            <input type="text" id="overtime_rate" value="1.25">
                        </div>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px; margin-bottom:8px;">Late Deductions</div>
                    <div class="section-sub">Set fixed deduction amount for late attendance.</div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Late Worker Deduction (PHP)</label>
                            <input type="number" id="late_worker_deduction" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
                </div>

                <div class="footer-actions">
                    <button class="btn-action" type="button" onclick="saveActiveTab()"><i class="fas fa-save"></i>Save Payroll Settings</button>
                </div>
            </div>

            <!-- Notifications -->
            <div id="notifications" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Notification Settings</div>
                <div class="section-sub">Choose how Admin receives alerts about staff submissions and operational changes. Login activity stays in Audit Logs.</div>
                <div class="section-title" style="font-size:16px;">Notification Channels</div>
                <div class="pill-switch">
                    <label class="checkbox-row"><input type="checkbox" id="email_notifications" checked>Email Notifications</label>
                    <label class="checkbox-row"><input type="checkbox" id="in_system_notifications" checked>In-System Notifications</label>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px;">Notification Types</div>
                    <div class="pill-switch">
                        <label class="checkbox-row"><input type="checkbox" id="leave_request_updates">Employee Additions & Updates</label>
                        <label class="checkbox-row"><input type="checkbox" id="payroll_processing">Payroll Submissions & Approvals</label>
                        <label class="checkbox-row"><input type="checkbox" id="attendance_issues">Attendance Adjustments</label>
                        <label class="checkbox-row"><input type="checkbox" id="system_updates">Site & Worker Assignments</label>
                        <label class="checkbox-row"><input type="checkbox" id="daily_reports">Overtime & Staff Reports</label>
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
                <div class="footer-actions">
                    <button class="btn-action" type="button" onclick="saveActiveTab()"><i class="fas fa-save"></i>Save Notification Settings</button>
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
                <div class="footer-actions">
                    <button class="btn-action" type="button" onclick="saveActiveTab()"><i class="fas fa-save"></i>Save Security Settings</button>
                </div>
            </div>

            <!-- System -->
            <div id="system" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">System Settings</div>
                <div class="section-sub">Configure system-wide settings and maintenance options.</div>

                <div class="section-title" style="font-size:16px;">System Status</div>
                <div class="pill-switch">
                    <label class="checkbox-row"><input type="checkbox" id="maintenance_mode">Maintenance Mode</label>
                 </div>

                <div class="form-grid" style="margin-top:16px;">
                    <div>
                        <label>Data Retention Period</label>
                        <div style="display:grid;grid-template-columns:minmax(0,1fr) 150px;gap:10px;">
                            <input type="number" id="data_retention_value" value="1" min="1" step="1" aria-label="Retention duration">
                            <select id="data_retention_unit" aria-label="Retention unit">
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                                <option value="years" selected>Years</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label>Backup Schedule</label>
                        <select id="backup_schedule">
                            <option>Daily</option>
                            <option>Weekly</option>
                        </select>
                    </div>
                </div>
                <div class="muted" style="margin-top:8px;">Last Backup: <span id="last_backup_at">Loading…</span></div>
                <div class="footer-actions" style="margin-top:12px;">
                    <button class="btn-secondary" type="button" onclick="runBackup()"><i class="fas fa-database"></i>Run Backup Now</button>
                </div>

                <div id="sampleDataDevTools" class="dev-tools-panel" style="display:none; margin-top:18px;">
                    <div class="section-title" style="font-size:16px;">Development Tools</div>
                    <div class="section-sub">Generate realistic demo data for capstone presentations and testing. Existing records are never overwritten.</div>
                    <div class="dev-tools-warning">
                        <i class="fas fa-flask"></i>
                        For development and testing only. Creates 3 sites, 30 workers, 7 days of attendance, payroll, overtime, and timekeeper reports.
                    </div>
                    <div class="footer-actions" style="margin-top:12px;">
                        <button class="btn-secondary" type="button" id="generateSampleDataBtn" onclick="generateSampleData()">
                            <i class="fas fa-seedling"></i> Generate Sample Data
                        </button>
                    </div>
                </div>

                <div class="section-title" style="font-size:16px; margin-top:18px;">System Information</div>
                <div class="panel" style="padding:12px; margin-bottom:0;">
                    <div class="form-grid">
                        <div>
                            <label>System Version</label>
                            <div class="muted" id="system_version" style="margin-top:4px;">Loading…</div>
                        </div>
                        <div>
                            <label>Last Update</label>
                            <div class="muted" id="last_update" style="margin-top:4px;">Loading…</div>
                        </div>
                        <div>
                            <label>Server Environment</label>
                            <div class="muted" id="server_environment" style="margin-top:4px;">Loading…</div>
                        </div>
                        <div>
                            <label>Database Size</label>
                            <div class="muted" id="database_size" style="margin-top:4px;">Loading…</div>
                        </div>
                    </div>
                </div>

                <div class="footer-actions" style="margin-top:14px;">
                    <button class="btn-action" type="button" onclick="saveActiveTab()"><i class="fas fa-save"></i>Save System Settings</button>
                    <button class="btn-secondary" type="button" onclick="runBackup()"><i class="fas fa-database"></i>Backup System</button>
                    <button class="btn-secondary" type="button" onclick="clearSystemCache()"><i class="fas fa-broom"></i>Clear Cache</button>
                    <button class="btn-danger" type="button" onclick="resetAllSettings()"><i class="fas fa-undo"></i>Reset Settings</button>
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
                <div class="modal-description">
                    <i class="fas fa-info-circle"></i>
                    Fill in the details below to create a new system user. The user will receive their login credentials upon creation.
                </div>
                <div class="name-fields-row">
                    <div>
                        <label>First Name</label>
                        <input type="text" id="newUserFirstName" data-no-live-validation placeholder="Enter first name" autocomplete="given-name" oninput="validateUserIdentityFields('new')">
                        <div class="field-validation-message" id="newUserFirstNameError"></div>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" id="newUserLastName" data-no-live-validation placeholder="Enter last name" autocomplete="family-name" oninput="validateUserIdentityFields('new')">
                        <div class="field-validation-message" id="newUserLastNameError"></div>
                    </div>
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" id="newUserEmail" data-no-live-validation placeholder="user@example.com" autocomplete="email" oninput="validateUserIdentityFields('new')">
                    <div class="field-validation-message" id="newUserEmailError"></div>
                </div>
                <div>
                    <label>Role</label>
                    <select id="newUserRole">
                        <option value="Admin">Admin</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="HR">HR</option>
                        <option value="Timekeeper">Timekeeper</option>
                        <option value="Assistant Admin">Assistant Admin</option>
                        <option value="Worker">Worker</option>
                    </select>
                </div>
                <div class="modal-description">
                    <i class="fas fa-envelope-circle-check"></i>
                    A secure temporary password will be generated and emailed to the user. They must change it during their first login.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-light" onclick="closeAddUserModal()">Cancel</button>
                <button class="btn-action" id="addUserSubmitBtn" onclick="handleAddUser()">Add User</button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal" aria-hidden="true">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Edit User</div>
                <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUserId">
                <div class="name-fields-row">
                    <div>
                        <label>First Name</label>
                        <input type="text" id="editUserFirstName" data-no-live-validation placeholder="Enter first name" autocomplete="given-name" oninput="validateUserIdentityFields('edit')">
                        <div class="field-validation-message" id="editUserFirstNameError"></div>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" id="editUserLastName" data-no-live-validation placeholder="Enter last name" autocomplete="family-name" oninput="validateUserIdentityFields('edit')">
                        <div class="field-validation-message" id="editUserLastNameError"></div>
                    </div>
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" id="editUserEmail" data-no-live-validation placeholder="user@example.com" autocomplete="email" oninput="validateUserIdentityFields('edit')">
                    <div class="field-validation-message" id="editUserEmailError"></div>
                </div>
                <div>
                    <label>Role</label>
                    <select id="editUserRole">
                        <option value="Admin">Admin</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="HR">HR</option>
                        <option value="Timekeeper">Timekeeper</option>
                        <option value="Assistant Admin">Assistant Admin</option>
                        <option value="Worker">Worker</option>
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
                <button class="btn-action" id="editUserSubmitBtn" onclick="handleEditUser()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Reset User Password Modal -->
    <div class="modal-overlay" id="resetUserPasswordModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Change User Password</div>
                <button class="modal-close" onclick="closeResetUserPasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resetPasswordUserId">
                <div>
                    <label>User</label>
                    <input type="text" id="resetPasswordUserName" readonly>
                </div>
                <div>
                    <label>New Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="resetUserPassword" placeholder="Enter new password" oninput="validateResetUserPassword()">
                        <button type="button" class="password-toggle" onclick="togglePassword('resetUserPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="resetUserPasswordError"></div>
                </div>
                <div>
                    <label>Confirm New Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" id="resetUserPasswordConfirm" placeholder="Confirm new password" oninput="validateResetUserPassword()">
                        <button type="button" class="password-toggle" onclick="togglePassword('resetUserPasswordConfirm', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="resetUserPasswordConfirmError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-light" onclick="closeResetUserPasswordModal()">Cancel</button>
                <button class="btn-action" onclick="handleResetUserPassword()">Update Password</button>
            </div>
        </div>
    </div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

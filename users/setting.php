<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$embeddedDashboard = $embeddedDashboard ?? false;
$userManagementPage = $userManagementPage ?? false;
$isPayrollStaff = ($currentRole === 'Payroll Staff');
$isHr = ($currentRole === 'HR');
$isAssistantAdmin = ($currentRole === 'Assistant Admin');
$isAccountOnlySettings = ($isPayrollStaff || $isHr);

// Determine if user can access Users tab
$canAccessUsers = ($currentRole === 'Admin');
$canAddDeleteUsers = ($currentRole === 'Admin');
$canEditUsers = ($currentRole === 'Admin' || $currentRole === 'Assistant Admin');

$profileData = null;
if (($isAccountOnlySettings || $isAssistantAdmin) && !empty($_SESSION['user_id'])) {
    ensure_user_profile_photo_column($conn);
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

if (!$isAccountOnlySettings) {
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
}
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/setting.css?v=20260906-3">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/setting.js?v=20260921-admin-1" defer></script>
</head>
<body>
<?php endif; ?>


    <div class="main-content">
        <div class="content-area">
            <div class="section-title"><?php echo $userManagementPage ? 'Employee' : ($isAccountOnlySettings ? 'My Settings' : 'System Settings'); ?></div>

            <?php if (!$userManagementPage): ?>
            <div class="pill-tabs">
                <?php if ($isAccountOnlySettings): ?>
                <button class="pill-tab active" data-tab="profile" onclick="switchTab('profile')"><i class="fas fa-user"></i>Profile</button>
                <button class="pill-tab" data-tab="password" onclick="switchTab('password')"><i class="fas fa-lock"></i>Password</button>
                <?php else: ?>
                <?php if ($isAssistantAdmin): ?>
                <button class="pill-tab active" data-tab="profile" onclick="switchTab('profile')"><i class="fas fa-user-circle"></i>Profile</button>
                <button class="pill-tab" data-tab="system" onclick="switchTab('system')"><i class="fas fa-server"></i>System</button>
                <?php else: ?>
                <button class="pill-tab active" data-tab="company" onclick="switchTab('company')"><i class="fas fa-building"></i>Company</button>
                <button class="pill-tab" data-tab="payroll" onclick="switchTab('payroll')"><i class="fas fa-money-bill"></i>Payroll</button>
                <button class="pill-tab" data-tab="notifications" onclick="switchTab('notifications')"><i class="fas fa-bell"></i>Notifications</button>
                <button class="pill-tab" data-tab="security" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i>Security</button>
                <button class="pill-tab" data-tab="system" onclick="switchTab('system')"><i class="fas fa-server"></i>System</button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <script>
                window.currentUserRole = <?php echo json_encode($currentRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.canAddDeleteUsers = <?php echo json_encode($canAddDeleteUsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.canEditUsers = <?php echo json_encode($canEditUsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.isPayrollStaffSettings = <?php echo json_encode($isAccountOnlySettings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.isUserManagementPage = <?php echo json_encode($userManagementPage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            </script>

            <?php if ($userManagementPage): ?>
            <div id="users" class="panel">
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
                        <tbody id="usersTableBody"></tbody>
                    </table>
                </div>
                <?php if ($canAddDeleteUsers): ?>
                <div style="margin-top:14px;">
                    <button class="btn-action" onclick="openAddUserModal()"><i class="fas fa-plus"></i>Add User</button>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($isAccountOnlySettings): ?>
            <div id="profile" class="panel">
                <div class="section-title" style="font-size:18px;">Profile Information</div>
                <div class="section-sub">Update the account details and profile photo shown on your dashboard.</div>
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
                        <input type="text" id="profile_full_name" value="<?php echo htmlspecialchars($profileData['full_name'] ?? ''); ?>">
                    </div>
                    <div class="full-row">
                        <label>Email Address</label>
                        <input type="email" id="profile_email" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>">
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

            <div id="password" class="panel" style="display:none;">
                <div class="section-title" style="font-size:18px;">Change Password</div>
                <div class="section-sub">Use your current password to set a new one for this account.</div>
                <div class="form-grid">
                    <div class="full-row">
                        <label>Current Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="current_password" placeholder="Enter current password">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label>New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="new_password" placeholder="Enter new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label>Confirm New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="confirm_new_password" placeholder="Confirm new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="muted">Password must be at least 8 characters long.</div>
                <div class="footer-actions">
                    <button class="btn-action" type="button" onclick="changeMyPassword()"><i class="fas fa-key"></i>Update Password</button>
                </div>
            </div>
            <?php else: ?>
            <?php if ($isAssistantAdmin): ?>
            <div id="profile" class="panel">
                <div class="section-title" style="font-size:18px;">Assistant Admin Profile</div>
                <div class="section-sub">Update your dashboard name, email, and profile photo.</div>
                <div class="form-grid">
                    <div class="full-row">
                        <label>Profile Photo</label>
                        <div style="display:flex;align-items:center;gap:16px;margin-top:8px;">
                            <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:#eef1f5;display:grid;place-items:center;color:#64748b;font-size:25px;">
                                <img id="profile_photo_preview" src="<?php echo htmlspecialchars(user_profile_photo_url($profileData['profile_photo'] ?? '')); ?>" alt="Profile preview" style="<?php echo empty($profileData['profile_photo']) ? 'display:none;' : ''; ?>width:100%;height:100%;object-fit:cover;">
                                <i id="profile_photo_placeholder" class="fas fa-user" style="<?php echo empty($profileData['profile_photo']) ? '' : 'display:none;'; ?>"></i>
                            </div>
                            <div><input type="file" id="profile_photo" accept="image/jpeg,image/png,image/webp" onchange="previewProfilePhoto(this)"><div class="muted">JPG, PNG, or WebP. Maximum 5 MB.</div></div>
                        </div>
                    </div>
                    <div class="full-row"><label>Full Name</label><input type="text" id="profile_full_name" value="<?php echo htmlspecialchars($profileData['full_name'] ?? ''); ?>"></div>
                    <div class="full-row"><label>Email Address</label><input type="email" id="profile_email" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>"></div>
                    <div><label>Role</label><input type="text" value="Assistant Admin" disabled></div>
                    <div><label>Status</label><input type="text" value="<?php echo htmlspecialchars($profileData['status'] ?? 'Active'); ?>" disabled></div>
                </div>
                <div class="footer-actions"><button class="btn-action" type="button" onclick="saveMyProfile()"><i class="fas fa-save"></i>Save Profile</button></div>
            </div>
            <?php endif; ?>
            <?php if (!$isAssistantAdmin): ?>
            <div class="footer-actions" style="margin-bottom: 18px;">
                <button class="btn-action" type="button" onclick="saveAll()"><i class="fas fa-save"></i>Save All Settings</button>
                <button class="btn-secondary" type="button" onclick="loadAllSettings()"><i class="fas fa-rotate-right"></i>Reload Settings</button>
            </div>

            <!-- Company -->
            <div id="company" class="panel"<?php echo $isAssistantAdmin ? ' style="display:none;"' : ''; ?>>
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
                        <input type="tel" id="phone" value="(555) 123-4567">
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
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Overtime Rate (multiplier)</label>
                            <input type="text" id="overtime_rate" value="1.25">
                        </div>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <div class="section-title" style="font-size:16px; margin-bottom:8px;">Late & Position Deductions</div>
                    <div class="section-sub">Set fixed deduction amounts for late attendance and default deductions by worker position.</div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Late Worker Deduction (PHP)</label>
                            <input type="number" id="late_worker_deduction" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div>
                            <label>Construction Worker Deduction (PHP)</label>
                            <input type="number" class="position-deduction-input" data-position="Construction Worker" min="0" step="0.01" value="0.00">
                        </div>
                        <div>
                            <label>Laborer Deduction (PHP)</label>
                            <input type="number" class="position-deduction-input" data-position="Laborer" min="0" step="0.01" value="0.00">
                        </div>
                        <div>
                            <label>Carpenter Deduction (PHP)</label>
                            <input type="number" class="position-deduction-input" data-position="Carpenter" min="0" step="0.01" value="0.00">
                        </div>
                        <div>
                            <label>Electrician Deduction (PHP)</label>
                            <input type="number" class="position-deduction-input" data-position="Electrician" min="0" step="0.01" value="0.00">
                        </div>
                        <div>
                            <label>Plumber Deduction (PHP)</label>
                            <input type="number" class="position-deduction-input" data-position="Plumber" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
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
            </div>
            <?php endif; ?>

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
                        <label>Data Retention Period (years)</label>
                        <input type="number" id="data_retention_days" value="1" min="1" step="1">
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
                    <?php if (!$isAssistantAdmin): ?><button class="btn-danger" type="button" onclick="resetAllSettings()"><i class="fas fa-undo"></i>Reset Settings</button><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
                    <input type="email" id="newUserEmail" data-no-live-validation placeholder="user@example.com">
                </div>
                <div>
                    <label>Role</label>
                    <select id="newUserRole">
                        <option value="Admin">Admin</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="Timekeeper">Timekeeper</option>
                        <option value="Assistant Admin">Assistant Admin</option>
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
                    <input type="email" id="editUserEmail" data-no-live-validation placeholder="user@example.com">
                </div>
                <div>
                    <label>Role</label>
                    <select id="editUserRole">
                        <option value="Admin">Admin</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="Timekeeper">Timekeeper</option>
                        <option value="Assistant Admin">Assistant Admin</option>
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

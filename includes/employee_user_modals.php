<script src="../js/user_email_validation.js?v=20260920-availability-3" defer></script>
<div class="modal-overlay" id="addUserModal" role="dialog" aria-modal="true" aria-label="Add New User"><div class="modal">
<div class="modal-header"><div class="modal-title">Add New User</div><button class="modal-close" onclick="closeAddUserModal()">&times;</button></div>
<div class="modal-body">
<div class="modal-description"><i class="fas fa-info-circle"></i> Fill in the details below to create a new system user.</div>
<div class="name-fields-row">
<div><label>First Name</label><input type="text" id="newUserFirstName" data-no-live-validation placeholder="First name" maxlength="50" autocomplete="given-name" oninput="handleUserNameInput(event,'new')"><div class="field-validation-message" id="newUserFirstNameError" aria-live="assertive"></div></div>
<div><label>Last Name</label><input type="text" id="newUserLastName" data-no-live-validation placeholder="Last name" maxlength="50" autocomplete="family-name" oninput="handleUserNameInput(event,'new')"><div class="field-validation-message" id="newUserLastNameError" aria-live="assertive"></div></div>
</div>
<div><label>Email Address</label><input type="email" id="newUserEmail" data-no-live-validation placeholder="user@example.com" autocomplete="email" oninput="validateUserIdentityFields('new')"><div class="field-validation-message" id="newUserEmailError" aria-live="polite"></div></div>
<div><label>Role</label><select id="newUserRole"><option>Admin</option><option>Payroll Staff</option><option>HR</option><option>Timekeeper</option><option>Assistant Admin</option><option>Worker</option></select></div>
<div class="modal-description"><i class="fas fa-envelope-circle-check"></i> A secure temporary password will be generated and emailed to the user. They must change it during their first login.</div>
</div><div class="modal-footer"><button class="btn-light" onclick="closeAddUserModal()">Cancel</button><button class="btn-action" id="addUserSubmitBtn" onclick="handleAddUser()">Add User</button></div>
</div></div>

<div class="modal-overlay" id="editUserModal" aria-hidden="true"><div class="modal">
<div class="modal-header"><div class="modal-title">Edit User</div><button class="modal-close" onclick="closeEditUserModal()">&times;</button></div>
<div class="modal-body"><input type="hidden" id="editUserId">
<div class="name-fields-row">
<div><label>First Name</label><input type="text" id="editUserFirstName" data-no-live-validation placeholder="First name" maxlength="50" oninput="handleUserNameInput(event,'edit')"><div class="field-validation-message" id="editUserFirstNameError" aria-live="assertive"></div></div>
<div><label>Last Name</label><input type="text" id="editUserLastName" data-no-live-validation placeholder="Last name" maxlength="50" oninput="handleUserNameInput(event,'edit')"><div class="field-validation-message" id="editUserLastNameError" aria-live="assertive"></div></div>
</div>
<div><label>Email Address</label><input type="email" id="editUserEmail" data-no-live-validation placeholder="user@example.com" oninput="validateUserIdentityFields('edit')"><div class="field-validation-message" id="editUserEmailError" aria-live="polite"></div></div>
<div><label>Role</label><select id="editUserRole"><option>Admin</option><option>Payroll Staff</option><option>HR</option><option>Timekeeper</option><option>Assistant Admin</option><option>Worker</option></select></div>
<div><label>Status</label><select id="editUserStatus"><option>Active</option><option>Inactive</option></select></div>
</div><div class="modal-footer"><button class="btn-light" onclick="closeEditUserModal()">Cancel</button><button class="btn-action" id="editUserSubmitBtn" onclick="handleEditUser()">Save Changes</button></div>
</div></div>

<div class="modal-overlay" id="resetUserPasswordModal"><div class="modal">
<div class="modal-header"><div class="modal-title">Change User Password</div><button class="modal-close" onclick="closeResetUserPasswordModal()">&times;</button></div>
<div class="modal-body"><input type="hidden" id="resetPasswordUserId"><div><label>User</label><input type="text" id="resetPasswordUserName" readonly></div><div><label>New Password</label><div class="password-field-wrapper"><input type="password" id="resetUserPassword" placeholder="Enter new password" oninput="validateResetUserPassword()"><button type="button" class="password-toggle" onclick="togglePassword('resetUserPassword',this)"><i class="fas fa-eye"></i></button></div><div class="error-message" id="resetUserPasswordError"></div></div><div><label>Confirm New Password</label><div class="password-field-wrapper"><input type="password" id="resetUserPasswordConfirm" placeholder="Confirm new password" oninput="validateResetUserPassword()"><button type="button" class="password-toggle" onclick="togglePassword('resetUserPasswordConfirm',this)"><i class="fas fa-eye"></i></button></div><div class="error-message" id="resetUserPasswordConfirmError"></div></div></div>
<div class="modal-footer"><button class="btn-light" onclick="closeResetUserPasswordModal()">Cancel</button><button class="btn-action" onclick="handleResetUserPassword()">Update Password</button></div>
</div></div>

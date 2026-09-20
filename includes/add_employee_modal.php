<?php
// Shared Add Employee modal markup used by Admin / Assistant Admin / Payroll Staff.
// This file exists to guarantee the modal form UI is identical across roles.
?>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="modal" data-employee-modal-role="<?php echo htmlspecialchars((string) ($currentRole ?? ($_SESSION['role'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
    <div
        class="modal-content"
        style="
            width: 760px !important;
            flex: 0 1 760px !important;
            min-width: 0 !important;
            max-width: calc(100vw - 40px) !important;
            height: auto !important;
            max-height: 85vh !important;
            overflow: hidden !important;
            margin: auto !important;
            box-sizing: border-box !important;
        "
    >
        <div class="modal-header">
            <h2>Add New Employee</h2>
            <button type="button" class="close-modal" aria-label="Close" onclick="closeModal(); return false;">&times;</button>
        </div>
        <div
            class="modal-body"
            style="
                height: auto !important;
                max-height: calc(85vh - 80px) !important;
                overflow-y: auto !important;
            "
        >
            <form id="addEmployeeForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employeePosition">Position</label>
                        <select id="employeePosition" name="position" required>
                            <option value="" selected disabled>Select position</option>
                            <option value="Construction Worker" data-hourly-rate="125" data-salary-rate="22000">Construction Worker</option>
                            <option value="Laborer" data-hourly-rate="110" data-salary-rate="19000">Laborer</option>
                            <option value="Carpenter" data-hourly-rate="150" data-salary-rate="26000">Carpenter</option>
                            <option value="Mason" data-hourly-rate="150" data-salary-rate="26000">Mason</option>
                            <option value="Electrician" data-hourly-rate="175" data-salary-rate="30000">Electrician</option>
                            <option value="Plumber" data-hourly-rate="170" data-salary-rate="29000">Plumber</option>
                            <option value="Welder" data-hourly-rate="165" data-salary-rate="28000">Welder</option>
                            <option value="Painter" data-hourly-rate="140" data-salary-rate="24000">Painter</option>
                            <option value="Heavy Equipment Operator" data-hourly-rate="190" data-salary-rate="33000">Heavy Equipment Operator</option>
                            <option value="Site Foreman" data-hourly-rate="220" data-salary-rate="38000">Site Foreman</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rate Type</label>
                        <select name="rate_type" required>
                            <option value="Hourly" selected>Hourly</option>
                            <option value="Salary">Salary</option>
                        </select>
                    </div>
                </div>

                <div class="profile-section government-deduction-section">
                    <div class="profile-section-head">
                        <h4>Government Deductions</h4>
                        <p>Set whether statutory deductions apply to this worker during payroll processing.</p>
                    </div>
                    <div class="profile-section-body">
                        <div class="government-deduction-options">
                            <label class="government-deduction-option">
                                <input type="radio" name="government_deduction_status" value="With Deductions" checked required>
                                <span>Subject to Government Deductions</span>
                            </label>
                            <label class="government-deduction-option">
                                <input type="radio" name="government_deduction_status" value="No Deductions">
                                <span>No Government Deductions</span>
                            </label>
                        </div>

                        <div class="government-deduction-types" style="margin-top:10px;">
                            <label style="display:block; font-weight:600; margin-bottom:6px;">Choose deductions (if subject)</label>

                            <div class="government-deduction-type-options" style="display:flex; flex-wrap:wrap; gap:14px;">
                                <label class="government-deduction-type-option">
                                    <input type="checkbox" name="government_deduction_types[]" value="sss" checked>
                                    <span>SSS</span>
                                </label>
                                <label class="government-deduction-type-option">
                                    <input type="checkbox" name="government_deduction_types[]" value="philhealth" checked>
                                    <span>PhilHealth</span>
                                </label>
                                <label class="government-deduction-type-option">
                                    <input type="checkbox" name="government_deduction_types[]" value="pagibig" checked>
                                    <span>Pag-IBIG</span>
                                </label>
                            </div>

                            <div class="muted" style="margin-top:6px; font-size:12px; color:#6b7280;">
                                If multiple are selected, all will be deducted.
                            </div>
                        </div>
                    </div>
                </div>


                <div class="form-row">
                    <div class="form-group">
                        <label>Join Date</label>
                        <input type="date" name="join_date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employeeSalary">Pay Rate (PHP)</label>
                        <input type="number" id="employeeSalary" name="salary" step="0.01" min="0.01" placeholder="Select a position or enter an amount" required>
                        <small class="salary-suggestion-hint">A suggested amount is filled automatically and can be edited.</small>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                    </div>
                </div>

                <div class="profile-section" style="margin-top:14px;">
                    <div class="profile-section-head">
                        <h4>Personal Information</h4>
                        <p>View personal details, address, and emergency contact information.</p>
                    </div>
                    <div class="profile-section-body profile-grid-2">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Worker login email" autocomplete="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="Phone number must be exactly 11 digits" required>
                        </div>


                        <div class="form-group">
                            <label>Street Address</label>
                            <input type="text" name="street_address" required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" required>
                        </div>

                        <div class="form-group">
                            <label>State / Province</label>
                            <input type="text" name="state_province" required>
                        </div>
                        <div class="form-group">
                            <label>Postal / ZIP Code</label>
                            <input type="text" name="postal_code" required>
                        </div>

                        <div class="form-group">
                            <label>Country</label>
                            <select name="country" required>
                                <option value="">Select country</option>
                                <option value="Philippines" selected>Philippines</option>
                                <option value="Australia">Australia</option>
                                <option value="Canada">Canada</option>
                                <option value="Japan">Japan</option>
                                <option value="Singapore">Singapore</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="United States">United States</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact - Contact Name</label>
                            <input type="text" name="emergency_contact_name" required>
                        </div>

                        <div class="form-group">
                            <label>Emergency Contact - Contact Phone</label>
                            <input type="tel" name="emergency_contact_phone" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="Emergency contact phone must be exactly 11 digits" required>
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact - Relationship</label>
                            <select name="emergency_contact_relationship" required>
                                <option value="" disabled hidden selected>Select relationship</option>
                                <option value="Mother">Mother</option>
                                <option value="Father">Father</option>
                                <option value="Parent">Parent</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Child">Child</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Relative">Relative</option>
                                <option value="Friend">Friend</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="profile-section" style="margin-top:14px;">
                    <div class="profile-section-head"><h4>Worker Login Account</h4><p>An account will be created automatically. The first-time password is <strong>password</strong>; the worker must replace it after signing in.</p></div>
                </div>

                <div class="form-group">
                    <label>Employee Photo (2×2 inches, JPG or PNG)</label>
                    <input type="file" name="photo" id="employeePhotoInput" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    <p class="photo-upload-hint">Square or portrait photos work best. Non-square images are center-cropped to 2×2 inches.</p>
                </div>

                <div class="form-group image-preview-group">
                    <div class="image-preview-label">2×2 Photo Preview</div>
                    <div class="image-preview-box" id="employeePhotoPreview">
                        <span>No image selected</span>
                    </div>
                    <p class="photo-preview-note" id="employeePhotoPreviewNote" hidden></p>
                </div>

                <button type="submit" class="btn-submit">Add Employee</button>
            </form>
        </div>

    </div>
</div>

<!-- Archive Employee Confirmation Modal -->
<div id="archiveEmployeeModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveEmployeeTitle" aria-hidden="true">
    <div class="modal-content archive-employee-dialog">
        <div class="archive-employee-icon" aria-hidden="true"><i class="fas fa-box-archive"></i></div>
        <h2 id="archiveEmployeeTitle">Archive Employee?</h2>
        <p>Are you sure you want to archive this employee?</p>
        <p class="archive-employee-note">You can restore the employee later from the Archive page.</p>
        <div class="archive-employee-actions">
            <button type="button" class="archive-employee-cancel" id="cancelArchiveEmployee">Cancel</button>
            <button type="button" class="archive-employee-confirm" id="confirmArchiveEmployee">
                <i class="fas fa-box-archive"></i> Archive Employee
            </button>
        </div>
    </div>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="../css/employee.css">
     <script src="../js/employee.js" defer></script>

     </head>
     <body>
    <div class="main-content">      
        <div class="employee-content">
            <div class="section-header">
                <h2 class="section-title">Employee Management</h2>
                        </div>

            <div class="section-actions">
                <div class="tabs">
                    <button class="tab active"><i class="fas fa-user"></i><span>All Employees</span></button>
                    <button class="tab"><i class="fas fa-map-marker-alt"></i><span>Site Assignments</span></button>
                </div>
                <button class="btn-add" id="openAddModalBtn">
                    <i class="fas fa-plus"></i><span>Add New Employee</span>
                </button>
            </div>

            <div class="tab-content active" id="employeesTab">
                <div class="search-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search employees...">
                    </div>
                    <div class="filter-dropdown">
                        <i class="fas fa-building"></i>
                        <select><option>All Sites</option></select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="employee-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Site</th>
                                <th>Status</th>
                                <th>Salary</th>
                                <th>Join Date</th>
                                <th>Approval</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar-small">J</div>
                                        <div class="employee-details">
                                            <div class="employee-name">John Doe</div>
                                            <div class="employee-id">ID: 1</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Construction Worker</td>
                                <td>Main Street Project</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td class="salary">₱15,000</td>
                                <td>2022-05-15</td>
                                <td>
                                    <div class="approval-info">
                                        <span class="approval-badge">Approved</span>
                                        <span class="approval-by">By: HR Manager</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view btn-view-employee" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar-small" style="background-color: #F3E5F5; color: #9C27B0;">J</div>
                                        <div class="employee-details">
                                            <div class="employee-name">Jane Smith</div>
                                            <div class="employee-id">ID: 2</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Electrician</td>
                                <td>Downtown Office Complex</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td class="salary">₱18,000</td>
                                <td>2021-11-03</td>
                                <td>
                                    <div class="approval-info">
                                        <span class="approval-badge">Approved</span>
                                        <span class="approval-by">By: HR Manager</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view btn-view-employee" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="addEmployeeModal" class="modal">
        <div class="modal-content-sm">
            <div class="modal-header-simple">
                <h2>Add New Employee</h2>
                <button class="modal-close-btn" id="closeAddModalBtn">&times;</button>
            </div>
            <div class="modal-body-simple">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" class="form-control" placeholder="">
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" class="form-control" placeholder="">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Join Date</label>
                    <input type="date" class="form-control" value="2026-01-18">
                </div>
                <div class="form-group">
                    <label>Base Rate</label>
                    <input type="text" class="form-control" placeholder="Enter base rate">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="cancelAddBtn">Cancel</button>
                <button class="btn-primary">Add Employee</button>
            </div>
        </div>
    </div>

    <div id="employeeModal" class="modal">
        <div class="modal-content-lg">
            
            <div class="modal-header-top">
                <button class="modal-close-btn" id="closeViewModalBtn">&times;</button>
                <div class="profile-hero">
                    <div class="hero-avatar">
                        <i class="far fa-user"></i>
                    </div>
                    <div class="hero-info">
                        <h2>John Doe</h2>
                        <div class="hero-meta">
                            <span><i class="fas fa-briefcase"></i> Construction Worker</span>
                            <span class="divider-vertical"></span>
                            <span class="status-badge active">Active</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-nav">
                <div class="modal-nav-item active" data-target="profileDetails">Profile Details</div>
                <div class="modal-nav-item" data-target="attendanceRecords">Attendance Records</div>
                <div class="modal-nav-item" data-target="employmentHistory">Employment History</div>
            </div>
            
            <div class="modal-body-scroll">
                
                <div id="profileDetails" class="modal-tab-content active">
                    <div class="content-section-header">
                        <div class="content-section-title">
                            <h3>Personal Information</h3>
                            <p>View personal details, address, and emergency contact information.</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-secondary"><i class="fas fa-edit"></i> Edit Profile</button>
                            <button class="btn-primary"><i class="fas fa-qrcode"></i> Download QR</button>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-card-header">
                            <h4>Contact Details</h4>
                            <button class="btn-action edit" title="Edit Contact Details"><i class="fas fa-pen"></i></button>
                        </div>
                        <div class="grid-2">
                            <div class="field-group">
                                <label>Full Name</label>
                                <div class="field-value">John Doe</div>
                            </div>
                            <div class="field-group">
                                <label>Email Address</label>
                                <div class="field-value">john.doe@example.com</div>
                            </div>
                            <div class="field-group">
                                <label>Phone Number</label>
                                <div class="field-value">(555) 123-4567</div>
                            </div>
                            <div class="field-group">
                                <label>Date of Birth</label>
                                <div class="field-value" style="color: #9ca3af;">Not provided</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <h4>Address</h4>
                            <button class="btn-action edit" title="Edit Address"><i class="fas fa-pen"></i></button>
                        </div>
                        <div class="grid-2">
                            <div class="field-group full-width">
                                <label>Street Address</label>
                                <div class="field-value">123 Construction Way</div>
                            </div>
                            <div class="field-group">
                                <label>City</label>
                                <div class="field-value">Builder City</div>
                            </div>
                            <div class="field-group">
                                <label>State / Province</label>
                                <div class="field-value">CA</div>
                            </div>
                            <div class="field-group">
                                <label>Postal / ZIP Code</label>
                                <div class="field-value">90210</div>
                            </div>
                            <div class="field-group">
                                <label>Country</label>
                                <div class="field-value">USA</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <h4>Emergency Contact</h4>
                            <button class="btn-action edit" title="Edit Emergency Contact"><i class="fas fa-pen"></i></button>
                        </div>
                        <div class="grid-2">
                            <div class="field-group">
                                <label>Contact Name</label>
                                <div class="field-value" style="color: #9ca3af;">Not provided</div>
                            </div>
                            <div class="field-group">
                                <label>Contact Phone</label>
                                <div class="field-value" style="color: #9ca3af;">Not provided</div>
                            </div>
                            <div class="field-group full-width">
                                <label>Relationship</label>
                                <div class="field-value" style="color: #9ca3af;">Not provided</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <h4>System Information</h4>
                            <button class="btn-action edit" title="Edit System Info"><i class="fas fa-pen"></i></button>
                        </div>
                        <div class="grid-2">
                            <div class="field-group">
                                <label>Role</label>
                                <div class="field-value">Construction Worker</div>
                            </div>
                            <div class="field-group">
                                <label>User ID</label>
                                <div class="field-value">1</div>
                            </div>
                            <div class="field-group">
                                <label>Current Site</label>
                                <div class="field-value">Main Street Project</div>
                            </div>
                            <div class="field-group">
                                <label>Join Date</label>
                                <div class="field-value">May 15, 2022</div>
                            </div>
                            <div class="field-group">
                                <label>Employment Status</label>
                                <div class="field-value"><span class="status-badge active">Active</span></div>
                            </div>
                            <div class="field-group">
                                <label>Monthly Salary</label>
                                <div class="field-value green-text">₱15,000</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="attendanceRecords" class="modal-tab-content">
                    <div class="content-section-header">
                        <div class="content-section-title">
                            <h3>Attendance History</h3>
                        </div>
                        <div class="filter-dropdown">
                            <i class="fas fa-filter"></i>
                            <select><option>Last 30 Days</option></select>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-title">Attendance Rate</div>
                            <div class="stat-value">90%</div>
                            <div class="stat-sub">Based on 20 days</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Avg. Hours</div>
                            <div class="stat-value">8.0h</div>
                            <div class="stat-sub neutral">Per worked day</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Late Arrivals</div>
                            <div class="stat-value warning">2</div>
                            <div class="stat-sub neutral">Days late</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Absences</div>
                            <div class="stat-value danger">2</div>
                            <div class="stat-sub neutral">Days absent</div>
                        </div>
                    </div>

                    <div class="attendance-table-wrapper">
                        <table class="employee-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Fri, Jan 16</td>
                                    <td><span class="status-pill pill-present"><i class="far fa-check-circle"></i> Present</span></td>
                                    <td>07:12 AM</td>
                                    <td>04:05 PM</td>
                                    <td><strong>7.88h</strong></td>
                                </tr>
                                <tr>
                                    <td>Thu, Jan 15</td>
                                    <td><span class="status-pill pill-present"><i class="far fa-check-circle"></i> Present</span></td>
                                    <td>07:21 AM</td>
                                    <td>04:30 PM</td>
                                    <td><strong>8.16h</strong></td>
                                </tr>
                                <tr>
                                    <td>Wed, Jan 14</td>
                                    <td><span class="status-pill pill-absent"><i class="far fa-times-circle"></i> Absent</span></td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td><strong>0.00h</strong></td>
                                </tr>
                                <tr>
                                    <td>Tue, Jan 13</td>
                                    <td><span class="status-pill pill-late"><i class="far fa-clock"></i> Late</span></td>
                                    <td>08:45 AM</td>
                                    <td>05:00 PM</td>
                                    <td><strong>7.25h</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="employmentHistory" class="modal-tab-content">
                    <div class="content-section-header">
                        <div class="content-section-title">
                            <h3>Employment History</h3>
                            <p>Timeline of roles, promotions, and site assignments.</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-indicator active"></div>
                                <div class="timeline-content">
                                    <h4>Senior Construction Worker</h4>
                                    <p>Main Street Project</p>
                                    <div class="timeline-meta">
                                        <span class="timeline-date">Jan 2024 - Present</span>
                                    </div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-indicator"></div>
                                <div class="timeline-content">
                                    <h4>Transferred to Main Street Project</h4>
                                    <p>Assigned to new commercial development phase.</p>
                                    <div class="timeline-meta">
                                        <span class="timeline-date">Mar 2023 - Jan 2024</span>
                                    </div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-indicator"></div>
                                <div class="timeline-content">
                                    <h4>Hired as Construction Worker</h4>
                                    <p>Downtown Office Complex</p>
                                    <div class="timeline-meta">
                                        <span class="timeline-date">May 2022 - Mar 2023</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="editProfileModal" class="modal" style="z-index: 1050;">
        <div class="modal-content-lg" style="max-width: 700px;">
            <div class="modal-header-simple" style="border-bottom: 1px solid #e5e7eb;">
                <h2>Edit Profile Info</h2>
                <button class="modal-close-btn" id="closeEditModalBtn">&times;</button>
            </div>
            <div class="modal-body-scroll" style="padding: 24px;">
                
                <h4 style="margin-bottom: 15px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">Contact Details</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="John Doe">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" value="john.doe@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" class="form-control" value="(555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" class="form-control">
                    </div>
                </div>

                <h4 style="margin-top: 10px; margin-bottom: 15px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">Address</h4>
                <div class="grid-2">
                    <div class="form-group full-width">
                        <label>Street Address</label>
                        <input type="text" class="form-control" value="123 Construction Way">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" class="form-control" value="Builder City">
                    </div>
                    <div class="form-group">
                        <label>State / Province</label>
                        <input type="text" class="form-control" value="CA">
                    </div>
                    <div class="form-group">
                        <label>Postal / ZIP Code</label>
                        <input type="text" class="form-control" value="90210">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" class="form-control" value="USA">
                    </div>
                </div>

                <h4 style="margin-top: 10px; margin-bottom: 15px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">Emergency Contact</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Contact Name</label>
                        <input type="text" class="form-control" placeholder="Enter contact name">
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" class="form-control" placeholder="Enter contact phone">
                    </div>
                    <div class="form-group full-width">
                        <label>Relationship</label>
                        <input type="text" class="form-control" placeholder="E.g. Spouse, Sibling">
                    </div>
                </div>

                <h4 style="margin-top: 10px; margin-bottom: 15px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">System Information</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control">
                            <option selected>Construction Worker</option>
                            <option>Electrician</option>
                            <option>Foreman</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Current Site</label>
                        <select class="form-control">
                            <option selected>Main Street Project</option>
                            <option>Downtown Office Complex</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select class="form-control">
                            <option selected>Active</option>
                            <option>Inactive</option>
                            <option>On Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monthly Salary (₱)</label>
                        <input type="number" class="form-control" value="15000">
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="border-top: 1px solid #e5e7eb;">
                <button class="btn-secondary" id="cancelEditBtn">Cancel</button>
                <button class="btn-primary" id="saveEditBtn">Save Changes</button>
            </div>
        </div>
    </div>

  
</body>
</html>
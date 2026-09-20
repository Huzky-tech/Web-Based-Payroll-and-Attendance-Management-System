<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff', 'HR']);
$embeddedDashboard = $embeddedDashboard ?? false;
$canManageSites = $currentRole === 'Assistant Admin';
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sites - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/active_site.css?v=20260906-3">
    <link rel="stylesheet" href="../css/responsive_mobile.css?v=20260903-1">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
    <script src="../js/responsive_mobile.js?v=20260913-1" defer></script>
<script src="../js/active_site.js?v=20260907-1" defer></script>
</head>
<body data-dashboard-role="<?php echo ($_SESSION['role'] ?? '') === 'Assistant Admin' ? 'assistant' : (($_SESSION['role'] ?? '') === 'Admin' ? 'admin' : 'payroll'); ?>">
<?php endif; ?>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->

            <!-- Page Header -->
            <div class="page-header">
                <h1>Active Sites Management</h1>
                <p>Monitor and manage all construction sites</p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-icon blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value"></div>
                        <div class="summary-card-label">Total Sites</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon green">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value"></div>
                        <div class="summary-card-label">Active Sites</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value"></div>
                        <div class="summary-card-label">Total Workers</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon orange">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value"></div>
                        <div class="summary-card-label">Utilization</div>
                    </div>
                </div>
            </div>

            <!-- Add Site Button -->
            <?php if ($canManageSites): ?>
            <div class="add-site-section">
                <button class="btn-add-site" id="btnAddSite" type="button">
                    <i class="fas fa-plus"></i>
                    <span>Add New Site</span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Site Cards -->
            <div class="site-cards" id="siteCards">
            </div>
        </div>
    </div>

    <!-- Add New Site Modal -->
    <div class="modal-overlay" id="addSiteModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="header-title">
                    <div class="icon-box-orange">
                        <i class="fas fa-building"></i>
                    </div>
                    <h2>Add New Construction Site</h2>
                </div>
                <button class="close-btn" id="closeModal" type="button">&times;</button>
            </div>

            <form id="addSiteForm">
                <div class="stepper-wrapper">
                    <div class="step-item current" data-step-indicator="0">
                        <div class="step-circle">1</div>
                        <span class="step-label">Details</span>
                    </div>
                    <div class="step-line" data-step-line="0"></div>
                    <div class="step-item" data-step-indicator="1">
                        <div class="step-circle">2</div>
                        <span class="step-label">Location</span>
                    </div>
                    <div class="step-line" data-step-line="1"></div>
                    <div class="step-item" data-step-indicator="2">
                        <div class="step-circle">3</div>
                        <span class="step-label">Operations</span>
                    </div>
                    <div class="step-line" data-step-line="2"></div>
                    <div class="step-item" data-step-indicator="3">
                        <div class="step-circle">4</div>
                        <span class="step-label">Confirm</span>
                    </div>
                </div>

                <div class="modal-content">
                    <section class="modal-step active" data-step-panel="0">
                        <div class="step-panel-header">
                            <h3>Site Details</h3>
                            <p>Start with the basic site identity that appears first on the site card.</p>
                        </div>

                        <div class="modal-form-grid">
                            <div class="form-group form-group-full">
                                <label for="siteName">Site Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="siteName" 
                                    name="siteName" 
                                    placeholder="e.g. Road Street Site"
                                    required
                                >
                            </div>

                            <div class="form-group form-group-full">
                                <label>Status</label>
                                <div class="radio-group modern-radio-group">
                                    <label class="radio-option modern-radio-option" for="statusActive">
                                        <input type="radio" id="statusActive" name="status" value="active" checked>
                                        <span>Active</span>
                                    </label>
                                    <label class="radio-option modern-radio-option" for="statusInactive">
                                        <input type="radio" id="statusInactive" name="status" value="inactive">
                                        <span>Inactive</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="modal-step" data-step-panel="1">
                        <div class="step-panel-header">
                            <h3>Location</h3>
                            <p>Add the site address and optional coordinates for the review summary.</p>
                        </div>

                        <div class="modal-form-grid">
                            <div class="form-group form-group-full">
                                <label for="location">Site Location <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input 
                                        type="text" 
                                        id="location" 
                                        name="location" 
                                        placeholder="e.g. Rodelsa Circle, Cagayan de Oro City"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-group form-group-full">
                                <label for="siteCoordinates">Coordinates</label>
                                <input
                                    type="text"
                                    id="siteCoordinates"
                                    name="siteCoordinates"
                                    placeholder="e.g. 8.47086, 124.64307"
                                >
                            </div>
                        </div>
                    </section>

                    <section class="modal-step" data-step-panel="2">
                        <div class="step-panel-header">
                            <h3>Operations</h3>
                            <p>Set the operating details that shape the target capacity and review card.</p>
                        </div>

                        <div class="modal-form-grid">
                            <div class="form-group">
                                <label for="requiredWorkers">Target Capacity <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-users"></i>
                                    <input 
                                        type="number" 
                                        id="requiredWorkers" 
                                        name="requiredWorkers" 
                                        placeholder="e.g. 50"
                                        min="1"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="siteManager">Manager</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-briefcase"></i>
                                    <input 
                                        type="text" 
                                        id="siteManager" 
                                        name="siteManager" 
                                        placeholder="e.g. John Smith"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="startDate">Start Date <span class="required">*</span></label>
                                <div class="input-with-icon right-icon">
                                    <input 
                                        type="date" 
                                        id="startDate" 
                                        name="startDate"
                                        required
                                    >
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="shiftStart">Shift Start Time</label>
                                <input type="time" id="shiftStart" name="shiftStart" value="07:00" required>
                            </div>

                            <div class="form-group">
                                <label for="lunchStart">Lunch Start Time</label>
                                <input type="time" id="lunchStart" name="lunchStart" value="12:00" required>
                            </div>

                            <div class="form-group">
                                <label for="lunchEnd">Lunch End Time</label>
                                <input type="time" id="lunchEnd" name="lunchEnd" value="13:00" required>
                            </div>

                            <div class="form-group">
                                <label for="shiftEnd">Shift End Time</label>
                                <input type="time" id="shiftEnd" name="shiftEnd" value="17:00" required>
                            </div>
                        </div>
                    </section>

                    <section class="modal-step confirm-step" data-step-panel="3">
                        <div class="confirm-header">
                            <div class="big-check-icon">&#10003;</div>
                            <h3>Review & Confirm</h3>
                            <p>Please review the site details before creating.</p>
                        </div>

                        <div class="summary-card">
                            <div class="summary-group">
                                <label>SITE NAME</label>
                                <p class="data-value" id="confirmSiteName">-</p>
                            </div>
                            <div class="summary-group">
                                <label>STATUS</label>
                                <span class="badge-active" id="confirmSiteStatus">Active</span>
                            </div>
                            <div class="summary-group full-width">
                                <label>LOCATION</label>
                                <p class="data-value" id="confirmSiteLocation">-</p>
                                <p class="coordinates" id="confirmSiteCoordinates">Coordinates: Not set</p>
                            </div>
                            <div class="summary-group">
                                <label>MANAGER</label>
                                <p class="data-value" id="confirmSiteManager">Not assigned</p>
                            </div>
                            <div class="summary-group">
                                <label>WORKERS</label>
                                <p class="data-value" id="confirmSiteWorkers">0 required</p>
                            </div>
                            <div class="summary-group">
                                <label>START DATE</label>
                                <p class="data-value" id="confirmSiteStartDate">-</p>
                            </div>
                            <div class="summary-group">
                                <label>HOURS</label>
                                <p class="data-value" id="confirmSiteHours">07:00 - 17:00</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="addSiteBackBtn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </button>

                    <button type="button" class="btn-primary-green" id="addSiteNextBtn">
                        <span>Next</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn-primary-green" id="addSiteSubmitBtn">
                        <i class="fas fa-check"></i>
                        <span>Confirm & Create Site</span>
                    </button>

                </div>

            </form>
        </div>
    </div>

    <div class="assign-workers-overlay" id="assignWorkersModal" aria-hidden="true">
        <div class="assign-workers-dialog" role="dialog" aria-modal="true" aria-labelledby="assignWorkersTitle">
            <div class="assign-workers-header">
                <div>
                    <div class="assign-workers-title" id="assignWorkersTitle">Assign Workers</div>
                    <div class="assign-workers-subtitle">Manage worker assignments and designate site roles</div>
                </div>
                <button type="button" class="assign-workers-close" id="closeAssignWorkersModal" aria-label="Close assign workers modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="assign-workers-top-cards">
                <div class="assign-workers-top-card">
                    <div class="assign-workers-top-title">Assigned Workers</div>
                    <div class="assign-workers-top-value" id="assignWorkersCountCard">0 <span>/0</span></div>
                </div>

                <div class="assign-workers-top-card assign-timekeeper-card">
                    <div class="assign-workers-top-title">Timekeeper</div>
                    <select id="assignSiteTimekeeperSelect" class="assign-timekeeper-select" aria-label="Assign timekeeper">
                        <option value="">Not assigned</option>
                    </select>
                </div>

                <div class="assign-workers-top-card accent-blue">
                    <div class="assign-workers-top-title">
                        <i class="fas fa-location-dot"></i>
                        <span>Site Location</span>
                    </div>
                    <strong id="assignWorkersLocationCard">No location provided</strong>
                </div>
            </div>

            <div class="assign-workers-search-row">
                <input
                    type="text"
                    id="assignWorkersSearch"
                    class="assign-workers-search-input"
                    placeholder="Search available workers by name, ID, position, or address..."
                >

                <button type="button" class="assign-workers-temp-btn" id="assignWorkersTempBtn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Temporary Worker</span>
                </button>
            </div>

            <div class="assign-workers-filters">
                <select class="assign-workers-filter-select" id="assignWorkersStatusFilter" aria-label="Filter workers by assignment status">
                    <option value="">All Workers</option>
                    <option value="assigned">Assigned</option>
                    <option value="not_assigned">Not Assigned</option>
                </select>

                <select class="assign-workers-filter-select" id="assignWorkersRoleFilter">
                    <option value="">All Positions</option>
                </select>

                <select class="assign-workers-filter-select" id="assignWorkersLocationFilter">
                    <option value="">All Locations</option>
                </select>
            </div>

            <div class="assign-workers-feedback" id="assignWorkersFeedback" aria-live="polite"></div>

            <div class="assign-workers-list" id="assignWorkersList">
                <div class="assign-workers-empty">Select a site to manage worker assignments.</div>
            </div>

            <div class="assign-workers-footer">
                <button type="button" class="assign-workers-footer-btn cancel" id="cancelAssignWorkersBtn">Cancel</button>
                <button type="button" class="assign-workers-footer-btn save" id="saveAssignWorkersBtn">Save Assignments</button>
            </div>
        </div>
    </div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

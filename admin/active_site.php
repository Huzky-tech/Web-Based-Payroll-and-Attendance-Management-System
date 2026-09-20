<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$embeddedDashboard = $embeddedDashboard ?? false;
$canManageSites = in_array($currentRole, ['Admin', 'Assistant Admin'], true);
$googleMapsApiKey = htmlspecialchars(getenv('GOOGLE_MAPS_API_KEY') ?: '', ENT_QUOTES, 'UTF-8');
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-maps-api-key" content="<?php echo $googleMapsApiKey; ?>">
    <title>Active Sites - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../css/active_site.css?v=20260921-manager-1">
    <link rel="stylesheet" href="../css/responsive_mobile.css?v=20260903-1">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
    <script src="../js/responsive_mobile.js?v=20260913-1" defer></script>
<script src="../js/active_site.js?v=20260921-manager-1" defer></script>

</head>
<body data-dashboard-role="<?php
    echo match ($currentRole) {
        'Admin' => 'admin',
        'Assistant Admin' => 'assistant',
        'HR' => 'hr',
        default => 'payroll',
    };
?>">
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

            <div class="site-toolbar" aria-label="Search and filter sites">
                <div class="site-search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="siteSearchInput" placeholder="Search sites or locations...">
                </div>
                <select id="siteWorkerFilter" aria-label="Filter by worker assignment">
                    <option value="">All worker assignments</option>
                    <option value="assigned">With assigned workers</option>
                    <option value="unassigned">No assigned workers</option>
                </select>
                <select id="siteStatusFilter" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="siteAssignmentSort" aria-label="Sort by worker assignments">
                    <option value="newest">Newest</option>
                    <option value="most_assigned">Most workers assigned</option>
                    <option value="least_assigned">Least workers assigned</option>
                </select>
                <?php if ($canManageSites): ?>
                <button class="btn-add-site" id="btnAddSite" type="button">
                    <i class="fas fa-plus"></i>
                    <span>Add New Site</span>
                </button>
                <?php endif; ?>
            </div>

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
                                <div class="site-field-error" id="siteNameError" aria-live="polite"></div>
                            </div>

                            <div class="form-group form-group-full">
                                <div class="site-status-note"><i class="fas fa-info-circle"></i> New sites start as <strong>Inactive</strong> and automatically become active once a worker is assigned.</div>
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
                                        maxlength="255"
                                        required
                                    >
                                </div>
                                <div class="site-field-error" id="locationError" aria-live="polite"></div>
                            </div>

                            <div class="form-group form-group-full">
                                <label for="siteCoordinates">Coordinates (Pin)</label>

                                <input
                                    type="text"
                                    id="siteCoordinates"
                                    name="siteCoordinates"
                                    placeholder="e.g. 8.47086, 124.64307"
                                    inputmode="decimal"
                                >
                                <div class="site-field-error" id="siteCoordinatesError" aria-live="polite"></div>
                            </div>

                            <div class="form-group form-group-full">
                                <label for="geofenceRadiusM">Location Lock Radius (meters) <span class="required">*</span></label>
                                <input
                                    type="text"
                                    id="geofenceRadiusM"
                                    name="geofenceRadiusM"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                    min="1"
                                    max="20000"
                                    step="1"
                                    placeholder="e.g. 50"
                                    required
                                >
                                <div class="site-field-error" id="geofenceRadiusMError" aria-live="polite"></div>
                                <div class="muted" style="margin-top:6px; font-size:12px;">
                                    A circular geofence will be drawn around the draggable pin.
                                </div>
                            </div>

                            <input type="hidden" id="geofenceLatitude" name="geofenceLatitude" value="">
                            <input type="hidden" id="geofenceLongitude" name="geofenceLongitude" value="">
                            <input type="hidden" id="geofenceRadiusMHidden" name="geofenceRadiusMHidden" value="">


                            <div class="form-group form-group-full">
                                <div class="site-map-card">
                                    <div class="site-map-card-header">
                                        <div>
                                            <span class="site-map-chip">
                                                <i class="fas fa-map-pin"></i>
                                                <span>Philippines Map Pin</span>
                                            </span>
                                            <h4>Pin Site Location</h4>
                                            <p>Type a Philippine address, then drag or place the pin on the exact site.</p>
                                        </div>
                                        <div class="site-map-header-actions">
                                            <button type="button" class="btn-map-search btn-use-current-location" id="useMyLocationBtn">
                                                <i class="fas fa-crosshairs"></i>
                                                <span>Use My Location</span>
                                            </button>
                                            <button type="button" class="btn-map-search" id="findLocationOnMapBtn">
                                                <i class="fas fa-location-crosshairs"></i>
                                                <span>Find on Map</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="site-map-feedback" id="siteMapFeedback">Search for a location in the Philippines to load the map.</div>
                                    <div class="site-map-frame" id="siteMapFrame">
                                        <div class="site-map-placeholder" id="siteMapPlaceholder">
                                            <i class="fas fa-map-marked-alt"></i>
                                            <span>The map will appear here after you search for a location.</span>
                                        </div>
                                        <div class="site-location-map" id="siteLocationMap" aria-label="Construction site location map"></div>
                                    </div>
                                    <div class="site-map-helper-row">
                                        <div class="site-map-helper-card">
                                            <i class="fas fa-keyboard"></i>
                                            <div>
                                                <strong>Search</strong>
                                                <span>Use barangay, city, or province for a better result.</span>
                                            </div>
                                        </div>
                                        <div class="site-map-helper-card">
                                            <i class="fas fa-hand-pointer"></i>
                                            <div>
                                                <strong>Pin Exact Spot</strong>
                                                <span>Click the map or drag the marker to the exact site entrance.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                        max="10000"
                                        step="1"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="siteManager">Manager</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-briefcase"></i>
                                    <select id="siteManager" name="siteManager" required>
                                        <option value="" selected disabled>Select manager</option>
                                    </select>
                                </div>
                                <div class="site-field-error" id="siteManagerError" aria-live="polite"></div>
                            </div>

                             <div class="form-group">
                                <label for="startDate">Start Date <span class="required">*</span></label>
                                <div class="input-with-icon right-icon">
                                    <input 
                                        type="date" 
                                        id="startDate" 
                                        name="startDate"
                                        min="<?php echo date('Y-m-d'); ?>"
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
                                <span class="badge-active badge-inactive" id="confirmSiteStatus">Inactive until workers are assigned</span>
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
                        <option value="">Select a timekeeper</option>
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

            <div class="assign-site-activation-hint" id="assignSiteActivationHint" aria-live="polite"></div>

            <div class="assign-workers-search-row">
                <input
                    type="text"
                    id="assignWorkersSearch"
                    class="assign-workers-search-input"
                    placeholder="Search available workers by name, ID, position, or address..."
                >

                <button type="button" class="assign-workers-temp-btn" id="assignWorkersTempBtn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Worker</span>
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

    <div class="details-modal-overlay" id="siteDetailsModal" aria-hidden="true">
        <div class="details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="siteDetailsTitle">
            <div class="details-modal-header">
                <div>
                    <div class="details-modal-title" id="siteDetailsTitle">Site Details</div>
                    <div class="details-modal-subtitle" id="siteDetailsSubtitle">Review full site information.</div>
                </div>
                <button type="button" class="details-modal-close" id="closeSiteDetailsModal" aria-label="Close site details">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="details-modal-body" id="siteDetailsBody"></div>
        </div>
    </div>

    <div class="edit-site-overlay" id="editSiteModal" aria-hidden="true">
        <div class="edit-site-dialog" role="dialog" aria-modal="true" aria-labelledby="editSiteTitle">
            <div class="edit-site-header">
                <div>
                    <div class="edit-site-title" id="editSiteTitle">Edit Site</div>
                    <div class="edit-site-subtitle">Update the site information and schedule.</div>
                </div>
                <button type="button" class="edit-site-close" id="closeEditSiteModal" aria-label="Close edit site modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form class="edit-site-form" id="editSiteForm">
                <input type="hidden" id="editSiteId" name="site_id">

                <div class="edit-site-grid">
                    <div class="form-group form-group-full">
                        <label for="editSiteName">Site Name</label>
                        <input type="text" id="editSiteName" name="site_name" required>
                        <div class="site-field-error" id="editSiteNameError" aria-live="polite"></div>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="editSiteLocation">Location</label>
                        <input type="text" id="editSiteLocation" name="location" maxlength="255" required>
                        <div class="site-field-error" id="editSiteLocationError" aria-live="polite"></div>
                    </div>

                    <div class="form-group form-group-full">
                        <div class="site-map-ui-card edit-site-map-card">
                            <div class="site-map-ui-header">
                                <div>
                                    <div class="site-map-ui-title">Edit Site Pin</div>
                                    <div class="site-map-ui-subtitle">Search a Philippine location, then click or drag the pin to the exact site spot.</div>
                                </div>
                                <span class="site-map-ui-chip">Pin Editor</span>
                            </div>

                            <div class="site-map-ui-actions">
                                <button type="button" class="site-map-search-btn" id="useMyEditLocationBtn">
                                    <i class="fas fa-location-dot"></i>
                                    <span>Use My Location</span>
                                </button>
                                <button type="button" class="site-map-search-btn" id="findEditLocationOnMapBtn">
                                    <i class="fas fa-location-crosshairs"></i>
                                    <span>Find on Map</span>
                                </button>   
                            </div>

                            <div class="site-map-feedback" id="editSiteMapFeedback" aria-live="polite"></div>

                            <div class="site-map-stage active">
                                <div class="site-map-frame">
                                    <div class="site-location-map" id="editSiteLocationMap" aria-label="Editable site location map"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editSiteCoordinates">Coordinates (Pin)</label>
                        <input type="text" id="editSiteCoordinates" name="coordinates" inputmode="decimal" placeholder="e.g. 8.470860, 124.643070">
                        <div class="site-field-error" id="editSiteCoordinatesError" aria-live="polite"></div>
                    </div>

                    <div class="form-group">
                        <label for="editGeofenceRadiusM">Location Lock Radius (meters) <span class="required">*</span></label>
                        <input
                            type="text"
                            id="editGeofenceRadiusM"
                            name="geofenceRadiusM"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            min="1"
                            max="20000"
                            step="1"
                            placeholder="e.g. 50"
                            required
                        >
                        <div class="site-field-error" id="editGeofenceRadiusMError" aria-live="polite"></div>
                    </div>

                    <input type="hidden" id="editGeofenceLatitude" name="geofenceLatitude" value="">
                    <input type="hidden" id="editGeofenceLongitude" name="geofenceLongitude" value="">
                    <input type="hidden" id="editGeofenceRadiusMHidden" name="geofenceRadiusMHidden" value="">


                    <div class="form-group">
                        <label for="editSiteStatus">Status</label>
                        <select id="editSiteStatus" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editRequiredWorkers">Required Workers</label>
                        <input type="number" id="editRequiredWorkers" name="required_workers" min="1" max="10000" step="1" required>
                        <div class="site-field-error" id="editRequiredWorkersError" aria-live="polite"></div>
                    </div>

                     <div class="form-group">
                                <label for="editSiteManager">Manager</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-briefcase"></i>
                                    <select id="editSiteManager" name="site_manager" required>
                                        <option value="" selected disabled>Select manager</option>
                                    </select>
                                </div>
                                <div class="site-field-error" id="editSiteManagerError" aria-live="polite"></div>
                            </div>

                    <div class="form-group">
                        <label for="editShiftStart">Start Time</label>
                        <input type="time" id="editShiftStart" name="shift_start">
                    </div>

                    <div class="form-group">
                        <label for="editLunchStart">Lunch Start Time</label>
                        <input type="time" id="editLunchStart" name="lunch_start" value="12:00">
                    </div>

                    <div class="form-group">
                        <label for="editLunchEnd">Lunch End Time</label>
                        <input type="time" id="editLunchEnd" name="lunch_end" value="13:00">
                    </div>

                    <div class="form-group">
                        <label for="editShiftEnd">End Time</label>
                        <input type="time" id="editShiftEnd" name="shift_end">
                    </div>

                </div>

                <div class="edit-site-feedback" id="editSiteFeedback" aria-live="polite"></div>

                <div class="edit-site-footer">
                    <button type="button" class="edit-site-btn secondary" id="cancelEditSiteBtn">Cancel</button>
                    <button type="submit" class="edit-site-btn primary" id="saveEditSiteBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="edit-site-overlay" id="archiveSiteModal" aria-hidden="true">
        <div class="edit-site-dialog archive-site-dialog" role="dialog" aria-modal="true" aria-labelledby="archiveSiteTitle">
            <div class="edit-site-header">
                <div>
                    <div class="edit-site-title" id="archiveSiteTitle">Archive Site</div>
                    <div class="edit-site-subtitle">Move this site out of the active site list.</div>
                </div>
                <button type="button" class="edit-site-close" id="closeArchiveSiteModal" aria-label="Close archive site modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="archive-site-modal-body">
                <p class="archive-site-modal-message">Are you sure you want to archive this site?</p>
                <p class="archive-site-modal-note">The site will remain available from the Archive page and can be restored later.</p>
            </div>
            <div class="archive-site-modal-footer">
                <button type="button" class="archive-site-btn secondary" id="cancelArchiveSiteBtn">Cancel</button>
                <button type="button" class="archive-site-btn primary" id="confirmArchiveSiteBtn"><i class="fas fa-box-archive"></i> Archive Site</button>
            </div>
        </div>
    </div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

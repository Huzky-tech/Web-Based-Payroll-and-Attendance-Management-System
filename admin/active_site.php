<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sites - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/active_site.css">
    <script src="../js/active_site.js" defer></script>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1 class="page-title">Active Sites</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:46 PM</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">A</div>
                    <div class="user-info">
                        <div class="user-name">admin</div>
                        <div class="user-role">Manager</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Breadcrumbs -->
            <div class="breadcrumbs">
                <a href="dashboard.php">admin</a>
                <span>></span>
                <span>Active Sites Management</span>
            </div>

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
            <div class="add-site-section">
                <button class="btn-add-site" id="btnAddSite">
                    <i class="fas fa-plus"></i>
                    <span>Add New Site</span>
                </button>
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
                <h2 class="modal-title">
                    <i class="fas fa-building"></i>
                    <span>Add New Construction Site</span>
                </h2>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addSiteForm">
                <div class="form-group">
                    <label for="siteName">Site Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="siteName" 
                        name="siteName" 
                        placeholder="e.g. Downtown Office Complex"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="location">Location / Address <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            placeholder="e.g. 123 Main St, Cityville"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="requiredWorkers">Required Workers <span class="required">*</span></label>
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
                    <label for="startDate">Start Date</label>
                    <div class="input-with-icon right-icon">
                        <input 
                            type="date" 
                            id="startDate" 
                            name="startDate"
                        >
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="siteManager">Site Manager</label>
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
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Brief description of the project scope..."
                    ></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="statusActive" name="status" value="active" checked>
                            <label for="statusActive">Active</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="statusInactive" name="status" value="inactive">
                            <label for="statusInactive">Inactive</label>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-create">Create Site</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

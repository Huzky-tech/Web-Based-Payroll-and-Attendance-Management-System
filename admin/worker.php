<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Directory - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/worker.css">
     <script src="../js/worker.js" defer></script>
</head>
<body>


    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <div class="page-title">Worker Directory</div>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:46 PM</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">A</div>
                    <div class="user-info">
                        <div class="user-name">admin</div>
                        <div class="user-role">admin</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="breadcrumbs">
                <a href="dashboard.html">Admin</a><span>></span><span>Worker Directory</span>
            </div>

            <div class="page-header">
                <div>
                    <h1>Worker Directory</h1>
                    <p>Manage and monitor all construction workers</p>
                </div>
                <button class="btn-primary" id="btnAddWorker"><i class="fas fa-plus"></i><span>Add Worker</span></button>
            </div>

            <!-- Summary -->
            <div class="summary-cards" id="summaryCards">
                <div class="summary-card">
                    <div class="summary-icon blue"><i class="fas fa-users"></i></div>
                    <div class="summary-content">
                        <div class="label">Total Workers</div>
                        <div class="value" data-summary="total">0</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green"><i class="fas fa-user-check"></i></div>
                    <div class="summary-content">
                        <div class="label">Active</div>
                        <div class="value" data-summary="active">0</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon amber"><i class="fas fa-user-clock"></i></div>
                    <div class="summary-content">
                        <div class="label">On Leave</div>
                        <div class="value" data-summary="onleave">0</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon gray"><i class="fas fa-user-slash"></i></div>
                    <div class="summary-content">
                        <div class="label">Inactive</div>
                        <div class="value" data-summary="inactive">0</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, or position...">
                </div>
                <select id="statusFilter" class="select-input">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="onleave">On Leave</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="siteFilter" class="select-input">
                    <option value="all">All Sites</option>
                </select>
            </div>

            <!-- Worker Cards -->
            <div class="worker-grid" id="workerGrid"></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="workerModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Add Worker</div>
                <button class="modal-close" id="closeModal"><i class="fas fa-times"></i></button>
            </div>
            <form id="workerForm">
                <div class="form-group">
                    <label for="workerName">Full Name</label>
                    <input type="text" id="workerName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="workerRole">Position / Role</label>
                    <input type="text" id="workerRole" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="workerSite">Site</label>
                    <input type="text" id="workerSite" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="workerEmail">Email</label>
                    <input type="email" id="workerEmail" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="workerPhone">Phone</label>
                    <input type="text" id="workerPhone" class="form-control" placeholder="+63" required>
                </div>
                    <div class="form-group">
                    <label for="workerJoin">Joined Date</label>
                    <input type="date" id="workerJoin" class="form-control">
                </div>
                <div class="form-group">
                    <label for="workerStatus">Status</label>
                    <select id="workerStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="onleave">On Leave</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelModal">Cancel</button>
                    <button type="submit" class="btn-primary" style="flex:1; justify-content:center;">Save Worker</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
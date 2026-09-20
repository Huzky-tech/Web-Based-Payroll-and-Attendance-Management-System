<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timekeeper Site Assignment - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/timekeeper_assign.css?v=20260608-1">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
<script src="../js/timekeeper_assign.js?v=20260913-security-1" defer></script>
</head>
<body>
<?php endif; ?>

<?php if (!$embeddedDashboard): ?>
<div class="main-content">
<?php endif; ?>
    <div class="content-area">
        <div class="page-header">
            <h1>Timekeeper Site Assignment</h1>
            <p class="page-subtitle">Assign each Timekeeper to a single active construction site for mobile attendance access.</p>
        </div>

        <div class="assignment-toolbar">
            <button type="button" class="btn-primary" id="btnAssignSite">
                <i class="fas fa-plus"></i> Assign Site
            </button>
        </div>

        <div class="table-card">
            <table class="assignment-table">
                <thead>
                    <tr>
                        <th>Timekeeper Name</th>
                        <th>Email</th>
                        <th>Assigned Site</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="assignmentTableBody">
                    <tr>
                        <td colspan="5" class="loading-cell">
                            <i class="fas fa-spinner fa-spin"></i> Loading assignments...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="assignmentModal" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Assign Site</h3>
                <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Close">&times;</button>
            </div>
            <form id="assignmentForm">
                <input type="hidden" id="formUserId" value="">
                <input type="hidden" id="formAssignmentId" value="">

                <div class="form-group" id="timekeeperSelectGroup">
                    <label for="formTimekeeper">Timekeeper</label>
                    <select id="formTimekeeper" required>
                        <option value="">Select Timekeeper</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="formSite">Site</label>
                    <select id="formSite" required>
                        <option value="">Select Active Site</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="formStatus">Status</label>
                    <select id="formStatus" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="modalCancelBtn">Cancel</button>
                    <button type="submit" class="btn-primary">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>

<?php if (!$embeddedDashboard): ?>
</div>
</body>
<?php endif; ?>

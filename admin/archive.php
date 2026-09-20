<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/archive.css?v=20260814-2">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/archive.js?v=20260814-2" defer></script>
</head>
<body>
<?php endif; ?>

<div class="content-area archive-page">
    <div class="archive-header-card">
        <div class="archive-header-left">
            <div class="archive-header-icon">
                <i class="fa-regular fa-box-archive"></i>
            </div>

            <div class="archive-header-text">
                <h1>Archive</h1>
                <p>View and manage archived records</p>
            </div>
        </div>

        <div class="archive-item-badge" id="archiveItemBadge">0 items</div>
    </div>

    <div class="archive-filter-card">
        <input type="text" class="archive-search" id="archiveSearchInput" placeholder="Search archived items...">

        <div class="archive-bottom-filter">
            <button class="archive-filter-btn" type="button" id="archiveFilterButton">
                <i class="fa-solid fa-filter"></i> Filters
            </button>

            <button class="archive-tag active" type="button" data-type="all" id="archiveTagAll">All (0)</button>
            <button class="archive-tag" type="button" data-type="employee" id="archiveTagEmployee">Employee (0)</button>
            <button class="archive-tag" type="button" data-type="site" id="archiveTagSite">Site (0)</button>
        </div>
    </div>

    <div class="archive-list" id="archiveList">
        <div class="archive-empty">Loading archived items...</div>
    </div>

    <div class="archive-empty" id="archiveEmptyState" hidden>No archived items match the current search or filter.</div>

    <div class="archive-modal-overlay" id="archiveDetailsModal" aria-hidden="true">
        <div class="archive-modal" role="dialog" aria-modal="true" aria-labelledby="archiveDetailsTitle">
            <div class="archive-modal-header">
                <div>
                    <h2 id="archiveDetailsTitle">Archived Record</h2>
                    <p id="archiveDetailsSubtitle">Review archived information</p>
                </div>

                <button type="button" class="archive-modal-close" id="closeArchiveDetailsModal" aria-label="Close archive details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="archive-modal-body">
                <div class="archive-modal-section">
                    <div class="archive-modal-label">Title</div>
                    <div class="archive-modal-value" id="archiveDetailsName">-</div>
                </div>

                <div class="archive-modal-section">
                    <div class="archive-modal-label">Description</div>
                    <div class="archive-modal-description" id="archiveDetailsDescription">-</div>
                </div>

                <div class="archive-modal-meta-grid">
                    <div class="archive-modal-section">
                        <div class="archive-modal-label">Archived Date</div>
                        <div class="archive-modal-value" id="archiveDetailsArchivedDate">-</div>
                    </div>

                    <div class="archive-modal-section">
                        <div class="archive-modal-label">Archived By</div>
                        <div class="archive-modal-value" id="archiveDetailsArchivedBy">-</div>
                    </div>

                    <div class="archive-modal-section">
                        <div class="archive-modal-label">Original Date</div>
                        <div class="archive-modal-value" id="archiveDetailsOriginalDate">-</div>
                    </div>
                </div>
            </div>

            <div class="archive-modal-footer">
                <button type="button" class="archive-modal-footer-close" id="closeArchiveDetailsFooter">Close</button>
            </div>
        </div>
    </div>

    <div class="archive-modal-overlay" id="restoreArchiveModal" aria-hidden="true">
        <div class="archive-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="restoreArchiveTitle">
            <div class="archive-confirm-icon"><i class="fa-solid fa-trash-arrow-up"></i></div>
            <h2 id="restoreArchiveTitle">Restore archived record?</h2>
            <p>Are you sure you want to restore <strong id="restoreArchiveItemName">this record</strong>?</p>
            <div class="archive-confirm-actions">
                <button type="button" class="archive-confirm-cancel" id="cancelRestoreArchive">Cancel</button>
                <button type="button" class="archive-confirm-restore" id="confirmRestoreArchive"><i class="fa-solid fa-trash-arrow-up"></i> Restore</button>
            </div>
        </div>
    </div>

    <div class="archive-toast" id="archiveToast" aria-live="polite"></div>
</div>

<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

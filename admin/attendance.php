<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippians CDO - Attendance Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/attendance.css?v=20260907-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/attendance.js?v=20260915-schedule-refresh-1" defer></script>
</head>
<body>
<?php endif; ?>
    <main class="main-content">
        <header>
            <h1>Attendance Tracking</h1>
            <div class="user-controls"></div>
        </header>
        <section class="page-title-box">
            <h2 style="font-size: 18px;">Attendance Tracking</h2>
            <div class="filter-row">
                <input type="date" id="attendanceDateFilter" value="2023-07-05">
                <input type="text" id="attendanceSearchInput" placeholder="Search by name or ID..." style="width: 250px;">
                <select id="attendanceSiteFilter"><option value="">All Sites</option></select>
                <select id="attendancePositionFilter"><option value="">All Positions</option></select>
                <select id="attendanceManagerFilter"><option value="">All Site Managers</option></select>
                <select id="attendanceStatusFilter">
                    <option value="">All Status</option>
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                    <option value="Not Started">Not Started</option>
                    <option value="Absent">Absent</option>
                </select>
                <button class="export-btn" id="attendanceExportCsvBtn" type="button" title="Export attendance to Excel"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
            </div>
        </section>

        <section class="attendance-slider">
            <button class="slider-nav slider-nav-left" id="statsPrevBtn" type="button" aria-label="Scroll attendance cards left">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="cards-track" id="statsCardsWrapper">
                <div class="cards-grid" id="statsCards">
                    <div class="loading-spinner">Loading sites...</div>
                </div>
            </div>
            <button class="slider-nav slider-nav-right" id="statsNextBtn" type="button" aria-label="Scroll attendance cards right">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </section>

        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 id="tableSiteHeader" style="font-size: 14px;"><i class="fa-solid fa-city" style="color: #f39c12;"></i></h3>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <label style="font-size: 12px; color: #888; display: flex; align-items: center; gap: 6px;">
                        Rows:
                        <select id="attendanceLimitSelect" style="padding: 4px 8px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 12px;">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                    <span id="recordCount" style="font-size: 11px; background: #eee; padding: 4px 8px; border-radius: 4px;">0 records</span>
                </div>
            </div>

            <div class="attendance-table-scroll">
            <table>
                <thead>
                    <tr>
                        <th><button class="sort-btn" type="button" data-sort-by="employee">Employee</button></th>
                        <th>Date</th>
                        <th><button class="sort-btn" type="button" data-sort-by="time_in">Time In</button></th>
                        <th>Lunch Out</th>
                        <th>PM In</th>
                        <th><button class="sort-btn" type="button" data-sort-by="time_out">Time Out</button></th>
                        <th><button class="sort-btn" type="button" data-sort-by="status">Status</button></th>
                        <th><button class="sort-btn" type="button" data-sort-by="position">Position</button></th>
                        <th>Scanned By</th>
                        <th>Scan Coordinates</th>
                        <th>Photo Evidence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendance-body"></tbody>
            </table>
            </div>
            
            <!-- Attendance Pagination -->
            <div class="attendance-pagination" id="attendancePagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e0e0e0;">
                <span id="attendancePaginationInfo" style="font-size: 12px; color: #888;">Showing 0-0 of 0</span>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="att-pg-btn" id="attendancePrevPage" onclick="attendanceSystem.changePage(-1)" disabled>
                        <i class="fa-solid fa-chevron-left"></i> Prev
                    </button>
                    <div id="attendancePageNumbers" style="display: flex; align-items: center; gap: 4px;"></div>
                    <button type="button" class="att-pg-btn" id="attendanceNextPage" onclick="attendanceSystem.changePage(1)" disabled>
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

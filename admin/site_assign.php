<?php
include '../api/connection/db_config.php';

/* ============================
   GET TOTAL PAYROLL STAFF
============================ */
function getTotalPayrollStaff($conn){
$sql = "SELECT COUNT(*) as total FROM payrollstaff";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}


/* ============================
   GET TOTAL ACTIVE SITES
============================ */
function getActiveSites($conn){
$sql = "SELECT COUNT(*) as total FROM projectsite WHERE Status='active'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}


/* ============================
   GET TOTAL ASSIGNMENTS
============================ */
function getTotalAssignments($conn){
$sql = "SELECT COUNT(*) as total FROM payrollstaffassignment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}


/* ============================
   GET ALL PAYROLL STAFF
============================ */
function getPayrollStaff($conn){

$sql = "SELECT u.id as user_id, u.full_name as name, u.email,
            COUNT(psa.SiteID) as total_sites
            FROM users u 
            INNER JOIN payrollstaff ps ON u.id = ps.UserID
            LEFT JOIN payrollstaffassignment psa ON ps.PayrollStaff_ID = psa.PayrollStaff_ID
            GROUP BY u.id";

    $result = mysqli_query($conn,$sql);

    $staff = [];

    while($row = mysqli_fetch_assoc($result)){
        $staff[] = $row;
    }

    return $staff;
}


/* ============================
   GET STAFF SITE ASSIGNMENTS
============================ */
function getStaffAssignments($conn,$staff_id){

$sql = "SELECT p.SiteID as id, p.Site_Name as site_name
            FROM payrollstaffassignment psa
            JOIN projectsite p ON psa.SiteID = p.SiteID
            WHERE psa.PayrollStaff_ID IN (SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID='$staff_id')";



    $result = mysqli_query($conn,$sql);

    $sites = [];

    while($row = mysqli_fetch_assoc($result)){
        $sites[] = $row;
    }

    return $sites;
}


/* ============================
   ASSIGN SITE TO STAFF
============================ */
function assignSiteToStaff($conn,$staff_id,$site_id){

// Get PayrollStaff_ID from user_id
    $staff_query = "SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID='$staff_id'";
    $staff_result = mysqli_query($conn, $staff_query);
    if(mysqli_num_rows($staff_result) == 0){
        return "no_staff";
    }
    $staff_row = mysqli_fetch_assoc($staff_result);
    $payroll_staff_id = $staff_row['PayrollStaff_ID'];

    $check = "SELECT * FROM payrollstaffassignment 
              WHERE PayrollStaff_ID='$payroll_staff_id' 
              AND SiteID='$site_id'";
    $result = mysqli_query($conn,$check);

    if(mysqli_num_rows($result) > 0){
        return "already_assigned";
    }

    $sql = "INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID)
            VALUES('$payroll_staff_id','$site_id')";



    if(mysqli_query($conn,$sql)){
        return "success";
    }else{
        return "error";
    }

}


/* ============================
   REMOVE SITE ASSIGNMENT
============================ */
function removeSiteAssignment($conn,$staff_id,$site_id){

// Get PayrollStaff_ID from user_id
    $staff_query = "SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID='$staff_id'";
    $staff_result = mysqli_query($conn, $staff_query);
    if(mysqli_num_rows($staff_result) == 0){
        return "no_staff";
    }
    $staff_row = mysqli_fetch_assoc($staff_result);
    $payroll_staff_id = $staff_row['PayrollStaff_ID'];

    $sql = "DELETE FROM payrollstaffassignment
            WHERE PayrollStaff_ID='$payroll_staff_id'
            AND SiteID='$site_id'";



    if(mysqli_query($conn,$sql)){
        return "removed";
    }else{
        return "error";
    }

}


/* ============================
   GET AUDIT LOG
============================ */
function getAssignmentAuditLog($conn){

$sql = "SELECT u.full_name as name, p.Site_Name as site_name, al.Action, al.Date as created_at
            FROM audit_logs al
            LEFT JOIN users u ON al.UserID = u.id
            LEFT JOIN payrollstaffassignment psa ON al.Details LIKE CONCAT('%', psa.staffAssignID, '%')
            LEFT JOIN projectsite p ON psa.SiteID = p.SiteID
            WHERE al.Action LIKE '%assign%' OR al.Action LIKE '%site%'
            ORDER BY al.Date DESC
            LIMIT 50";



    $result = mysqli_query($conn,$sql);

    $logs = [];

    while($row = mysqli_fetch_assoc($result)){
        $logs[] = $row;
    }

    return $logs;
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/site_assign.css">
      <script src="../js/site_assign.js" defer></script>
</head>
<body>
  <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Site Assignments</h1>
                   </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Payroll Staff</div>
                       <div class="summary-value">
<?php echo getTotalPayrollStaff($conn); ?>
</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Active Sites</div>
                        <div class="summary-value">
<?php echo getActiveSites($conn); ?>
</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Assignments</div>
                        <div class="summary-value">
<?php echo getTotalAssignments($conn); ?>
</div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment Management -->
            <div class="assignment-section">
                <div class="assignment-header">
                    <div class="assignment-title-section">
                        <h3>Staff Assignment Management</h3>
                        <p class="assignment-subtitle">Assign payroll staff to specific construction sites</p>
                    </div>
                    <button class="btn-audit" onclick="viewAuditLog()">
                        <i class="fas fa-clipboard-list"></i> View Audit Log
                    </button>
                </div>

                <div class="assignment-panels">
                    <!-- Left Panel - Staff List -->
                    <div class="staff-list-panel">
                        <div class="staff-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="staffSearch" placeholder="Search payroll staff..." onkeyup="filterStaff()">
                        </div>
                        <div class="staff-list" id="staffList">
                            <div class="staff-item" data-staff="staff-a" onclick="selectStaff('staff-a', 'Payroll Staff A')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff A</div>
                                    <span class="staff-sites-badge">1 Sites</span>
                                </div>
                                <div class="staff-email">staff.a@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-b" onclick="selectStaff('staff-b', 'Payroll Staff B')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff B</div>
                                </div>
                                <div class="staff-email">staff.b@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-c" onclick="selectStaff('staff-c', 'Payroll Staff C')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff C</div>
                                </div>
                                <div class="staff-email">staff.c@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-d" onclick="selectStaff('staff-d', 'Payroll Staff D')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff D</div>
                                </div>
                                <div class="staff-email">staff.d@company.com</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State (shown by default) -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-user-group"></i>
                            <div class="sites-placeholder-title">Select a Staff Member</div>
                            <div class="sites-placeholder-text">Select a payroll staff member from the list to view and manage their site assignments.</div>
                        </div>

                        <!-- Site Assignments Content (hidden by default) -->
                        <div id="sitesContent" style="display: none;">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title" id="panelTitle">Assign Sites to Payroll Staff A</h4>
                                <p class="sites-panel-subtitle" id="panelSubtitle">1 active assignments</p>
                            </div>
                            <div id="sitesList">
                                <!-- Sites will be dynamically loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 
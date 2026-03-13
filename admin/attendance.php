<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippians CDO - Attendance Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/attendance.css">
     <script src="../js/attendance.js" defer></script>
</head>
<body>
    <main class="main-content">
        <header>
            <h1>Attendance Tracking</h1>
            <div class="user-controls">
                </div>
        </header>
        <section class="page-title-box">
            <h2 style="font-size: 18px;">Attendance Tracking</h2> 
            <div class="filter-row">
                <input type="date" value="2023-07-05">
                <input type="text" placeholder="Search by name or ID..." style="width: 250px;">
                <select><option>All Sites</option></select>
                <select><option>All Departments</option></select>
                <select><option>All Supervisors</option></select>
                <button class="export-btn"><i class="fa-solid fa-file-export"></i> Export</button>
            </div>
        </section>


        <div class="cards-grid" id="statsCards">
            <!-- Dynamic cards loaded from API -->
            <div class="loading-spinner">Loading sites...</div>
        </div>


        <div class="table-section">

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 id="tableSiteHeader" style="font-size: 14px;"><i class="fa-solid fa-city" style="color: #f39c12;"></i></h3>
                <span id="recordCount" style="font-size: 11px; background: #eee; padding: 4px 8px; border-radius: 4px;">0 records</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendance-body">

<!-- Dynamic table populated by JS -->

                </tbody>
            </table>
        </div>
    </main>

   
</body>
</html>
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

        <div class="cards-grid">
            <div class="project-card active">
                <h3><i class="fa-solid fa-city" style="color: #f39c12;"></i> Main Street Project</h3>
                <div class="total-count">6</div>
                <div class="status-mini-row">
                    <span>Present</span>
                    <div class="status-bar"><div class="fill-bar" style="width: 50%; background: var(--status-present);"></div></div>
                    <span>3</span>
                </div>
                <div class="status-mini-row">
                    <span>Late</span>
                    <div class="status-bar"><div class="fill-bar" style="width: 50%; background: var(--status-late);"></div></div>
                    <span>3</span>
                </div>
            </div>

            <div class="project-card">
                <h3><i class="fa-solid fa-building" style="color: #f39c12;"></i> Downtown Office</h3>
                <div class="total-count">4</div>
                <div class="status-mini-row">
                    <span>Present</span>
                    <div class="status-bar"><div class="fill-bar" style="width: 100%; background: var(--status-present);"></div></div>
                    <span>4</span>
                </div>
            </div>

            <div class="project-card">
                <h3><i class="fa-solid fa-house" style="color: #f39c12;"></i> Riverside Apts</h3>
                <div class="total-count">7</div>
                <div class="status-mini-row">
                    <span>Present</span>
                    <div class="status-bar"><div class="fill-bar" style="width: 70%; background: var(--status-present);"></div></div>
                    <span>5</span>
                </div>
            </div>
        </div>

        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 14px;"><i class="fa-solid fa-city" style="color: #f39c12;"></i> Main Street Project</h3>
                <span style="font-size: 11px; background: #eee; padding: 4px 8px; border-radius: 4px;">6 records</span>
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
                    <tr>
                        <td class="emp-info"><strong>John Doe</strong><small>ID: 001</small></td>
                        <td>2023-07-05</td>
                        <td>07:55 AM</td>
                        <td>05:05 PM</td>
                        <td><span class="status-pill present">Present</span></td>
                        <td>Construction</td>
                        <td class="action-btns"><i class="fa-regular fa-eye"></i> <i class="fa-solid fa-pencil"></i></td>
                    </tr>
                    <tr>
                        <td class="emp-info"><strong>Jane Smith</strong><small>ID: 002</small></td>
                        <td>2023-07-05</td>
                        <td>08:02 AM</td>
                        <td>05:00 PM</td>
                        <td><span class="status-pill late">Late</span></td>
                        <td>Electrical</td>
                        <td class="action-btns"><i class="fa-regular fa-eye"></i> <i class="fa-solid fa-pencil"></i></td>
                    </tr>
                    <tr>
                        <td class="emp-info"><strong>Robert Brown</strong><small>ID: 005</small></td>
                        <td>2023-07-05</td>
                        <td>08:15 AM</td>
                        <td>05:00 PM</td>
                        <td><span class="status-pill late">Late</span></td>
                        <td>Construction</td>
                        <td class="action-btns"><i class="fa-regular fa-eye"></i> <i class="fa-solid fa-pencil"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

   
</body>
</html>
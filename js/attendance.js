// Attendance System - Dynamic Backend Integration

class AttendanceSystem {
    constructor() {
        this.currentSiteId = null;
        this.currentDate = new Date().toISOString().split('T')[0];
        this.init();
    }

    async init() {
        await this.loadStats();
        this.bindEvents();
        this.updateDateFilter();
    }

    bindEvents() {
        // Dynamic card binding after load
        document.addEventListener('click', (e) => {
            if (e.target.closest('.project-card')) {
                this.loadSiteAttendance(e.target.closest('.project-card'));
            }
        });

        // Filters
        const dateInput = document.querySelector('input[type="date"]');
        if (dateInput) {
            dateInput.addEventListener('change', (e) => {
                this.currentDate = e.target.value;
                if (this.currentSiteId) {
                    this.loadSiteAttendance(document.querySelector('.project-card.active'));
                } else {
                    this.loadStats();
                }
            });
        }

        // Search
        const searchInput = document.querySelector('input[placeholder="Search by name or ID..."]');
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => this.searchAttendance(e.target.value));
        }
    }

    async loadStats() {
        try {
            const params = new URLSearchParams({date: this.currentDate});
            const response = await fetch(`../api/get_attendance_stats.php?${params}`);
            const result = await response.json();

            if (result.success) {
                this.populateCards(result.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    populateCards(stats) {
        const container = document.querySelector('.cards-grid');
        if (!container) return;

        container.innerHTML = '';

        stats.slice(0, 6).forEach(stat => {
            const card = document.createElement('div');
            card.className = 'project-card';
            card.dataset.siteId = stat.SiteID;
            card.innerHTML = `
                <h3><i class="fa-solid fa-city" style="color: #f39c12;"></i> ${stat.Site_Name}</h3>
                <div class="total-count">${stat.total_workers}</div>
                <div class="status-mini-row">
                    <span>Present</span>
                    <div class="status-bar"><div class="fill-bar" style="width: ${stat.total_workers > 0 ? Math.round((stat.present_count/stat.total_workers)*100) : 0}%; background: var(--status-present);"></div></div>
                    <span>${stat.present_count}</span>
                </div>
                <div class="status-mini-row">
                    <span>Late</span>
                    <div class="status-bar"><div class="fill-bar" style="width: ${stat.total_workers > 0 ? Math.round((stat.late_count/stat.total_workers)*100) : 0}%; background: var(--status-late);"></div></div>
                    <span>${stat.late_count}</span>
                </div>
            `;
            container.appendChild(card);
        });
    }

    async loadSiteAttendance(card) {
        document.querySelectorAll('.project-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        this.currentSiteId = parseInt(card.dataset.siteId);
        
        try {
            const params = new URLSearchParams({
                site_id: this.currentSiteId,
                date: this.currentDate
            });
            const response = await fetch(`../api/get_attendance.php?${params}`);
            const result = await response.json();

            if (result.success) {
                this.displayAttendance(result.data);
            }
        } catch (error) {
            console.error('Failed to load attendance:', error);
        }
    }

    displayAttendance(records) {
        const tbody = document.getElementById('attendance-body');
        if (!tbody) return;

        tbody.innerHTML = '';

        records.forEach(record => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td class="emp-info">
                    <strong>${record.First_Name || ''} ${record.Last_Name || ''}</strong>
                    <small>ID: ${record.ID || record.WorkerID}</small>
                </td>
                <td>${this.currentDate}</td>
                <td>${record.Time_In || '--:--'}</td>
                <td>${record.Time_Out || '--:--'}</td>
                <td><span class="status-pill ${record.AttendanceStatus?.toLowerCase() || 'absent'}">${record.AttendanceStatus || 'Absent'}</span></td>
                <td>${record.department || 'N/A'}</td>
                <td class="action-btns">
                    ${!record.Time_In ? `<i class="fa-solid fa-play-circle" title="Clock In" onclick="attendanceSystem.clockIn(${record.WorkerID || record.ID})" style="cursor:pointer; color:#27AE60; font-size:1.2em;"></i>` : ''}
                    ${record.Time_In && !record.Time_Out ? `<i class="fa-solid fa-stop-circle" title="Clock Out" onclick="attendanceSystem.clockOut(${record.AttendanceID})" style="cursor:pointer; color:#E74C3C; font-size:1.2em;"></i>` : ''}
                    <i class="fa-regular fa-eye" title="View" style="cursor:pointer;"></i>
                    <i class="fa-solid fa-pencil" title="Edit" onclick="editAttendance(${record.AttendanceID})" style="cursor:pointer;"></i>
                </td>
            `;
        });
    }

    async clockIn(workerId) {
        try {
            const response = await fetch('../api/clock_in.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({worker_id: workerId, site_id: this.currentSiteId})
            });
            const result = await response.json();
            
            if (result.success) {
                alert(`Clocked in successfully at ${result.time_in}`);
                this.loadSiteAttendance(document.querySelector('.project-card.active'));
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Clock in failed: ' + error.message);
        }
    }

    async clockOut(attendanceId) {
        try {
            const response = await fetch('../api/clock_out.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({attendance_id: attendanceId})
            });
            const result = await response.json();
            
            if (result.success) {
                alert(`Clocked out: ${result.hours_worked}h (${result.status})`);
                this.loadSiteAttendance(document.querySelector('.project-card.active'));
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Clock out failed: ' + error.message);
        }
    }

    searchAttendance(term) {
        const rows = document.querySelectorAll('#attendance-body tr');
        const lowerTerm = term.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lowerTerm) ? '' : 'none';
        });
    }

    updateDateFilter() {
        const dateInput = document.querySelector('input[type="date"]');
        if (dateInput) dateInput.value = this.currentDate;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.attendanceSystem = new AttendanceSystem();
    });
} else {
    window.attendanceSystem = new AttendanceSystem();
}


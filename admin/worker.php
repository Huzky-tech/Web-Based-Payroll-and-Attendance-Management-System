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

    <script>
        const workers = [
            {
                name: 'John Smith',
                initials: 'JS',
                role: 'Construction Worker',
                status: 'active',
                site: 'Road Street Site',
                email: 'john.smith@example.com',
                phone: '+1 234-567-8901',
                joined: '2023-01-15',
                attendance: 95,
                color: 'gold'
            },
            {
                name: 'Sarah Johnson',
                initials: 'SJ',
                role: 'Foreman',
                status: 'active',
                site: 'Building Construction Site',
                email: 'sarah.j@example.com',
                phone: '+1 234-567-8902',
                joined: '2022-11-20',
                attendance: 98,
                color: 'yellow'
            },
            {
                name: 'Mike Brown',
                initials: 'MB',
                role: 'Equipment Operator',
                status: 'onleave',
                site: 'Road Street Site',
                email: 'mike.b@example.com',
                phone: '+1 234-567-8903',
                joined: '2023-03-10',
                attendance: 92,
                color: 'gold'
            },
            {
                name: 'Ava Miller',
                initials: 'AM',
                role: 'Safety Officer',
                status: 'active',
                site: 'Bridge Project Alpha',
                email: 'ava.m@example.com',
                phone: '+1 234-567-8904',
                joined: '2022-08-05',
                attendance: 96,
                color: 'blue'
            }
        ];

        const workerGrid = document.getElementById('workerGrid');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const siteFilter = document.getElementById('siteFilter');

        const modal = document.getElementById('workerModal');
        const btnAddWorker = document.getElementById('btnAddWorker');
        const closeModal = document.getElementById('closeModal');
        const cancelModal = document.getElementById('cancelModal');
        const workerForm = document.getElementById('workerForm');

        function updateDateTime() {
            const now = new Date();
            const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const day = days[now.getDay()];
            const month = months[now.getMonth()];
            const date = now.getDate();
            const year = now.getFullYear();
            let hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const minutesStr = minutes < 10 ? '0' + minutes : minutes;
            document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
            document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        function renderSites() {
            const uniqueSites = Array.from(new Set(workers.map(w => w.site)));
            siteFilter.innerHTML = '<option value="all">All Sites</option>' + uniqueSites.map(s => `<option value="${s}">${s}</option>`).join('');
        }

        function statusClass(status) {
            if (status === 'active') return 'status-pill status-active';
            if (status === 'onleave') return 'status-pill status-onleave';
            return 'status-pill status-inactive';
        }

        function renderWorkers() {
            const query = searchInput.value.toLowerCase();
            const statusVal = statusFilter.value;
            const siteVal = siteFilter.value;

            workerGrid.innerHTML = '';
            const filtered = workers.filter(w => {
                const matchesText = w.name.toLowerCase().includes(query) || w.email.toLowerCase().includes(query) || w.role.toLowerCase().includes(query);
                const matchesStatus = statusVal === 'all' || w.status === statusVal;
                const matchesSite = siteVal === 'all' || w.site === siteVal;
                return matchesText && matchesStatus && matchesSite;
            });

            filtered.forEach(w => {
                const card = document.createElement('div');
                card.className = 'worker-card';
                card.innerHTML = `
                    <div class="worker-header">
                        <div class="avatar ${w.color}">${w.initials}</div>
                        <div>
                            <div class="worker-name">${w.name}</div>
                            <div class="worker-role">${w.role}</div>
                        </div>
                        <span class="${statusClass(w.status)}">${w.status === 'onleave' ? 'on leave' : w.status}</span>
                    </div>
                    <div class="info-row"><i class="fas fa-location-dot"></i><span>${w.site}</span></div>
                    <div class="info-row"><i class="fas fa-envelope"></i><span>${w.email}</span></div>
                    <div class="info-row"><i class="fas fa-phone"></i><span>${w.phone}</span></div>
                    <div class="info-row"><i class="fas fa-calendar"></i><span>Joined ${w.joined}</span></div>
                    <div class="attendance">
                        <span>Attendance Rate</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:${w.attendance}%"></div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="btn-ghost"><i class="fas fa-eye"></i>View</button>
                        <button class="btn-edit"><i class="fas fa-pen"></i>Edit</button>
                    </div>
                `;
                workerGrid.appendChild(card);
            });

            updateSummary(filtered);
        }

        function updateSummary(list) {
            const total = list.length;
            const active = list.filter(w => w.status === 'active').length;
            const onleave = list.filter(w => w.status === 'onleave').length;
            const inactive = list.filter(w => w.status === 'inactive').length;

            document.querySelector('[data-summary="total"]').textContent = total;
            document.querySelector('[data-summary="active"]').textContent = active;
            document.querySelector('[data-summary="onleave"]').textContent = onleave;
            document.querySelector('[data-summary="inactive"]').textContent = inactive;
        }

        function openModal() { modal.classList.add('active'); }
        function closeModalFunc() {
            modal.classList.remove('active');
            workerForm.reset();
            document.getElementById('workerStatus').value = 'active';
        }

        btnAddWorker.addEventListener('click', openModal);
        closeModal.addEventListener('click', closeModalFunc);
        cancelModal.addEventListener('click', closeModalFunc);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModalFunc(); });

        workerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('workerName').value.trim();
            const role = document.getElementById('workerRole').value.trim();
            const site = document.getElementById('workerSite').value.trim();
            const email = document.getElementById('workerEmail').value.trim();
            const phone = document.getElementById('workerPhone').value.trim();
            const joined = document.getElementById('workerJoin').value || 'Not set';
            const status = document.getElementById('workerStatus').value;
            if (!name || !role || !site || !email || !phone) return;

            const initials = name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
            workers.push({
                name,
                initials,
                role,
                status,
                site,
                email,
                phone,
                joined,
                attendance: 90,
                color: 'blue'
            });
            renderSites();
            renderWorkers();
            closeModalFunc();
        });

        searchInput.addEventListener('input', renderWorkers);
        statusFilter.addEventListener('change', renderWorkers);
        siteFilter.addEventListener('change', renderWorkers);

        renderSites();
        renderWorkers();
    </script>
</body>
</html>

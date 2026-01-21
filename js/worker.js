
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
   
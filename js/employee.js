// Employee Management JavaScript - Fully Safe Version

const API_BASE = '../api/';
let currentUserId = 1; // Replace with actual session user ID
let selectedStaffId = null;

// Update date and time
function updateDateTime() {
    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
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

    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (dateEl) dateEl.textContent = `${day}, ${month} ${date}, ${year}`;
    if (timeEl) timeEl.textContent = `${hours}:${minutesStr} ${ampm}`;
}

// Status class helper
function getStatusClass(status) {
    switch(status) {
        case 'Active': return 'active';
        case 'Inactive': return 'inactive';
        case 'OnLeave': return 'on-leave';
        default: return 'active';
    }
}

// Render employee table
function renderEmployeeTable(employees) {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!employees || employees.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px;">
            No employees found. Click "Add New Employee" to create one.
        </td></tr>`;
        return;
    }

    employees.forEach(employee => {
        const statusClass = getStatusClass(employee.worker_status);
        const statusText = employee.worker_status || 'Active';
        const firstName = employee.First_Name || '';
        const initial = firstName ? firstName.charAt(0).toUpperCase() : 'E';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="employee-info">
                    <div class="employee-avatar">${initial}</div>
                    <div class="employee-details">
                        <div class="employee-name">${employee.full_name || employee.First_Name + ' ' + employee.Last_Name}</div>
                        <div class="employee-id">ID: ${employee.WorkerID}</div>
                    </div>
                </div>
            </td>
            <td>${employee.position || 'Not Assigned'}</td>
            <td>${employee.Site_Name || 'Not Assigned'}</td>
            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            <td class="salary">₱${parseFloat(employee.salary || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
            <td>${employee.join_date || 'N/A'}</td>
            <td>
                <div class="approval-info">
                    <span class="approval-badge">Pending</span>
                    <span class="approval-by">Not approved</span>
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action view" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action delete" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Load employees
async function loadEmployees() {
    try {
        const res = await fetch(API_BASE + 'get_employees.php');
        const data = await res.json();
        if (data.success) renderEmployeeTable(data.employees);
    } catch(err) {
        console.error('Error loading employees:', err);
    }
}

// Search/filter
async function searchEmployees(term) { term ? filterBySearch(term) : loadEmployees(); }
async function filterBySite(siteId) { siteId ? filterBySiteAPI(siteId) : loadEmployees(); }
async function filterByStatus(status) { status ? filterByStatusAPI(status) : loadEmployees(); }

// View employee
async function viewEmployee(id) {
    try {
        const res = await fetch(API_BASE + 'get_employee.php?id=' + id);
        const data = await res.json();
        if (data.success) {
            const emp = data.employee;
            alert(`Name: ${emp.full_name}\nPosition: ${emp.position || 'N/A'}\nSite: ${emp.Site_Name || 'N/A'}\nStatus: ${emp.worker_status}\nSalary: ₱${parseFloat(emp.salary || 0).toLocaleString()}\nJoin: ${emp.join_date}\nPhone: ${emp.phone || 'N/A'}`);
        } else alert('Failed: ' + data.message);
    } catch(err) { console.error(err); }
}

// Delete employee
async function deleteEmployee(id) {
    if (!confirm('Delete this employee?')) return;
    try {
        const res = await fetch(API_BASE + 'delete_employee.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id,user_id:currentUserId})
        });
        const data = await res.json();
        if (data.success) { alert('Deleted'); location.reload(); } 
        else alert('Failed: ' + data.message);
    } catch(err){ console.error(err); }
}

// Load dashboard stats
async function loadDashboardStats() {
    try {
        const res = await fetch(API_BASE + 'get_dashboard_stats.php');
        const data = await res.json();
        if (!data.success) return;
        const stats = data.statistics;
        const staffCountEl = document.getElementById('staffCount');
        const activeSitesEl = document.getElementById('activeSitesCount');
        const assignmentsEl = document.getElementById('assignmentsCount');
        if (staffCountEl) staffCountEl.textContent = stats.payroll_staff || 0;
        if (activeSitesEl) activeSitesEl.textContent = stats.active_sites || 0;
        if (assignmentsEl) assignmentsEl.textContent = stats.total_assignments || 0;
    } catch(err){ console.error(err); }
}

// Staff selection
async function selectStaff(el, id) {
    selectedStaffId = id;
    document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    const name = el.querySelector('.staff-name')?.textContent;
    const titleEl = document.querySelector('.sites-panel-title');
    if (titleEl) titleEl.textContent = `Assign Sites to ${name}`;
}

// Assign site
async function assignSiteToStaff(siteId) {
    if (!selectedStaffId) { 
        alert('Please select a worker first!');
        return; 
    }
    try {
        const res = await fetch(API_BASE + 'assign_worker.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ workerId: selectedStaffId, siteId: siteId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Worker assigned successfully!');
            location.reload();  // Refresh to update counts
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) {
        console.error(err);
        alert('Network error. Try again.');
    }
}

// Modal functions
function showAddEmployeeModal() {
    const modal = document.getElementById('addEmployeeModal');
    if (modal) { modal.classList.add('active'); const form = document.getElementById('addEmployeeForm'); if(form) form.reset(); }
}
function closeModal() { const modal = document.getElementById('addEmployeeModal'); if(modal) modal.classList.remove('active'); }

// Form submission handler
async function handleAddEmployeeSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('user_id', currentUserId);
    
    try {
        const response = await fetch(API_BASE + 'create_employee.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Employee added successfully!');
            closeModal();
            loadEmployees(); // Refresh table
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Submit error:', error);
        alert('Network error. Please try again.');
    }
}

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready - handlers attached');

    // Expose functions globally for onclick handlers
    window.selectStaff = selectStaff;
    window.assignSiteToStaff = assignSiteToStaff;

    const btnAddEmployee = document.getElementById('btnAddEmployee');
    const addEmployeeModal = document.getElementById('addEmployeeModal');
    const closeModalBtn = document.querySelector('.close-modal');

    if (btnAddEmployee && addEmployeeModal) {
        btnAddEmployee.addEventListener('click', () => {
            addEmployeeModal.classList.add('active');
            const form = document.getElementById('addEmployeeForm');
            if (form) form.reset();
        });
    }

    if (closeModalBtn && addEmployeeModal) {
        closeModalBtn.addEventListener('click', () => {
            addEmployeeModal.classList.remove('active');
        });
    }

    // Close modal when clicking outside content
    if (addEmployeeModal) {
        addEmployeeModal.addEventListener('click', e => {
            if (e.target === addEmployeeModal) {
                addEmployeeModal.classList.remove('active');
            }
        });
    }

    updateDateTime();
    setInterval(updateDateTime, 60000);

    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            if(tabName==='employees'){
                document.getElementById('employeesTab')?.classList.add('active');
                document.getElementById('btnAddEmployee')?.style.setProperty('display','flex');
                loadEmployees();
            } else {
                document.getElementById('assignmentsTab')?.classList.add('active');
                document.getElementById('btnAddEmployee')?.style.setProperty('display','none');
                loadDashboardStats();
            }
        });
    });

    // Add Employee button
    document.getElementById('btnAddEmployee')?.addEventListener('click', e => { e.preventDefault(); showAddEmployeeModal(); });

    // Modal close
    document.querySelector('.close-modal')?.addEventListener('click', closeModal);
    document.getElementById('addEmployeeModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

    // Form submission
    const addEmployeeForm = document.getElementById('addEmployeeForm');
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', handleAddEmployeeSubmit);
    }

    // Staff selection listeners
    document.querySelectorAll('.staff-item').forEach(item => {
        item.addEventListener('click', function() {
            const staffId = parseInt(this.dataset.staff);
            if(!isNaN(staffId)) selectStaff(this, staffId);
        });
    });

    // Site buttons listeners
    document.querySelectorAll('.site-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const siteItem = this.closest('.site-item');
            const siteId = siteItem?.dataset.siteId || parseInt(this.dataset.siteId);
            if(siteId) assignSiteToStaff(siteId);
        });
    });

    // Dynamic action buttons (view/delete)
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-action');
        if(!btn) return;
        const row = btn.closest('tr');
        if(!row) return;
        const idMatch = row.querySelector('.employee-id')?.textContent.match(/ID: (\d+)/);
        if(!idMatch) return;
        const empId = parseInt(idMatch[1]);
        if(btn.classList.contains('view')) viewEmployee(empId);
        else if(btn.classList.contains('delete')) deleteEmployee(empId);
    });
});

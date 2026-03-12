// Employee Management JavaScript
// Handles employee CRUD operations and interactions

// API Base URL
const API_BASE = '../api/';

// Current user ID (would typically come from session)
let currentUserId = 1;

// Update date and time
function updateDateTime() {
    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();
    
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;
    
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (dateEl) dateEl.textContent = `${day}, ${month} ${date}, ${year}`;
    if (timeEl) timeEl.textContent = `${hours}:${minutesStr} ${ampm}`;
}

// Update time every minute
updateDateTime();
setInterval(updateDateTime, 60000);

// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        // Update tab active state
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Show/hide tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        if (tabName === 'employees') {
            document.getElementById('employeesTab').classList.add('active');
            const btnAdd = document.getElementById('btnAddEmployee');
            if (btnAdd) btnAdd.style.display = 'flex';
        } else if (tabName === 'assignments') {
            document.getElementById('assignmentsTab').classList.add('active');
            const btnAdd = document.getElementById('btnAddEmployee');
            if (btnAdd) btnAdd.style.display = 'none';
            loadDashboardStats();
        }
    });
});

// Search employees
async function searchEmployees(searchTerm) {
    if (!searchTerm || searchTerm.trim().length === 0) {
        loadEmployees();
        return;
    }
    
    try {
        const response = await fetch(API_BASE + 'search_employees.php?search=' + encodeURIComponent(searchTerm));
        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.employees);
        }
    } catch (error) {
        console.error('Error searching employees:', error);
    }
}

// Filter by site
async function filterBySite(siteId) {
    if (!siteId) {
        loadEmployees();
        return;
    }
    
    try {
        const response = await fetch(API_BASE + 'filter_employees_by_site.php?site_id=' + siteId);
        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.employees);
        }
    } catch (error) {
        console.error('Error filtering employees:', error);
    }
}

// Filter by status
async function filterByStatus(status) {
    if (!status) {
        loadEmployees();
        return;
    }
    
    try {
        const response = await fetch(API_BASE + 'filter_employees_by_status.php?status=' + status);
        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.employees);
        }
    } catch (error) {
        console.error('Error filtering employees:', error);
    }
}

// Load employees from API
async function loadEmployees() {
    try {
        const response = await fetch(API_BASE + 'get_employees.php');
        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.employees);
        }
    } catch (error) {
        console.error('Error loading employees:', error);
    }
}

// Render employee table
function renderEmployeeTable(employees) {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!employees || employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No employees found. Click "Add New Employee" to create one.</td></tr>';
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
            <td class="salary">₱${parseFloat(employee.salary || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td>${employee.join_date || 'N/A'}</td>
            <td>
                <div class="approval-info">
                    <span class="approval-badge">Pending</span>
                    <span class="approval-by">Not approved</span>
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action view" title="View" onclick="viewEmployee(${employee.WorkerID})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action delete" title="Delete" onclick="deleteEmployee(${employee.WorkerID})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Get status class
function getStatusClass(status) {
    switch(status) {
        case 'Active':
            return 'active';
        case 'Inactive':
            return 'inactive';
        case 'OnLeave':
            return 'on-leave';
        default:
            return 'active';
    }
}

// View employee details
async function viewEmployee(employeeId) {
    try {
        const response = await fetch(API_BASE + 'get_employee.php?id=' + employeeId);
        const data = await response.json();
        
        if (data.success) {
            const emp = data.employee;
            alert(`Employee Details:\n\nName: ${emp.full_name}\nPosition: ${emp.position || 'Not Assigned'}\nSite: ${emp.Site_Name || 'Not Assigned'}\nStatus: ${emp.worker_status}\nSalary: ₱${parseFloat(emp.salary || 0).toLocaleString()}\nJoin Date: ${emp.join_date}\nPhone: ${emp.phone || 'N/A'}`);
        } else {
            alert('Failed to get employee: ' + data.message);
        }
    } catch (error) {
        console.error('Error viewing employee:', error);
    }
}

// Delete employee
async function deleteEmployee(employeeId) {
    if (!confirm('Are you sure you want to delete this employee?')) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE + 'delete_employee.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: employeeId,
                user_id: currentUserId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Employee deleted successfully');
            // Reload the page to refresh data
            location.reload();
        } else {
            alert('Failed to delete employee: ' + data.message);
        }
    } catch (error) {
        console.error('Error deleting employee:', error);
    }
}

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch(API_BASE + 'get_dashboard_stats.php');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.statistics;
            const staffCountEl = document.getElementById('staffCount');
            const activeSitesEl = document.getElementById('activeSitesCount');
            const assignmentsEl = document.getElementById('assignmentsCount');
            
            if (staffCountEl) staffCountEl.textContent = stats.payroll_staff || 0;
            if (activeSitesEl) activeSitesEl.textContent = stats.active_sites || 0;
            if (assignmentsEl) assignmentsEl.textContent = stats.total_assignments || 0;
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Select staff member
let selectedStaffId = null;

async function selectStaff(element, staffId) {
    selectedStaffId = staffId;
    
    document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
    element.classList.add('selected');
    
    const staffName = element.querySelector('.staff-name').textContent;
    const titleEl = document.querySelector('.sites-panel-title');
    if (titleEl) {
        titleEl.textContent = `Assign Sites to ${staffName}`;
    }
    
    // Load assigned sites
    try {
        const response = await fetch(API_BASE + 'get_staff_sites.php?payroll_staff_id=' + staffId);
        const data = await response.json();
        
        if (data.success) {
            console.log('Staff sites:', data.sites);
        }
    } catch (error) {
        console.error('Error loading staff sites:', error);
    }
}

// Assign site to staff
async function assignSiteToStaff(siteId) {
    if (!selectedStaffId) {
        alert('Please select a staff member first');
        return;
    }
    
    try {
        const response = await fetch(API_BASE + 'assign_site_to_staff.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                payroll_staff_id: selectedStaffId,
                site_id: siteId,
                user_id: currentUserId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Site assigned successfully');
            loadDashboardStats();
        } else {
            alert('Failed to assign site: ' + data.message);
        }
    } catch (error) {
        console.error('Error assigning site:', error);
    }
}

// Add New Employee button handler
const btnAddEmployee = document.getElementById('btnAddEmployee');
if (btnAddEmployee) {
    btnAddEmployee.addEventListener('click', function() {
        showAddEmployeeModal();
    });
}

// Show add employee modal
function showAddEmployeeModal() {
    const modal = document.getElementById('addEmployeeModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Close modal
function closeModal() {
    const modal = document.getElementById('addEmployeeModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addEmployeeModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
};

// Handle form submission
const addEmployeeForm = document.getElementById('addEmployeeForm');
if (addEmployeeForm) {
    addEmployeeForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            position: formData.get('position'),
            salary: parseFloat(formData.get('salary')) || 0,
            phone: formData.get('phone'),
            join_date: formData.get('join_date'),
            user_id: currentUserId
        };
        
        try {
            const response = await fetch(API_BASE + 'create_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Employee added successfully!');
                closeModal();
                // Reload to show new employee
                location.reload();
            } else {
                alert('Failed to add employee: ' + result.message);
            }
        } catch (error) {
            console.error('Error adding employee:', error);
            alert('Error adding employee');
        }
    });
}

// Staff selection (for existing hardcoded items)
document.querySelectorAll('.staff-item').forEach(item => {
    item.addEventListener('click', function() {
        const staffId = this.getAttribute('data-staff');
        if (staffId && !isNaN(staffId)) {
            selectStaff(this, parseInt(staffId));
        }
        
        document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        
        const staffName = this.querySelector('.staff-name').textContent;
        const titleEl = document.querySelector('.sites-panel-title');
        if (titleEl) {
            titleEl.textContent = `Assign Sites to ${staffName}`;
        }
    });
});

// Site action buttons
document.querySelectorAll('.site-action').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const action = this.classList.contains('remove') ? 'Remove' : 'Add';
        const siteItem = this.closest('.site-item');
        if (!siteItem) return;
        
        const siteName = siteItem.querySelector('.site-name').textContent;
        console.log(`${action} clicked for ${siteName}`);
    });
});

// Action buttons (for dynamically added rows)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-action');
    if (!btn) return;
    
    if (btn.classList.contains('view')) {
        const row = btn.closest('tr');
        if (!row) return;
        const employeeIdMatch = row.querySelector('.employee-id')?.textContent.match(/ID: (\d+)/);
        if (employeeIdMatch) {
            viewEmployee(parseInt(employeeIdMatch[1]));
        }
    } else if (btn.classList.contains('delete')) {
        const row = btn.closest('tr');
        if (!row) return;
        const employeeIdMatch = row.querySelector('.employee-id')?.textContent.match(/ID: (\d+)/);
        if (employeeIdMatch) {
            deleteEmployee(parseInt(employeeIdMatch[1]));
        }
    }
});

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize - the data is already rendered by PHP
// Only add JavaScript interactivity
console.log('Employee Management initialized');


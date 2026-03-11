
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
            
            document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
            document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
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
                    document.getElementById('btnAddEmployee').style.display = 'flex';
                } else if (tabName === 'assignments') {
                    document.getElementById('assignmentsTab').classList.add('active');
                    document.getElementById('btnAddEmployee').style.display = 'none';
                }
            });
        });

        // Add New Employee button
        document.querySelector('.btn-add').addEventListener('click', function() {
            console.log('Add New Employee clicked');
            // Add your modal or navigation logic here
        });

        // Staff selection
        document.querySelectorAll('.staff-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                
                const staffName = this.querySelector('.staff-name').textContent;
                const staffEmail = this.querySelector('.staff-email').textContent;
                
                // Update right panel header
                document.querySelector('.sites-panel-title').textContent = `Assign Sites to ${staffName}`;
                
                // You can update the sites list here based on selected staff
                console.log(`Selected: ${staffName} (${staffEmail})`);
            });
        });

        // Site action buttons
        document.querySelectorAll('.site-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const action = this.classList.contains('remove') ? 'Remove' : 'Add';
                const siteName = this.closest('.site-item').querySelector('.site-name').textContent;
                console.log(`${action} clicked for ${siteName}`);
                // Add your action logic here
            });
        });

        // Action buttons
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.classList.contains('view') ? 'View' : 'Delete';
                const row = this.closest('tr');
                const employeeName = row.querySelector('.employee-name').textContent;
                console.log(`${action} clicked for ${employeeName}`);
                // Add your action logic here
            });
        });
  

        document.addEventListener('DOMContentLoaded', () => {
            // -- View Profile Modal Logic --
            const viewModal = document.getElementById('employeeModal');
            const viewBtns = document.querySelectorAll('.btn-view-employee');
            const closeViewBtn = document.getElementById('closeViewModalBtn');

            viewBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    viewModal.classList.add('show');
                });
            });

            closeViewBtn.addEventListener('click', () => {
                viewModal.classList.remove('show');
            });

            // -- Add New Employee Modal Logic --
            const addModal = document.getElementById('addEmployeeModal');
            const openAddBtn = document.getElementById('openAddModalBtn');
            const closeAddBtn = document.getElementById('closeAddModalBtn');
            const cancelAddBtn = document.getElementById('cancelAddBtn');

            openAddBtn.addEventListener('click', () => {
                addModal.classList.add('show');
            });

            const closeAddModal = () => {
                addModal.classList.remove('show');
            };

            closeAddBtn.addEventListener('click', closeAddModal);
            cancelAddBtn.addEventListener('click', closeAddModal);

            // -- NEW: Edit Profile Modal Logic --
            const editModal = document.getElementById('editProfileModal');
            const closeEditBtn = document.getElementById('closeEditModalBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const saveEditBtn = document.getElementById('saveEditBtn');
            
            // This selects all the little orange pencil buttons AND the "Edit Profile" button at the top
            const allEditButtons = document.querySelectorAll('.btn-action.edit, .header-actions .btn-secondary');

            allEditButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Open the edit modal over the profile modal
                    editModal.classList.add('show'); 
                });
            });

            const closeEditModal = () => {
                editModal.classList.remove('show');
            };

            closeEditBtn.addEventListener('click', closeEditModal);
            cancelEditBtn.addEventListener('click', closeEditModal);
            
            saveEditBtn.addEventListener('click', () => {
                // Here is where you would normally save to a database.
                alert('Changes saved successfully!');
                closeEditModal();
            });

            // -- Global Click to Close Modals --
            window.addEventListener('click', (e) => {
                if (e.target === viewModal) {
                    viewModal.classList.remove('show');
                }
                if (e.target === addModal) {
                    addModal.classList.remove('show');
                }
                if (e.target === editModal) {
                    editModal.classList.remove('show');
                }
            });

            // -- Tabs Logic Inside View Modal --
            const tabItems = document.querySelectorAll('.modal-nav-item');
            const tabContents = document.querySelectorAll('.modal-tab-content');

            tabItems.forEach(item => {
                item.addEventListener('click', () => {
                    tabItems.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    item.classList.add('active');

                    const targetId = item.getAttribute('data-target');
                    document.getElementById(targetId).classList.add('active');
                });
            });
        });
  
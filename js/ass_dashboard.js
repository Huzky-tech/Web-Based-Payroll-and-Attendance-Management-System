
        // Active states on sidebar
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {

                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            });
        });


        // Modal Handling
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            // Prevent scrolling on body when modal is open
            document.body.style.overflow = 'hidden'; 
        }

        function closeModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) {
                return;
            }
            modalEl.style.display = 'none';
            // Restore scrolling
            
            document.body.style.overflow = 'hidden'; // Keep body hidden as per original CSS
        }


        // Close modal when clicking outside the container
        window.onclick = function(event) {
            if (event.target.className === 'modal-overlay') {
                event.target.style.display = "none";
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const dropdown = document.getElementById('userProfileDropdown');
            const toggle = document.getElementById('userProfileToggle');
            const menu = document.getElementById('userProfileMenu');

            if (!dropdown || !toggle || !menu) return;

            // dashboard.js owns the shared profile dropdown when it is present.
            // Do not add another click handler for the same toggle.
            if (toggle.dataset.profileDropdownBound === 'true') return;
            toggle.dataset.profileDropdownBound = 'true';

            function setOpen(isOpen) {
                menu.style.display = isOpen ? 'block' : 'none';
                toggle.setAttribute('aria-expanded', String(isOpen));
            }

            setOpen(false);

            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = menu.style.display === 'block';
                setOpen(!isOpen);
            });

            document.addEventListener('click', () => setOpen(false));
            menu.addEventListener('click', () => setOpen(false));
        });
    
    

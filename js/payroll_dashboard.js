
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

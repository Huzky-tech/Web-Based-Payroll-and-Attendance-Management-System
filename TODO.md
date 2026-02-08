# TODO: Fix Issues in js/active_site.js

- [x] Move startDateEl.valueAsDate = new Date() inside btnAddSite click handler
- [x] Fix closeModalFunc to get startDateEl inside function
- [x] Add check for siteCards existence in loadSites before clearing innerHTML
- [x] Wrap fetch in loadSites with try/catch and error handling
- [x] Replace console.log in View Details button with redirect to site_details.php
- [x] Replace console.log in Manage button with redirect to site_manage.php
- [x] Verify form validation trims all inputs (already done, confirm)
- [x] Fix data name mismatch: change site.currentWorkers to site.Current_Workers

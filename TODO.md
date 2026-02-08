# Dashboard Backend Refactor TODO

- [x] Create new API endpoint `api/get_dashboard_summary.php` to aggregate and return JSON summary data
- [x] Update `js/dashboard.js` to fetch from new API and populate UI without calculations
- [x] Remove hardcoded values from `admin/dashboard.php` and add placeholders for dynamic data
- [ ] Test the new API endpoint for correct aggregated data
- [ ] Verify frontend displays data correctly without errors

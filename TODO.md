# JS and PHP/HTML Separation Tasks

## Files to Process
- [x] index.php - Move inline JS to js/index.js, handle PHP logic
- [x] admin/dashboard.php - Move inline JS to js/dashboard.js
- [x] admin/employee.php - Move inline JS to js/employee.js
- [x] admin/worker.php - Move inline JS to js/worker.js
- [x] admin/site_assign.php - Move inline JS to js/site_assign.js
- [x] admin/setting.php - Move inline JS to js/setting.js
- [x] admin/audit.php - Move inline JS to js/audit.js
- [x] users/payroll.php - Move inline JS to js/payroll.js

## Steps for Each File
1. Extract inline JavaScript code
2. Create corresponding JS file in js/ directory
3. Move JS code to the new file
4. Update PHP file to include external JS file
5. Test functionality

## Special Cases
- index.php: Handle PHP variable ($new_user) in JS logic
- Ensure all event listeners and DOM manipulations work after separation

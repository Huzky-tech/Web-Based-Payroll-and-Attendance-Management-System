# TODO: Implement Backend for admin/setting.php

## Database Updates
- [x] Update database/payroll_db.sql: Extend users table with full_name, role, status, last_login columns.
- [x] Add company_settings table.
- [x] Add payroll_settings table.
- [x] Add notification_settings table.
- [x] Add security_settings table.
- [x] Add system_settings table.
- [x] Insert default data into new tables.

## API Endpoints Creation
- [x] Create api/get_company_settings.php
- [x] Create api/update_company_settings.php (handle logo upload)
- [x] Create api/get_payroll_settings.php
- [x] Create api/update_payroll_settings.php
- [x] Create api/get_users.php
- [x] Create api/add_user.php
- [x] Create api/edit_user.php
- [x] Create api/delete_user.php
- [x] Create api/get_notification_settings.php
- [x] Create api/update_notification_settings.php
- [x] Create api/get_security_settings.php
- [x] Create api/update_security_settings.php
- [x] Create api/get_system_settings.php
- [x] Create api/update_system_settings.php
- [x] Create api/force_password_reset.php
- [x] Create api/terminate_sessions.php
- [x] Create api/backup_system.php
- [x] Create api/clear_cache.php
- [x] Create api/reset_settings.php

## Frontend Updates
- [x] Modify admin/setting.php: Add PHP to load initial company info and users list on page load.
- [x] Update js/setting.js: Add AJAX calls for loading data on page load and tab switch, handle save operations, logo upload.

## Additional Setup
- [x] Create uploads/ directory for logo storage.
- [x] Test database updates.
- [ ] Test API endpoints individually.
- [ ] Test frontend-backend integration.
- [ ] Handle errors and edge cases.

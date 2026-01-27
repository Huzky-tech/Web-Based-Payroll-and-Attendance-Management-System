# TODO: Implement User Login Functionality

## Steps to Complete
- [x] Modify index.php to include PHP backend for login processing
- [x] Add database connection code to connect to payroll_db
- [x] Implement form submission handling (POST request)
- [x] Add user authentication logic: query users table, verify email and hashed password
- [x] Start session on successful login and redirect to admin/dashboard.php
- [x] Handle login errors (invalid credentials) and display appropriate messages
- [x] Update form to use method="post"
- [x] Modify JavaScript to allow form submission on valid input
- [x] Create setup_db.php script to initialize database and default user
- [x] Run setup_db.php to ensure database is set up
- [x] Test the login functionality to ensure it works correctly

## Notes
- Database already exists with users table in database/payroll_db.sql
- Passwords are hashed using password_hash, so use password_verify for checking
- Ensure session management for logged-in users

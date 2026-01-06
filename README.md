# hrms

## Installation Instructions

### 1. Preparations
- Install **XAMPP** (or any LAMP stack with PHP 8.x and MySQL).
- Move the `hrms` folder into your `htdocs` directory.

### 2. Database Setup
- Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
- Create a new database named `hrms_db`.
- Select the `hrms_db` database and go to the **Import** tab.
- Choose the file located at `/sql/database_setup.sql`.
- Click **Go** to execute the setup.

### 3. Accessing the System
- **First Time Setup / Recovery**: `http://localhost/hrms/restore_admin.php`
  - *Run this if you can't log in or want to verify system health after transfer.*
- **Admin Portal**: `http://localhost/hrms/admin/login.php`
  - **User**: `admin@hrms.com`
  - **Pass**: `admin123`
- **Employee Portal**: `http://localhost/hrms/login.php`

## Features Included
- **Premium Admin Dashboard**: Real-time stats and management overview.
- **Dynamic Settings**: Configure company branding, currency (₱), and attendance rules.
- **Attendance Protocol**: Late thresholds and grace periods with automatic status tracking.
- **Payroll & Payslips**: Generate payroll and export high-fidelity PDF payslips with color-retained designs.
- **Department Management**: Organize employees into custom specialized units.

---
CREATED BY : AL-ADZMI S. SAJALUN

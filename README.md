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

## GitHub Pages Deployment

This repository is configured to deploy to GitHub Pages from the `index` branch.

### Setup Instructions:

1. **Enable GitHub Pages in Repository Settings:**
   - Go to your repository on GitHub: https://github.com/gibsongelera/hrms
   - Click on **Settings** → **Pages**
   - Under **Source**, select **GitHub Actions** (not "Deploy from a branch")
   - The workflow will automatically deploy when you push to the `index` branch

2. **Access Your Site:**
   - After deployment, your site will be available at: `https://gibsongelera.github.io/hrms/`

### Important Note:
⚠️ **GitHub Pages Limitations**: GitHub Pages only serves static files (HTML, CSS, JavaScript). Since this is a PHP application that requires a server and database, the full functionality will not work on GitHub Pages. For a production PHP application, consider using services like:
- Heroku
- DigitalOcean App Platform
- AWS Elastic Beanstalk
- Vercel (with serverless functions)
- Traditional web hosting with PHP support

The GitHub Pages deployment is useful for showcasing static documentation or a landing page.

---
CREATED BY : AL-ADZMI S. SAJALUN

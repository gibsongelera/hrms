-- HRMS Database Setup
-- Compatible with XAMPP/MySQL
-- Created: December 29, 2025

CREATE DATABASE IF NOT EXISTS hrms_db;
USE hrms_db;

-- 1. Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (role_name) VALUES ('Admin'), ('Manager'), ('Employee');

-- 2. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Initial Admin Account (User: admin@hrms.com | Pass: admin123)
INSERT INTO users (email, password, role_id) VALUES 
('admin@hrms.com', '$2y$10$0opgXmI3mcuIfOcg8bAN4uqUSeqYYWAl5yzdtm/GMLZSiO6JLT/2O', 1);

-- 3. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- 4. Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    job_role VARCHAR(100),
    department VARCHAR(100),
    base_salary DECIMAL(10, 2),
    hire_date DATE,
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    profile_pic VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Link Admin user to an employee record for system consistency
INSERT INTO employees (user_id, first_name, last_name, job_role, department, status) VALUES 
(1, 'System', 'Administrator', 'IT Administrator', 'IT Department', 'Active');

-- 5. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME,
    date DATE NOT NULL,
    work_hours DECIMAL(5, 2),
    status VARCHAR(20) DEFAULT 'Present',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 6. Leave Requests Table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 7. Payroll Table
CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    month_year VARCHAR(20) NOT NULL,
    base_salary DECIMAL(10, 2),
    deductions DECIMAL(10, 2) DEFAULT 0,
    net_pay DECIMAL(10, 2),
    payment_date DATE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 8. Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT
);

INSERT INTO settings (setting_key, setting_value) VALUES 
('company_name', 'HRMS'),
('currency', '₱'),
('late_threshold', '09:00'),
('grace_period', '15'),
('shift_end', '18:00'),
('system_status', 'Online');

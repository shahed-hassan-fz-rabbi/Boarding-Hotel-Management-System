-- ===========================================
-- BOARDING SCHOOL MANAGEMENT SYSTEM
-- Complete Database Schema with Sample Data
-- ===========================================
-- Import into an existing database:
--   Local:   CREATE DATABASE boarding_school CHARACTER SET utf8mb4;
--            then: mysql -u root -p boarding_school < boarding_school.sql
--   Hosting: select your assigned database in phpMyAdmin and import this file.
-- (No CREATE DATABASE / USE statements: shared hosts assign the db name.)

-- ===========================================
-- Users Table (Admin, Warden, Staff)
-- ===========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Warden', 'Staff') NOT NULL DEFAULT 'Staff',
    status TINYINT DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Insert Demo Admin (password: admin123)
INSERT INTO users (full_name, email, password, role, status)
VALUES ('Administrator', 'admin@boarding.com', '$2y$10$YIjlrJyPgd3/UL.KY7Hde.sSicSBEWmHCKXDCKKmkqVvvO3pBRBXK', 'Admin', 1);

-- ===========================================
-- Guardians Table
-- ===========================================

CREATE TABLE guardians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    guardian_name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- Students Table
-- ===========================================

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(50) NOT NULL UNIQUE,
    guardian_id INT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    dob DATE,
    blood_group VARCHAR(5),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    photo VARCHAR(255),
    address TEXT,
    admission_date DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guardian_id) REFERENCES guardians(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_student_code (student_code)
);

-- Sample Students
INSERT INTO students (student_code, first_name, last_name, gender, dob, blood_group, phone, email, admission_date, status)
VALUES
('STU20240101001', 'Ahmed', 'Hassan', 'Male', '2006-05-15', 'A+', '01712345678', 'ahmed.hassan@example.com', '2024-01-15', 'Active'),
('STU20240101002', 'Fatima', 'Khan', 'Female', '2006-08-20', 'B+', '01812345679', 'fatima.khan@example.com', '2024-01-20', 'Active'),
('STU20240101003', 'Muhammad', 'Ali', 'Male', '2007-03-10', 'O+', '01912345680', 'muhammad.ali@example.com', '2024-02-01', 'Active'),
('STU20240101004', 'Ayesha', 'Rahman', 'Female', '2006-12-05', 'A-', '01712345681', 'ayesha.rahman@example.com', '2024-02-15', 'Active');

-- ===========================================
-- Hostel Rooms Table
-- ===========================================

CREATE TABLE hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('Boys','Girls','Staff') NOT NULL,
    total_beds INT NOT NULL,
    allocated_beds INT NOT NULL DEFAULT 0,
    status ENUM('Available','Full') NOT NULL,
    floor INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_status (status),
    INDEX idx_room_number (room_number)
);

-- Sample Rooms
INSERT INTO hostel_rooms (room_number, room_type, total_beds, allocated_beds, status, floor, description)
VALUES
('B101', 'Boys', 6, 2, 'Available', 1, 'South Wing - First Floor'),
('B102', 'Boys', 6, 4, 'Available', 1, 'South Wing - First Floor'),
('B103', 'Boys', 6, 6, 'Full', 1, 'South Wing - First Floor'),
('B104', 'Boys', 8, 5, 'Available', 2, 'South Wing - Second Floor'),
('G201', 'Girls', 6, 3, 'Available', 3, 'North Wing - Third Floor'),
('G202', 'Girls', 6, 6, 'Full', 3, 'North Wing - Third Floor'),
('G203', 'Girls', 8, 4, 'Available', 3, 'North Wing - Third Floor'),
('S301', 'Staff', 4, 2, 'Available', 0, 'Administrative Building');

-- ===========================================
-- Room Beds Table
-- ===========================================

CREATE TABLE room_beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    bed_type VARCHAR(50),
    status ENUM('Available', 'Occupied', 'Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES hostel_rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bed (room_id, bed_number),
    INDEX idx_status (status)
);

-- Sample Beds
INSERT INTO room_beds (room_id, bed_number, bed_type, status) VALUES
(1, 'B101-01', 'Single', 'Occupied'),
(1, 'B101-02', 'Single', 'Occupied'),
(1, 'B101-03', 'Single', 'Available'),
(1, 'B101-04', 'Single', 'Available'),
(1, 'B101-05', 'Single', 'Available'),
(1, 'B101-06', 'Single', 'Available'),
(2, 'B102-01', 'Single', 'Occupied'),
(2, 'B102-02', 'Single', 'Occupied'),
(2, 'B102-03', 'Single', 'Occupied'),
(2, 'B102-04', 'Single', 'Occupied'),
(2, 'B102-05', 'Single', 'Available'),
(2, 'B102-06', 'Single', 'Available');

-- ===========================================
-- Room Allocations Table
-- ===========================================

CREATE TABLE room_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bed_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NULL,
    status ENUM('Active', 'Checked Out') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES room_beds(id) ON DELETE CASCADE,
    INDEX idx_status (status)
);

-- Sample Allocations
INSERT INTO room_allocations (student_id, bed_id, check_in, status) VALUES
(1, 1, '2024-01-15', 'Active'),
(2, 7, '2024-01-20', 'Active'),
(3, 2, '2024-02-01', 'Active'),
(4, 8, '2024-02-15', 'Active');

-- ===========================================
-- Payments Table
-- ===========================================

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    room_fee DECIMAL(10, 2) NOT NULL,
    meal_fee DECIMAL(10, 2) NOT NULL,
    fine DECIMAL(10, 2) DEFAULT 0,
    discount DECIMAL(10, 2) DEFAULT 0,
    paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    due_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_status ENUM('Paid', 'Partial', 'Due') DEFAULT 'Due',
    payment_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payment (student_id, month, year),
    INDEX idx_status (payment_status)
);

-- Sample Payments
INSERT INTO payments (student_id, month, year, room_fee, meal_fee, paid_amount, due_amount, payment_status, payment_date)
VALUES
(1, 1, 2024, 5000, 3000, 8000, 0, 'Paid', '2024-01-05'),
(1, 2, 2024, 5000, 3000, 0, 8000, 'Due', NULL),
(2, 1, 2024, 5000, 3000, 5000, 3000, 'Partial', '2024-01-10'),
(2, 2, 2024, 5000, 3000, 0, 8000, 'Due', NULL),
(3, 1, 2024, 5000, 3000, 8000, 0, 'Paid', '2024-02-01'),
(3, 2, 2024, 5000, 3000, 0, 8000, 'Due', NULL),
(4, 1, 2024, 5000, 3000, 0, 8000, 'Due', NULL);

-- ===========================================
-- Meal Records Table
-- ===========================================

CREATE TABLE meal_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    meal_date DATE NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner') NOT NULL,
    served TINYINT DEFAULT 1,
    quantity DECIMAL(5, 2),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_meal_date (meal_date),
    INDEX idx_student (student_id)
);

-- Sample Meal Records
INSERT INTO meal_records (student_id, meal_date, meal_type, served, quantity, created_by)
VALUES
(1, '2024-01-15', 'Breakfast', 1, 1.0, 1),
(1, '2024-01-15', 'Lunch', 1, 1.0, 1),
(1, '2024-01-15', 'Dinner', 1, 1.0, 1),
(2, '2024-01-20', 'Breakfast', 1, 1.0, 1),
(2, '2024-01-20', 'Lunch', 1, 1.0, 1),
(3, '2024-02-01', 'Breakfast', 1, 1.0, 1);

-- ===========================================
-- Complaints Table
-- ===========================================

CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    complaint_title VARCHAR(255) NOT NULL,
    complaint_description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Open',
    assigned_to INT,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_student (student_id)
);

-- Sample Complaints
INSERT INTO complaints (student_id, complaint_title, complaint_description, category, priority, status, assigned_to)
VALUES
(1, 'Water Supply Issue', 'No hot water in room', 'Maintenance', 'High', 'In Progress', 1),
(2, 'Room Temperature', 'AC not working properly', 'Maintenance', 'Medium', 'Open', NULL),
(3, 'Noisy Roommate', 'Roommate creates noise at night', 'Behavioral', 'Low', 'Resolved', 1);

-- ===========================================
-- Leave Types Table
-- ===========================================

CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(100) NOT NULL UNIQUE,
    max_days_per_year INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Leave Types
INSERT INTO leave_types (leave_type_name, max_days_per_year, description)
VALUES
('Casual Leave', 15, 'For personal reasons'),
('Sick Leave', 12, 'For health-related issues'),
('Medical Leave', 15, 'For medical treatment'),
('Emergency Leave', 7, 'For emergency situations'),
('Exam Leave', 30, 'For examination preparation');

-- ===========================================
-- Student Leave Balance Table
-- ===========================================

CREATE TABLE student_leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    leave_type_id INT NOT NULL,
    total_days INT NOT NULL,
    used_days INT DEFAULT 0,
    remaining_days INT NOT NULL,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
);

-- Sample Leave Balance
INSERT INTO student_leave_balance (student_id, leave_type_id, total_days, used_days, remaining_days, year)
VALUES
(1, 1, 15, 0, 15, 2024),
(2, 1, 15, 2, 13, 2024),
(3, 1, 15, 0, 15, 2024),
(4, 1, 15, 1, 14, 2024);

-- ===========================================
-- Leave Applications Table
-- ===========================================

CREATE TABLE leave_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    number_of_days INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    approved_by INT,
    approval_notes TEXT,
    applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_on TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_student (student_id),
    INDEX idx_from_date (from_date)
);

-- Sample Leave Applications
INSERT INTO leave_applications (student_id, leave_type_id, from_date, to_date, number_of_days, reason, status, approved_by, approval_notes)
VALUES
(1, 1, '2024-02-01', '2024-02-03', 2, 'Family meeting', 'Approved', 1, 'Approved'),
(2, 2, '2024-02-05', '2024-02-07', 2, 'Fever and cold', 'Approved', 1, 'Medical condition'),
(3, 1, '2024-02-10', '2024-02-12', 2, 'Home visit', 'Pending', NULL, NULL);

-- ===========================================
-- Activity Logs Table
-- ===========================================

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Sample Activity Logs
INSERT INTO activity_logs (user_id, action, ip_address)
VALUES
(1, 'Login', '127.0.0.1'),
(1, 'Viewed Dashboard', '127.0.0.1'),
(1, 'Added Student: STU20240101001', '127.0.0.1');

-- ===========================================
-- System Settings Table
-- ===========================================

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_email VARCHAR(100) NOT NULL,
    school_phone VARCHAR(20) NOT NULL,
    school_address TEXT,
    room_fee DECIMAL(10, 2) DEFAULT 5000,
    meal_fee DECIMAL(10, 2) DEFAULT 3000,
    session_timeout INT DEFAULT 3600,
    backup_enabled TINYINT DEFAULT 0,
    email_notifications TINYINT DEFAULT 1,
    academic_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default Settings
INSERT INTO system_settings (school_name, school_email, school_phone, school_address, room_fee, meal_fee, academic_year)
VALUES ('Boarding School Management System', 'info@boarding.edu', '+880-XXX-XXXXXX', 'School Address Here', 5000, 3000, '2024-2025');



CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late') NOT NULL,
    remarks VARCHAR(255) NULL,
    marked_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_date (student_id, attendance_date)
);
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('High','Normal','Low') DEFAULT 'Normal',
    publish_date DATE NOT NULL,
    status ENUM('Active','Expired','Draft') DEFAULT 'Active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notice_user
    FOREIGN KEY (created_by)
    REFERENCES users(id)
    ON DELETE SET NULL
);





UPDATE users SET role = 'Teacher' WHERE role = 'Staff';
UPDATE users SET role = 'Hostel Supervisor' WHERE role = 'Warden';


ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Teacher', 'Hostel Supervisor', 'Accountant') NOT NULL DEFAULT 'Teacher';

-- ==========================================
-- DATABASE READY
-- ===========================================

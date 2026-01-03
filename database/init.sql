-- Employee Management System Database Schema
-- SQLite Database Initialization Script

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Drop existing tables (for development)
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS leave_types;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS departments;

-- Create departments table
CREATE TABLE departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create employees table
CREATE TABLE employees (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    department_id INTEGER NOT NULL,
    position VARCHAR(100) NOT NULL,
    role TEXT CHECK(role IN ('employee', 'admin', 'hr')) DEFAULT 'employee',
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    status TEXT CHECK(status IN ('active', 'inactive', 'terminated')) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Create leave types table
CREATE TABLE leave_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_paid BOOLEAN DEFAULT true,
    max_days_per_year INTEGER DEFAULT 0, -- 0 means unlimited
    color VARCHAR(7) DEFAULT '#3b82f6', -- Hex color for UI
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create leave requests table
CREATE TABLE leave_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id VARCHAR(10) NOT NULL,
    leave_type_id INTEGER NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INTEGER NOT NULL,
    reason TEXT NOT NULL,
    status TEXT CHECK(status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by VARCHAR(10) NULL,
    admin_comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (reviewed_by) REFERENCES employees(id),
    CHECK (end_date >= start_date),
    CHECK (total_days > 0)
);

-- Create attendance table (for future use)
CREATE TABLE attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status TEXT CHECK(status IN ('present', 'absent', 'half_day', 'late', 'on_leave')) DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE(employee_id, date)
);

-- Insert sample departments
INSERT INTO departments (name, description) VALUES 
('Engineering', 'Software development and technical operations'),
('Human Resources', 'Employee management and organizational development'),
('Marketing', 'Brand management and customer acquisition'),
('Sales', 'Revenue generation and client relations'),
('Finance', 'Financial planning and accounting');

-- Insert sample leave types
INSERT INTO leave_types (name, description, is_paid, max_days_per_year, color) VALUES 
('Annual Leave', 'Yearly vacation leave', true, 20, '#10b981'),
('Sick Leave', 'Medical leave for illness', true, 12, '#ef4444'),
('Personal Leave', 'Personal time off', false, 5, '#f59e0b'),
('Maternity Leave', 'Maternity/Paternity leave', true, 90, '#ec4899'),
('Emergency Leave', 'Urgent personal matters', false, 3, '#dc2626'),
('Study Leave', 'Educational purposes', false, 10, '#6366f1'),
('Bereavement Leave', 'Loss of family member', true, 7, '#64748b');

-- Insert sample employees
INSERT INTO employees (id, name, email, phone, department_id, position, role, hire_date, salary, status) VALUES 
('E001', 'John Doe', 'john.doe@company.com', '+1-555-123-4567', 1, 'Senior Developer', 'employee', '2023-01-15', 85000.00, 'active'),
('E002', 'Jane Smith', 'jane.smith@company.com', '+1-555-234-5678', 3, 'Marketing Manager', 'admin', '2022-08-10', 75000.00, 'active'),
('E003', 'Mike Johnson', 'mike.johnson@company.com', '+1-555-345-6789', 4, 'Sales Representative', 'employee', '2023-03-20', 65000.00, 'active'),
('E004', 'Sarah Williams', 'sarah.williams@company.com', '+1-555-456-7890', 2, 'HR Manager', 'hr', '2021-11-05', 80000.00, 'active'),
('E005', 'David Brown', 'david.brown@company.com', '+1-555-567-8901', 5, 'Financial Analyst', 'employee', '2022-06-15', 70000.00, 'active'),
('E006', 'Emily Davis', 'emily.davis@company.com', '+1-555-678-9012', 1, 'Frontend Developer', 'employee', '2023-09-01', 72000.00, 'active'),
('A001', 'Admin User', 'admin@company.com', '+1-555-999-0000', 2, 'System Administrator', 'admin', '2021-01-01', 90000.00, 'active');

-- Insert sample leave requests for testing
INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, applied_at) VALUES 
('E001', 2, '2026-01-10', '2026-01-12', 3, 'Feeling unwell with flu symptoms', 'pending', '2026-01-03 08:30:00'),
('E002', 1, '2026-01-15', '2026-01-20', 6, 'Family vacation to Hawaii', 'approved', '2025-12-20 14:15:00'),
('E003', 3, '2026-01-18', '2026-01-19', 2, 'Personal appointment', 'pending', '2026-01-02 16:45:00'),
('E005', 1, '2026-02-01', '2026-02-05', 5, 'Long weekend getaway', 'pending', '2026-01-01 09:20:00'),
('E006', 2, '2025-12-28', '2025-12-30', 3, 'Doctor appointments', 'approved', '2025-12-15 11:30:00');

-- Update reviewed requests
UPDATE leave_requests SET 
    reviewed_at = '2025-12-21 09:00:00',
    reviewed_by = 'A001',
    admin_comments = 'Approved for vacation. Enjoy your time off!'
WHERE id = 2;

UPDATE leave_requests SET 
    reviewed_at = '2025-12-16 10:15:00',
    reviewed_by = 'E004',
    admin_comments = 'Medical leave approved. Please submit medical certificate.'
WHERE id = 5;

-- Create indexes for better performance
CREATE INDEX idx_leave_requests_employee ON leave_requests(employee_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_leave_requests_dates ON leave_requests(start_date, end_date);
CREATE INDEX idx_employees_role ON employees(role);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, date);

-- Create triggers for updating timestamps
CREATE TRIGGER update_employees_timestamp 
    AFTER UPDATE ON employees
    FOR EACH ROW
BEGIN
    UPDATE employees SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_leave_requests_timestamp 
    AFTER UPDATE ON leave_requests
    FOR EACH ROW
BEGIN
    UPDATE leave_requests SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER update_leave_types_timestamp 
    AFTER UPDATE ON leave_types
    FOR EACH ROW
BEGIN
    UPDATE leave_types SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Create view for leave request details
CREATE VIEW leave_request_details AS
SELECT 
    lr.id,
    lr.employee_id,
    e.name as employee_name,
    e.email as employee_email,
    d.name as department,
    e.position,
    lt.name as leave_type,
    lt.description as leave_type_description,
    lt.is_paid,
    lt.color as leave_type_color,
    lr.start_date,
    lr.end_date,
    lr.total_days,
    lr.reason,
    lr.status,
    lr.applied_at,
    lr.reviewed_at,
    lr.reviewed_by,
    reviewer.name as reviewer_name,
    lr.admin_comments,
    lr.created_at,
    lr.updated_at
FROM leave_requests lr
JOIN employees e ON lr.employee_id = e.id
JOIN departments d ON e.department_id = d.id
JOIN leave_types lt ON lr.leave_type_id = lt.id
LEFT JOIN employees reviewer ON lr.reviewed_by = reviewer.id;
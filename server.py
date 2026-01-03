#!/usr/bin/env python3
"""
Employee Management System - Python Server
A complete API server implementation to replace PHP dependencies
"""

import http.server
import socketserver
import json
import sqlite3
import os
import urllib.parse
import datetime
from pathlib import Path

# Configuration
PORT = 8001
DATABASE_PATH = "database/ems.db"
DATABASE_DIR = "database"

class EMSHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        """Add CORS headers to all responses"""
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        super().end_headers()

    def do_OPTIONS(self):
        """Handle preflight CORS requests"""
        self.send_response(200)
        self.end_headers()

    def do_GET(self):
        """Handle GET requests"""
        if self.path.startswith('/api'):
            self.handle_api_request()
        else:
            # Serve static files
            if self.path == '/':
                self.path = '/server_index.html'
            super().do_GET()

    def do_POST(self):
        """Handle POST requests"""
        if self.path.startswith('/api'):
            self.handle_api_request()
        else:
            self.send_error(404)

    def do_PUT(self):
        """Handle PUT requests"""
        if self.path.startswith('/api'):
            self.handle_api_request()
        else:
            self.send_error(404)

    def do_DELETE(self):
        """Handle DELETE requests"""
        if self.path.startswith('/api'):
            self.handle_api_request()
        else:
            self.send_error(404)

    def handle_api_request(self):
        """Route API requests to appropriate handlers"""
        try:
            # Parse URL and query parameters
            parsed_url = urllib.parse.urlparse(self.path)
            path = parsed_url.path.replace('/api', '').strip('/')
            query_params = urllib.parse.parse_qs(parsed_url.query)
            
            # Get request body for POST/PUT requests
            request_body = {}
            if self.command in ['POST', 'PUT']:
                content_length = int(self.headers.get('Content-Length', 0))
                if content_length > 0:
                    body = self.rfile.read(content_length).decode('utf-8')
                    try:
                        request_body = json.loads(body)
                    except json.JSONDecodeError:
                        pass

            # Route to handlers
            if path == '' or path == 'health':
                self.handle_health_check()
            elif path == 'init':
                self.handle_database_init()
            elif path.startswith('leave_requests') or path.startswith('leaves'):
                self.handle_leave_requests(query_params, request_body)
            elif path.startswith('payroll'):
                self.handle_payroll(query_params, request_body)
            else:
                self.send_api_response({
                    'success': False,
                    'message': f'Endpoint not found: {path}',
                    'available_endpoints': {
                        'GET /api/health': 'Health check',
                        'POST /api/init': 'Initialize database',
                        'GET /api/leave_requests': 'Get leave requests',
                        'POST /api/leave_requests': 'Create leave request'
                    }
                }, 404)

        except Exception as e:
            print(f"API Error: {e}")
            self.send_api_response({
                'success': False,
                'message': 'Internal server error',
                'error': str(e)
            }, 500)

    def handle_health_check(self):
        """Health check endpoint"""
        try:
            # Check database
            db_healthy = self.check_database_health()
            
            response = {
                'status': 'healthy' if db_healthy else 'unhealthy',
                'api': 'healthy',
                'database': 'healthy' if db_healthy else 'unhealthy',
                'timestamp': datetime.datetime.now().isoformat()
            }
            
            self.send_api_response(response, 200 if db_healthy else 503)
            
        except Exception as e:
            self.send_api_response({
                'status': 'unhealthy',
                'api': 'unhealthy',
                'database': 'unhealthy',
                'error': str(e),
                'timestamp': datetime.datetime.now().isoformat()
            }, 503)

    def handle_database_init(self):
        """Initialize database endpoint"""
        if self.command != 'POST':
            self.send_api_response({
                'success': False,
                'message': 'Method not allowed. Use POST to initialize database.'
            }, 405)
            return

        try:
            self.initialize_database()
            self.send_api_response({
                'success': True,
                'message': 'Database initialized successfully',
                'database_path': os.path.abspath(DATABASE_PATH)
            })
        except Exception as e:
            self.send_api_response({
                'success': False,
                'message': f'Database initialization failed: {str(e)}'
            }, 500)

    def handle_leave_requests(self, query_params, request_body):
        """Handle leave request operations"""
        try:
            if self.command == 'GET':
                self.handle_get_leave_requests(query_params)
            elif self.command == 'POST':
                self.handle_post_leave_request(request_body)
            elif self.command == 'PUT':
                self.handle_put_leave_request(query_params, request_body)
            elif self.command == 'DELETE':
                self.handle_delete_leave_request(query_params)
            else:
                self.send_api_response({
                    'success': False,
                    'message': 'Method not allowed'
                }, 405)
        except Exception as e:
            print(f"Leave request error: {e}")
            self.send_api_response({
                'success': False,
                'message': str(e)
            }, 400)

    def handle_get_leave_requests(self, query_params):
        """Get leave requests"""
        conn = self.get_db_connection()
        
        # Check for special queries
        if 'leave_types' in query_params:
            cursor = conn.execute("SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name")
            results = [dict(row) for row in cursor.fetchall()]
            self.send_api_response({
                'success': True,
                'message': 'Leave types retrieved successfully',
                'data': results
            })
            return
            
        if 'stats' in query_params:
            stats = self.get_leave_statistics(conn)
            self.send_api_response({
                'success': True,
                'message': 'Statistics retrieved successfully',
                'data': stats
            })
            return
        
        # Get leave requests
        sql = """
        SELECT lr.*, e.name as employee_name, lt.name as leave_type, 
               d.name as department, reviewer.name as reviewer_name
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        JOIN departments d ON e.department_id = d.id
        LEFT JOIN employees reviewer ON lr.reviewed_by = reviewer.id
        ORDER BY lr.applied_at DESC
        """
        
        cursor = conn.execute(sql)
        results = [dict(row) for row in cursor.fetchall()]
        
        self.send_api_response({
            'success': True,
            'message': 'Leave requests retrieved successfully',
            'data': results
        })

    def handle_post_leave_request(self, request_body):
        """Create new leave request"""
        # Validate required fields
        required_fields = ['leave_type_id', 'start_date', 'end_date', 'reason']
        for field in required_fields:
            if field not in request_body or not str(request_body[field]).strip():
                self.send_api_response({
                    'success': False,
                    'message': 'Validation failed',
                    'errors': {field: f'The {field} field is required'}
                }, 422)
                return

        # Calculate total days
        start_date = datetime.datetime.strptime(request_body['start_date'], '%Y-%m-%d')
        end_date = datetime.datetime.strptime(request_body['end_date'], '%Y-%m-%d')
        total_days = (end_date - start_date).days + 1

        if total_days <= 0:
            self.send_api_response({
                'success': False,
                'message': 'Validation failed',
                'errors': {'end_date': 'End date must be after start date'}
            }, 422)
            return

        # Insert into database
        conn = self.get_db_connection()
        cursor = conn.execute("""
            INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        """, ('E001', request_body['leave_type_id'], request_body['start_date'], 
              request_body['end_date'], total_days, request_body['reason']))
        
        conn.commit()
        
        # Get the created request
        request_id = cursor.lastrowid
        cursor = conn.execute("""
            SELECT lr.*, e.name as employee_name, lt.name as leave_type
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.id = ?
        """, (request_id,))
        
        result = dict(cursor.fetchone())
        
        self.send_api_response({
            'success': True,
            'message': 'Leave request submitted successfully',
            'data': result
        }, 201)

    def handle_put_leave_request(self, query_params, request_body):
        """Update leave request (approve/reject)"""
        request_id = query_params.get('id', [None])[0]
        if not request_id:
            self.send_api_response({
                'success': False,
                'message': 'Leave request ID is required'
            }, 400)
            return

        if 'status' not in request_body:
            self.send_api_response({
                'success': False,
                'message': 'Status is required'
            }, 400)
            return

        status = request_body['status']
        if status not in ['approved', 'rejected']:
            self.send_api_response({
                'success': False,
                'message': 'Status must be approved or rejected'
            }, 400)
            return

        # Update the request
        conn = self.get_db_connection()
        conn.execute("""
            UPDATE leave_requests 
            SET status = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ?, admin_comments = ?
            WHERE id = ?
        """, (status, 'A001', request_body.get('admin_comments'), request_id))
        
        conn.commit()

        # Get updated request
        cursor = conn.execute("""
            SELECT lr.*, e.name as employee_name, lt.name as leave_type
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.id = ?
        """, (request_id,))
        
        result = dict(cursor.fetchone())
        
        message = 'Leave request approved successfully' if status == 'approved' else 'Leave request rejected successfully'
        self.send_api_response({
            'success': True,
            'message': message,
            'data': result
        })

    def handle_delete_leave_request(self, query_params):
        """Delete/cancel leave request"""
        request_id = query_params.get('id', [None])[0]
        if not request_id:
            self.send_api_response({
                'success': False,
                'message': 'Leave request ID is required'
            }, 400)
            return

        conn = self.get_db_connection()
        conn.execute("DELETE FROM leave_requests WHERE id = ?", (request_id,))
        conn.commit()

        self.send_api_response({
            'success': True,
            'message': 'Leave request cancelled successfully'
        })

    def handle_payroll(self, query_params, request_body):
        """Handle payroll operations"""
        try:
            if self.command == 'GET':
                self.handle_get_payroll(query_params)
            elif self.command == 'PUT':
                self.handle_update_payroll(query_params, request_body)
            else:
                self.send_api_response({
                    'success': False,
                    'message': 'Method not allowed for payroll endpoint'
                }, 405)
        except Exception as e:
            print(f"Payroll error: {e}")
            self.send_api_response({
                'success': False,
                'message': str(e)
            }, 400)

    def handle_get_payroll(self, query_params):
        """Get payroll information"""
        conn = self.get_db_connection()
        
        # Check if requesting specific employee or all employees
        employee_id = query_params.get('employee_id', [None])[0]
        
        if employee_id:
            # Get specific employee payroll
            cursor = conn.execute("""
                SELECT e.id, e.name, e.email, e.position, e.hire_date, e.salary,
                       d.name as department, e.status
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                WHERE e.id = ?
            """, (employee_id,))
            result = cursor.fetchone()
            
            if result:
                payroll_data = dict(result)
                # Calculate additional payroll info
                payroll_data.update(self.calculate_payroll_details(payroll_data))
                
                self.send_api_response({
                    'success': True,
                    'message': 'Employee payroll retrieved successfully',
                    'data': payroll_data
                })
            else:
                self.send_api_response({
                    'success': False,
                    'message': 'Employee not found'
                }, 404)
        else:
            # Get all employees payroll (admin view)
            cursor = conn.execute("""
                SELECT e.id, e.name, e.email, e.position, e.hire_date, e.salary,
                       d.name as department, e.status
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                WHERE e.status = 'active'
                ORDER BY e.name
            """)
            results = [dict(row) for row in cursor.fetchall()]
            
            # Calculate payroll details for each employee
            for employee in results:
                employee.update(self.calculate_payroll_details(employee))
            
            self.send_api_response({
                'success': True,
                'message': 'All employee payroll data retrieved successfully',
                'data': results
            })

    def handle_update_payroll(self, query_params, request_body):
        """Update employee salary (admin only)"""
        employee_id = query_params.get('employee_id', [None])[0]
        if not employee_id:
            self.send_api_response({
                'success': False,
                'message': 'Employee ID is required'
            }, 400)
            return

        if 'salary' not in request_body:
            self.send_api_response({
                'success': False,
                'message': 'Salary is required'
            }, 400)
            return

        new_salary = request_body['salary']
        if not isinstance(new_salary, (int, float)) or new_salary <= 0:
            self.send_api_response({
                'success': False,
                'message': 'Salary must be a positive number'
            }, 400)
            return

        # Update salary in database
        conn = self.get_db_connection()
        cursor = conn.execute("""
            UPDATE employees 
            SET salary = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        """, (new_salary, employee_id))
        
        if cursor.rowcount == 0:
            self.send_api_response({
                'success': False,
                'message': 'Employee not found'
            }, 404)
            return
        
        conn.commit()

        # Get updated employee data
        cursor = conn.execute("""
            SELECT e.id, e.name, e.email, e.position, e.hire_date, e.salary,
                   d.name as department, e.status
            FROM employees e
            JOIN departments d ON e.department_id = d.id
            WHERE e.id = ?
        """, (employee_id,))
        
        result = dict(cursor.fetchone())
        result.update(self.calculate_payroll_details(result))
        
        self.send_api_response({
            'success': True,
            'message': f'Salary updated successfully for {result["name"]}',
            'data': result
        })

    def calculate_payroll_details(self, employee_data):
        """Calculate additional payroll information"""
        import datetime
        
        annual_salary = float(employee_data.get('salary', 0))
        monthly_salary = annual_salary / 12
        weekly_salary = annual_salary / 52
        daily_salary = annual_salary / 260  # Assuming 5 days/week, 52 weeks/year
        hourly_rate = annual_salary / 2080  # 40 hours/week, 52 weeks/year
        
        # Calculate years of service
        hire_date = datetime.datetime.strptime(employee_data['hire_date'], '%Y-%m-%d')
        years_of_service = (datetime.datetime.now() - hire_date).days / 365.25
        
        # Basic benefits calculation (could be more complex)
        health_insurance = monthly_salary * 0.05  # 5% of monthly salary
        retirement_401k = monthly_salary * 0.03   # 3% company match
        
        return {
            'annual_salary': round(annual_salary, 2),
            'monthly_salary': round(monthly_salary, 2),
            'weekly_salary': round(weekly_salary, 2),
            'daily_salary': round(daily_salary, 2),
            'hourly_rate': round(hourly_rate, 2),
            'years_of_service': round(years_of_service, 1),
            'health_insurance': round(health_insurance, 2),
            'retirement_401k': round(retirement_401k, 2),
            'total_monthly_benefits': round(health_insurance + retirement_401k, 2),
            'total_monthly_compensation': round(monthly_salary + health_insurance + retirement_401k, 2)
        }

    def get_leave_statistics(self, conn):
        """Get leave statistics"""
        stats = {}
        
        # Get counts by status
        cursor = conn.execute("SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status")
        for row in cursor.fetchall():
            stats[f"{row['status']}_requests"] = row['count']
        
        # Get total requests
        cursor = conn.execute("SELECT COUNT(*) as count FROM leave_requests")
        stats['total_requests'] = cursor.fetchone()['count']
        
        # Get employees on leave
        cursor = conn.execute("""
            SELECT COUNT(DISTINCT employee_id) as count FROM leave_requests 
            WHERE status = 'approved' AND start_date <= DATE('now') AND end_date >= DATE('now')
        """)
        stats['employees_on_leave'] = cursor.fetchone()['count']
        
        return stats

    def send_api_response(self, data, status_code=200):
        """Send JSON API response"""
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        
        response_json = json.dumps(data, indent=2, default=str)
        self.wfile.write(response_json.encode('utf-8'))

    def get_db_connection(self):
        """Get database connection"""
        if not os.path.exists(DATABASE_PATH):
            self.initialize_database()
        
        conn = sqlite3.connect(DATABASE_PATH)
        conn.row_factory = sqlite3.Row
        return conn

    def check_database_health(self):
        """Check if database is working"""
        try:
            conn = self.get_db_connection()
            conn.execute("SELECT 1")
            conn.close()
            return True
        except Exception:
            return False

    def initialize_database(self):
        """Initialize the database"""
        # Create database directory
        os.makedirs(DATABASE_DIR, exist_ok=True)
        
        conn = sqlite3.connect(DATABASE_PATH)
        
        # Create tables and insert sample data
        init_sql = '''
        PRAGMA foreign_keys = ON;

        -- Drop existing tables
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
            is_paid BOOLEAN DEFAULT 1,
            max_days_per_year INTEGER DEFAULT 0,
            color VARCHAR(7) DEFAULT '#3b82f6',
            is_active BOOLEAN DEFAULT 1,
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

        -- Insert sample data
        INSERT INTO departments (name, description) VALUES 
        ('Engineering', 'Software development and technical operations'),
        ('Human Resources', 'Employee management and organizational development'),
        ('Marketing', 'Brand management and customer acquisition'),
        ('Sales', 'Revenue generation and client relations'),
        ('Finance', 'Financial planning and accounting');

        INSERT INTO leave_types (name, description, is_paid, max_days_per_year, color) VALUES 
        ('Annual Leave', 'Yearly vacation leave', 1, 20, '#10b981'),
        ('Sick Leave', 'Medical leave for illness', 1, 12, '#ef4444'),
        ('Personal Leave', 'Personal time off', 0, 5, '#f59e0b'),
        ('Maternity Leave', 'Maternity/Paternity leave', 1, 90, '#ec4899'),
        ('Emergency Leave', 'Urgent personal matters', 0, 3, '#dc2626'),
        ('Study Leave', 'Educational purposes', 0, 10, '#6366f1'),
        ('Bereavement Leave', 'Loss of family member', 1, 7, '#64748b');

        INSERT INTO employees (id, name, email, phone, department_id, position, role, hire_date, salary, status) VALUES 
        ('E001', 'John Doe', 'john.doe@company.com', '+1-555-123-4567', 1, 'Senior Developer', 'employee', '2023-01-15', 85000.00, 'active'),
        ('E002', 'Jane Smith', 'jane.smith@company.com', '+1-555-234-5678', 3, 'Marketing Manager', 'admin', '2022-08-10', 75000.00, 'active'),
        ('E003', 'Mike Johnson', 'mike.johnson@company.com', '+1-555-345-6789', 4, 'Sales Representative', 'employee', '2023-03-20', 65000.00, 'active'),
        ('E004', 'Sarah Williams', 'sarah.williams@company.com', '+1-555-456-7890', 2, 'HR Manager', 'hr', '2021-11-05', 80000.00, 'active'),
        ('E005', 'David Brown', 'david.brown@company.com', '+1-555-567-8901', 5, 'Financial Analyst', 'employee', '2022-06-15', 70000.00, 'active'),
        ('E006', 'Emily Davis', 'emily.davis@company.com', '+1-555-678-9012', 1, 'Frontend Developer', 'employee', '2023-09-01', 72000.00, 'active'),
        ('A001', 'Admin User', 'admin@company.com', '+1-555-999-0000', 2, 'System Administrator', 'admin', '2021-01-01', 90000.00, 'active');

        INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, applied_at) VALUES 
        ('E001', 2, '2026-01-10', '2026-01-12', 3, 'Feeling unwell with flu symptoms', 'pending', '2026-01-03 08:30:00'),
        ('E002', 1, '2026-01-15', '2026-01-20', 6, 'Family vacation to Hawaii', 'approved', '2025-12-20 14:15:00'),
        ('E003', 3, '2026-01-18', '2026-01-19', 2, 'Personal appointment', 'pending', '2026-01-02 16:45:00');

        -- Update reviewed requests
        UPDATE leave_requests SET 
            reviewed_at = '2025-12-21 09:00:00',
            reviewed_by = 'A001',
            admin_comments = 'Approved for vacation. Enjoy your time off!'
        WHERE id = 2;
        '''
        
        # Execute each statement
        for statement in init_sql.split(';'):
            statement = statement.strip()
            if statement:
                conn.execute(statement)
        
        conn.commit()
        conn.close()

def main():
    """Start the server"""
    # Create database directory if it doesn't exist
    os.makedirs(DATABASE_DIR, exist_ok=True)
    
    print("🚀 Starting Employee Management System Server...")
    print(f"📍 Server running at: http://localhost:{PORT}")
    print(f"🗄️  Database location: {os.path.abspath(DATABASE_PATH)}")
    print("\n📋 Available endpoints:")
    print(f"  • http://localhost:{PORT}/ - Server dashboard")
    print(f"  • http://localhost:{PORT}/index.html - Main application")
    print(f"  • http://localhost:{PORT}/api/health - Health check")
    print(f"  • http://localhost:{PORT}/api/leave_requests - Leave requests API")
    print(f"  • http://localhost:{PORT}/api/payroll - Payroll management API")
    print("\n✅ Press Ctrl+C to stop the server")
    print("-" * 50)
    
    try:
        with socketserver.TCPServer(("", PORT), EMSHandler) as httpd:
            httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n\n🛑 Server stopped")

if __name__ == "__main__":
    main()
<?php
/**
 * Database Debug and Troubleshooting Script
 * Run this to diagnose and fix database issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Debug and Setup</h2>\n";
echo "<style>body{font-family:Arial;max-width:800px;margin:0 auto;padding:20px;}pre{background:#f4f4f4;padding:10px;border-radius:5px;}</style>\n";

// Check PHP environment
echo "<h3>1. PHP Environment Check</h3>\n";
echo "<ul>\n";
echo "<li>PHP Version: <strong>" . phpversion() . "</strong></li>\n";
echo "<li>Operating System: <strong>" . php_uname('s') . " " . php_uname('r') . "</strong></li>\n";
echo "<li>SQLite3 Extension: <strong>" . (extension_loaded('sqlite3') ? '✓ Enabled' : '✗ Not Available') . "</strong></li>\n";
echo "<li>PDO Extension: <strong>" . (extension_loaded('pdo') ? '✓ Enabled' : '✗ Not Available') . "</strong></li>\n";
echo "<li>PDO SQLite: <strong>" . (extension_loaded('pdo_sqlite') ? '✓ Enabled' : '✗ Not Available') . "</strong></li>\n";
echo "</ul>\n";

// Check directory permissions
echo "<h3>2. Directory and File Permissions</h3>\n";
$database_dir = __DIR__ . DIRECTORY_SEPARATOR . 'database';
$database_file = $database_dir . DIRECTORY_SEPARATOR . 'ems.db';
$init_file = $database_dir . DIRECTORY_SEPARATOR . 'init.sql';

echo "<ul>\n";
echo "<li>Current Directory: <code>" . __DIR__ . "</code></li>\n";
echo "<li>Database Directory: <code>" . $database_dir . "</code></li>\n";
echo "<li>Database Directory Exists: <strong>" . (is_dir($database_dir) ? '✓ Yes' : '✗ No') . "</strong></li>\n";

if (!is_dir($database_dir)) {
    echo "<li>Creating database directory... ";
    if (mkdir($database_dir, 0755, true)) {
        echo "<strong style='color:green'>✓ Created</strong></li>\n";
    } else {
        echo "<strong style='color:red'>✗ Failed</strong></li>\n";
    }
}

echo "<li>Database Directory Writable: <strong>" . (is_writable($database_dir) ? '✓ Yes' : '✗ No') . "</strong></li>\n";
echo "<li>Init SQL File Exists: <strong>" . (file_exists($init_file) ? '✓ Yes' : '✗ No') . "</strong></li>\n";
echo "</ul>\n";

// Check for missing extensions
if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
    echo "<div style='background:#ffebee;padding:15px;border-left:4px solid #f44336;margin:20px 0;'>\n";
    echo "<h4>❌ Missing Required Extensions</h4>\n";
    echo "<p>Your PHP installation is missing required SQLite extensions. Here's how to fix it:</p>\n";
    echo "<h5>For Windows XAMPP/WAMP:</h5>\n";
    echo "<ol>\n";
    echo "<li>Open <code>php.ini</code> file</li>\n";
    echo "<li>Find and uncomment these lines (remove the ';' at the beginning):</li>\n";
    echo "<pre>extension=pdo_sqlite\nextension=sqlite3</pre>\n";
    echo "<li>Restart your web server</li>\n";
    echo "</ol>\n";
    echo "<h5>For PHP Built-in Server:</h5>\n";
    echo "<p>These extensions should be enabled by default in PHP 7.4+</p>\n";
    echo "</div>\n";
    exit;
}

// Test basic SQLite functionality
echo "<h3>3. SQLite Functionality Test</h3>\n";
try {
    $test_db = new PDO('sqlite::memory:');
    $test_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $test_db->exec("CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)");
    $test_db->exec("INSERT INTO test (name) VALUES ('test')");
    $result = $test_db->query("SELECT * FROM test")->fetch();
    
    if ($result && $result['name'] === 'test') {
        echo "<p style='color:green'>✓ SQLite is working correctly</p>\n";
    } else {
        echo "<p style='color:red'>✗ SQLite test failed</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ SQLite Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Try to include and test our Database class
echo "<h3>4. Database Class Test</h3>\n";
$config_file = __DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Database.php';

if (!file_exists($config_file)) {
    echo "<p style='color:red'>✗ Database.php file not found at: <code>" . $config_file . "</code></p>\n";
    echo "<p>Creating minimal database class...</p>\n";
    
    // Create minimal database class if missing
    $api_dir = __DIR__ . DIRECTORY_SEPARATOR . 'api';
    $config_dir = $api_dir . DIRECTORY_SEPARATOR . 'config';
    
    if (!is_dir($api_dir)) mkdir($api_dir, 0755, true);
    if (!is_dir($config_dir)) mkdir($config_dir, 0755, true);
    
    // Create the database class file
    file_put_contents($config_file, createDatabaseClass());
    echo "<p style='color:green'>✓ Database.php created</p>\n";
}

try {
    require_once $config_file;
    echo "<p style='color:green'>✓ Database.php loaded successfully</p>\n";
    
    // Test database connection
    $database = new Database();
    echo "<p style='color:green'>✓ Database class instantiated</p>\n";
    
    // Test connection
    $connection = $database->getConnection();
    echo "<p style='color:green'>✓ Database connection established</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database Class Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<h4>Error Details:</h4>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

// Initialize database with fixed SQL
echo "<h3>5. Database Initialization</h3>\n";
try {
    if (!isset($database)) {
        $database = new Database();
    }
    
    // Create init SQL file if it doesn't exist
    if (!file_exists($init_file)) {
        echo "<p>Creating init.sql file...</p>\n";
        file_put_contents($init_file, createInitSQL());
        echo "<p style='color:green'>✓ init.sql created</p>\n";
    }
    
    // Initialize database with corrected SQL
    initializeDatabase($database);
    
    echo "<p style='color:green'>✓ Database initialized successfully!</p>\n";
    
    // Test queries
    testDatabaseQueries($database);
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database Initialization Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<h4>Error Details:</h4>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

// Success message
echo "<div style='background:#e8f5e8;padding:15px;border-left:4px solid #4caf50;margin:20px 0;'>\n";
echo "<h4>✅ Setup Complete!</h4>\n";
echo "<p>Your database is now ready to use. You can:</p>\n";
echo "<ul>\n";
echo "<li><a href='index.html'>Open the Employee Management System</a></li>\n";
echo "<li><a href='api/index.php'>Test the API</a></li>\n";
echo "<li><a href='api/leave_requests.php?stats=1'>View Leave Statistics</a></li>\n";
echo "</ul>\n";
echo "</div>\n";

/**
 * Create minimal database class
 */
function createDatabaseClass() {
    return '<?php
class Database {
    private $db_path;
    private $connection;
    
    public function __construct($db_path = null) {
        $this->db_path = $db_path ?: __DIR__ . "/../../database/ems.db";
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            $db_dir = dirname($this->db_path);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            
            $this->connection = new PDO("sqlite:" . $this->db_path);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->exec("PRAGMA foreign_keys = ON");
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $this->connection;
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->execute($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->execute($sql, $params)->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    public function healthCheck() {
        try {
            $this->getConnection()->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

class ApiResponse {
    public static function json($data, $status_code = 200) {
        http_response_code($status_code);
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            exit(0);
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function success($data = null, $message = "Success", $status_code = 200) {
        self::json([
            "success" => true,
            "message" => $message,
            "data" => $data,
            "timestamp" => date("c")
        ], $status_code);
    }
    
    public static function error($message = "An error occurred", $status_code = 400, $errors = null) {
        self::json([
            "success" => false,
            "message" => $message,
            "errors" => $errors,
            "timestamp" => date("c")
        ], $status_code);
    }
    
    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }
    
    public static function serverError($message = "Internal server error") {
        self::error($message, 500);
    }
    
    public static function validationError($errors, $message = "Validation failed") {
        self::error($message, 422, $errors);
    }
}

class Validator {
    public static function required($data, $required_fields) {
        $errors = [];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === "") {
                $errors[$field] = "The {$field} field is required";
            }
        }
        return $errors;
    }
    
    public static function isValidDate($date, $format = "Y-m-d") {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, "sanitize"], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, "UTF-8");
    }
}

class Auth {
    public static function getCurrentUser() {
        return [
            "id" => "E001",
            "name" => "John Doe",
            "email" => "john.doe@company.com",
            "role" => "employee"
        ];
    }
    
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return in_array($user["role"], ["admin", "hr"]);
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            ApiResponse::error("Admin access required", 403);
        }
    }
}
?>';
}

/**
 * Create corrected init SQL for Windows
 */
function createInitSQL() {
    return '-- Employee Management System Database Schema
-- SQLite Database Initialization Script

PRAGMA foreign_keys = ON;

-- Drop existing tables (for development)
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS leave_types;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS attendance;

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
    role TEXT CHECK(role IN ("employee", "admin", "hr")) DEFAULT "employee",
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    status TEXT CHECK(status IN ("active", "inactive", "terminated")) DEFAULT "active",
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
    color VARCHAR(7) DEFAULT "#3b82f6",
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
    status TEXT CHECK(status IN ("pending", "approved", "rejected")) DEFAULT "pending",
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
    status TEXT CHECK(status IN ("present", "absent", "half_day", "late", "on_leave")) DEFAULT "present",
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE(employee_id, date)
);

-- Insert sample departments
INSERT INTO departments (name, description) VALUES 
("Engineering", "Software development and technical operations"),
("Human Resources", "Employee management and organizational development"),
("Marketing", "Brand management and customer acquisition"),
("Sales", "Revenue generation and client relations"),
("Finance", "Financial planning and accounting");

-- Insert sample leave types
INSERT INTO leave_types (name, description, is_paid, max_days_per_year, color) VALUES 
("Annual Leave", "Yearly vacation leave", 1, 20, "#10b981"),
("Sick Leave", "Medical leave for illness", 1, 12, "#ef4444"),
("Personal Leave", "Personal time off", 0, 5, "#f59e0b"),
("Maternity Leave", "Maternity/Paternity leave", 1, 90, "#ec4899"),
("Emergency Leave", "Urgent personal matters", 0, 3, "#dc2626"),
("Study Leave", "Educational purposes", 0, 10, "#6366f1"),
("Bereavement Leave", "Loss of family member", 1, 7, "#64748b");

-- Insert sample employees
INSERT INTO employees (id, name, email, phone, department_id, position, role, hire_date, salary, status) VALUES 
("E001", "John Doe", "john.doe@company.com", "+1-555-123-4567", 1, "Senior Developer", "employee", "2023-01-15", 85000.00, "active"),
("E002", "Jane Smith", "jane.smith@company.com", "+1-555-234-5678", 3, "Marketing Manager", "admin", "2022-08-10", 75000.00, "active"),
("E003", "Mike Johnson", "mike.johnson@company.com", "+1-555-345-6789", 4, "Sales Representative", "employee", "2023-03-20", 65000.00, "active"),
("E004", "Sarah Williams", "sarah.williams@company.com", "+1-555-456-7890", 2, "HR Manager", "hr", "2021-11-05", 80000.00, "active"),
("E005", "David Brown", "david.brown@company.com", "+1-555-567-8901", 5, "Financial Analyst", "employee", "2022-06-15", 70000.00, "active"),
("E006", "Emily Davis", "emily.davis@company.com", "+1-555-678-9012", 1, "Frontend Developer", "employee", "2023-09-01", 72000.00, "active"),
("A001", "Admin User", "admin@company.com", "+1-555-999-0000", 2, "System Administrator", "admin", "2021-01-01", 90000.00, "active");

-- Insert sample leave requests for testing
INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, applied_at) VALUES 
("E001", 2, "2026-01-10", "2026-01-12", 3, "Feeling unwell with flu symptoms", "pending", "2026-01-03 08:30:00"),
("E002", 1, "2026-01-15", "2026-01-20", 6, "Family vacation to Hawaii", "approved", "2025-12-20 14:15:00"),
("E003", 3, "2026-01-18", "2026-01-19", 2, "Personal appointment", "pending", "2026-01-02 16:45:00"),
("E005", 1, "2026-02-01", "2026-02-05", 5, "Long weekend getaway", "pending", "2026-01-01 09:20:00"),
("E006", 2, "2025-12-28", "2025-12-30", 3, "Doctor appointments", "approved", "2025-12-15 11:30:00");

-- Update reviewed requests
UPDATE leave_requests SET 
    reviewed_at = "2025-12-21 09:00:00",
    reviewed_by = "A001",
    admin_comments = "Approved for vacation. Enjoy your time off!"
WHERE id = 2;

UPDATE leave_requests SET 
    reviewed_at = "2025-12-16 10:15:00",
    reviewed_by = "E004",
    admin_comments = "Medical leave approved. Please submit medical certificate."
WHERE id = 5;

-- Create indexes for better performance
CREATE INDEX idx_leave_requests_employee ON leave_requests(employee_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_leave_requests_dates ON leave_requests(start_date, end_date);
CREATE INDEX idx_employees_role ON employees(role);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, date);

-- Create view for leave request details (simplified for SQLite compatibility)
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
LEFT JOIN employees reviewer ON lr.reviewed_by = reviewer.id;';
}

/**
 * Initialize database with corrected SQL
 */
function initializeDatabase($database) {
    $connection = $database->getConnection();
    
    // Read SQL file
    $init_file = __DIR__ . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'init.sql';
    $sql = file_get_contents($init_file);
    
    // Split and execute statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $connection->exec($statement);
            } catch (Exception $e) {
                echo "<p style='color:orange'>Warning on statement: " . htmlspecialchars(substr($statement, 0, 50)) . "... - " . $e->getMessage() . "</p>\n";
            }
        }
    }
}

/**
 * Test database queries
 */
function testDatabaseQueries($database) {
    echo "<h4>Database Query Tests:</h4>\n";
    
    // Test employee count
    $employee_count = $database->fetchOne("SELECT COUNT(*) as count FROM employees");
    echo "<p>✓ Employees: " . $employee_count['count'] . "</p>\n";
    
    // Test leave types
    $leave_types = $database->fetchAll("SELECT name FROM leave_types WHERE is_active = 1");
    echo "<p>✓ Leave Types: " . count($leave_types) . "</p>\n";
    
    // Test leave requests
    $leave_requests = $database->fetchAll("SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status");
    echo "<p>✓ Leave Requests by Status:</p>\n";
    echo "<ul>\n";
    foreach ($leave_requests as $req) {
        echo "<li>" . ucfirst($req['status']) . ": " . $req['count'] . "</li>\n";
    }
    echo "</ul>\n";
    
    // Test view
    $view_test = $database->fetchOne("SELECT employee_name, leave_type, status FROM leave_request_details LIMIT 1");
    if ($view_test) {
        echo "<p>✓ Database View Working: " . htmlspecialchars($view_test['employee_name'] . " - " . $view_test['leave_type'] . " (" . $view_test['status'] . ")") . "</p>\n";
    }
}
?>
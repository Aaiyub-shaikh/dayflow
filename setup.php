<?php
/**
 * Database Setup Script
 * Run this script to initialize the Employee Management System database
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database class
require_once __DIR__ . '/api/config/Database.php';

echo "<h2>Employee Management System - Database Setup</h2>\n";

try {
    echo "<p>Initializing database...</p>\n";
    
    // Create database instance
    $database = new Database();
    
    // Initialize database
    $database->initialize();
    
    echo "<p style='color: green;'>✓ Database initialized successfully!</p>\n";
    
    // Test database connection
    if ($database->healthCheck()) {
        echo "<p style='color: green;'>✓ Database connection is healthy</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>\n";
    }
    
    // Show database information
    $db_path = dirname(__DIR__) . '/database/ems.db';
    echo "<p><strong>Database location:</strong> {$db_path}</p>\n";
    
    // Test some queries
    echo "<h3>Database Test Results:</h3>\n";
    
    // Count employees
    $employee_count = $database->fetchOne("SELECT COUNT(*) as count FROM employees");
    echo "<p>Employees in database: {$employee_count['count']}</p>\n";
    
    // Count leave types
    $leave_type_count = $database->fetchOne("SELECT COUNT(*) as count FROM leave_types");
    echo "<p>Leave types configured: {$leave_type_count['count']}</p>\n";
    
    // Count leave requests
    $request_count = $database->fetchOne("SELECT COUNT(*) as count FROM leave_requests");
    echo "<p>Leave requests in system: {$request_count['count']}</p>\n";
    
    // Show sample data
    echo "<h3>Sample Leave Types:</h3>\n";
    $leave_types = $database->fetchAll("SELECT name, description, is_paid FROM leave_types WHERE is_active = 1 LIMIT 5");
    echo "<ul>\n";
    foreach ($leave_types as $type) {
        $paid_text = $type['is_paid'] ? 'Paid' : 'Unpaid';
        echo "<li><strong>{$type['name']}</strong> ({$paid_text}) - {$type['description']}</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>Sample Employees:</h3>\n";
    $employees = $database->fetchAll("SELECT id, name, email, position, role FROM employees WHERE status = 'active' LIMIT 5");
    echo "<ul>\n";
    foreach ($employees as $emp) {
        echo "<li><strong>{$emp['name']}</strong> ({$emp['id']}) - {$emp['position']} [{$emp['role']}]</li>\n";
    }
    echo "</ul>\n";
    
    echo "<hr>\n";
    echo "<h3>Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Open <code>index.html</code> in your web browser</li>\n";
    echo "<li>Make sure your web server (Apache/Nginx) is running with PHP support</li>\n";
    echo "<li>The API endpoints are available at <code>/api/</code></li>\n";
    echo "<li>Test the API health at: <a href='api/health'>/api/health</a></li>\n";
    echo "</ol>\n";
    
    echo "<p style='color: blue;'><strong>Setup completed successfully!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your PHP configuration and file permissions.</p>\n";
    
    // Debug information
    echo "<h3>Debug Information:</h3>\n";
    echo "<p>PHP Version: " . phpversion() . "</p>\n";
    echo "<p>SQLite Support: " . (extension_loaded('sqlite3') ? 'Yes' : 'No') . "</p>\n";
    echo "<p>PDO Support: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>\n";
    echo "<p>PDO SQLite: " . (extension_loaded('pdo_sqlite') ? 'Yes' : 'No') . "</p>\n";
    echo "<p>Current Directory: " . __DIR__ . "</p>\n";
    echo "<p>Database Directory: " . dirname(__DIR__) . "/database/</p>\n";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMS Setup</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
            line-height: 1.6; 
        }
        h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        code { 
            background: #f4f4f4; 
            padding: 2px 4px; 
            border-radius: 3px; 
        }
        ul, ol { padding-left: 30px; }
    </style>
</head>
<body>
    <a href="index.html" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;">Go to Application →</a>
</body>
</html>
<?php
/**
 * Main API Router for Development Server
 * This file routes all API requests properly
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Get request URI and method
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request path
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove /api.php from the path
$path = str_replace('/api.php', '', $path);
$path = trim($path, '/');

// Debug info (remove in production)
error_log("API Router - Method: $method, Path: $path, URI: $request_uri");

try {
    switch (true) {
        case $path === '' || $path === 'api':
            handleIndex();
            break;
            
        case $path === 'health' || $path === 'healthcheck':
            handleHealthCheck();
            break;
            
        case $path === 'init' || $path === 'initialize':
            handleInitialize();
            break;
            
        case str_starts_with($path, 'leave_requests') || str_starts_with($path, 'leaves'):
            handleLeaveRequests();
            break;
            
        default:
            sendResponse([
                'success' => false,
                'message' => 'Endpoint not found: ' . $path,
                'available_endpoints' => [
                    'GET /api.php' => 'API info',
                    'GET /api.php/health' => 'Health check',
                    'POST /api.php/init' => 'Initialize database',
                    'GET /api.php/leave_requests' => 'Get leave requests',
                    'POST /api.php/leave_requests' => 'Create leave request'
                ]
            ], 404);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ], 500);
}

/**
 * Handle index/welcome request
 */
function handleIndex() {
    sendResponse([
        'name' => 'Employee Management System API',
        'version' => '1.0',
        'status' => 'running',
        'endpoints' => [
            'GET /api.php/health' => 'Health check',
            'POST /api.php/init' => 'Initialize database',
            'GET /api.php/leave_requests' => 'Get leave requests',
            'POST /api.php/leave_requests' => 'Create leave request',
            'PUT /api.php/leave_requests' => 'Update leave request',
            'DELETE /api.php/leave_requests' => 'Delete leave request'
        ],
        'timestamp' => date('c')
    ]);
}

/**
 * Handle health check
 */
function handleHealthCheck() {
    try {
        // Include database class
        require_once __DIR__ . '/api/config/Database.php';
        
        $database = new Database();
        $db_healthy = $database->healthCheck();
        
        sendResponse([
            'status' => $db_healthy ? 'healthy' : 'unhealthy',
            'api' => 'healthy',
            'database' => $db_healthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c')
        ], $db_healthy ? 200 : 503);
        
    } catch (Exception $e) {
        sendResponse([
            'status' => 'unhealthy',
            'api' => 'unhealthy',
            'database' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ], 503);
    }
}

/**
 * Handle database initialization
 */
function handleInitialize() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed. Use POST to initialize database.'
        ], 405);
        return;
    }
    
    try {
        require_once __DIR__ . '/api/config/Database.php';
        
        $database = new Database();
        $database->initialize();
        
        sendResponse([
            'success' => true,
            'message' => 'Database initialized successfully',
            'database_path' => dirname(__DIR__) . '/database/ems.db'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => 'Database initialization failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Handle leave requests
 */
function handleLeaveRequests() {
    // Include the leave requests handler
    require_once __DIR__ . '/api/leave_requests.php';
}

/**
 * Send JSON response
 */
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check if string starts with substring (PHP < 8 compatibility)
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}
?>
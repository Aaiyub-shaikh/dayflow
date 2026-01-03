<?php
/**
 * API Entry Point
 * Routes requests to appropriate endpoints
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once __DIR__ . '/config/Database.php';

// Get request URI and method
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request path
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove base path if exists (e.g., if API is in subfolder)
if (strpos($path, 'api/') === 0) {
    $path = substr($path, 4); // Remove 'api/'
    $path = trim($path, '/');
} elseif ($path === 'api') {
    $path = '';
}

// Route requests
try {
    switch (true) {
        case $path === '' || $path === 'index.php':
            handleIndex();
            break;
            
        case $path === 'init' || $path === 'initialize':
            handleInitialize();
            break;
            
        case $path === 'health' || $path === 'healthcheck':
            handleHealthCheck();
            break;
            
        case str_starts_with($path, 'leave_requests') || str_starts_with($path, 'leaves'):
            require_once __DIR__ . '/leave_requests.php';
            break;
            
        default:
            ApiResponse::notFound('Endpoint not found: ' . $path);
    }
} catch (Exception $e) {
    error_log("API Router Error: " . $e->getMessage());
    ApiResponse::serverError('Internal server error');
}

/**
 * Handle index/welcome request
 */
function handleIndex() {
    ApiResponse::success([
        'name' => 'Employee Management System API',
        'version' => API_VERSION,
        'endpoints' => [
            'GET /api/health' => 'Health check',
            'POST /api/init' => 'Initialize database',
            'GET /api/leave_requests' => 'Get leave requests',
            'POST /api/leave_requests' => 'Create leave request',
            'PUT /api/leave_requests?id=X' => 'Update leave request',
            'DELETE /api/leave_requests?id=X' => 'Delete leave request',
            'GET /api/leave_requests?leave_types=1' => 'Get leave types',
            'GET /api/leave_requests?stats=1' => 'Get statistics'
        ],
        'timestamp' => date('c')
    ], 'Welcome to the EMS API');
}

/**
 * Handle database initialization
 */
function handleInitialize() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed. Use POST to initialize database.', 405);
    }
    
    try {
        $database = new Database();
        $database->initialize();
        
        ApiResponse::success([
            'database_path' => dirname(__DIR__) . '/database/ems.db',
            'initialized' => true
        ], 'Database initialized successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Database initialization failed: ' . $e->getMessage());
    }
}

/**
 * Handle health check
 */
function handleHealthCheck() {
    try {
        $database = new Database();
        $db_healthy = $database->healthCheck();
        
        $status = [
            'api' => 'healthy',
            'database' => $db_healthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'version' => API_VERSION
        ];
        
        $overall_status = $db_healthy ? 'healthy' : 'unhealthy';
        $status_code = $db_healthy ? 200 : 503;
        
        ApiResponse::json([
            'status' => $overall_status,
            'data' => $status
        ], $status_code);
        
    } catch (Exception $e) {
        ApiResponse::json([
            'status' => 'unhealthy',
            'data' => [
                'api' => 'unhealthy',
                'database' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ]
        ], 503);
    }
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
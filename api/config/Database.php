<?php
/**
 * Database Configuration and Connection Class
 * Handles SQLite database connection and operations
 */

class Database {
    private $db_path;
    private $connection;
    
    public function __construct($db_path = null) {
        // Set default database path relative to this file
        $this->db_path = $db_path ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'ems.db';
    }
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        if ($this->connection === null) {
            try {
                // Create database directory if it doesn't exist
                $db_dir = dirname($this->db_path);
                if (!is_dir($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }
                
                // Create PDO connection
                $this->connection = new PDO("sqlite:" . $this->db_path);
                
                // Set error mode to exceptions
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Enable foreign keys
                $this->connection->exec("PRAGMA foreign_keys = ON");
                
                // Set default fetch mode
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Initialize database with schema
     */
    public function initialize() {
        try {
            $connection = $this->getConnection();
            
            // Check if database is already initialized
            $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='employees'");
            if ($stmt->fetch()) {
                return true; // Already initialized
            }
            
            // Read and execute schema file
            $schema_file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'init.sql';
            if (!file_exists($schema_file)) {
                throw new Exception("Schema file not found: " . $schema_file);
            }
            
            $schema = file_get_contents($schema_file);
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $connection->exec($statement);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Execute a prepared statement
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Check database connection health
     */
    public function healthCheck() {
        try {
            $this->getConnection()->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Configuration constants
define('API_VERSION', '1.0');
define('API_BASE_URL', '/api/v1');
define('CORS_ORIGINS', ['http://localhost', 'http://127.0.0.1']);
define('MAX_REQUEST_SIZE', 5 * 1024 * 1024); // 5MB
define('SESSION_TIMEOUT', 3600); // 1 hour

/**
 * API Response Helper
 */
class ApiResponse {
    /**
     * Send JSON response
     */
    public static function json($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $status_code = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], $status_code);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $status_code = 400, $errors = null) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ], $status_code);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send internal server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
}

/**
 * Request Validator
 */
class Validator {
    /**
     * Validate required fields
     */
    public static function required($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = "The {$field} field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate date format
     */
    public static function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Authentication Helper
 */
class Auth {
    /**
     * Simple session-based authentication (for demo purposes)
     * In production, use JWT tokens or similar
     */
    public static function getCurrentUser() {
        // For demo, return a default user
        // In production, validate session/token and return actual user
        return [
            'id' => 'E001',
            'name' => 'John Doe',
            'email' => 'john.doe@company.com',
            'role' => 'employee'
        ];
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return in_array($user['role'], ['admin', 'hr']);
    }
    
    /**
     * Require admin access
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            ApiResponse::forbidden('Admin access required');
        }
    }
}
?>
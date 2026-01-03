<?php
/**
 * Leave Requests API Endpoints
 * Handles all leave request operations
 */

// Include configuration and database
require_once __DIR__ . '/config/Database.php';

// Initialize database
try {
    $database = new Database();
    $database->initialize();
} catch (Exception $e) {
    ApiResponse::serverError('Database initialization failed: ' . $e->getMessage());
}

// Get request method and parse input
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$query_params = $_GET;

// Route requests based on method and parameters
try {
    switch ($method) {
        case 'GET':
            handleGetRequests($database, $query_params);
            break;
            
        case 'POST':
            handlePostRequest($database, $input);
            break;
            
        case 'PUT':
            handlePutRequest($database, $input, $query_params);
            break;
            
        case 'DELETE':
            handleDeleteRequest($database, $query_params);
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    ApiResponse::serverError('An error occurred while processing your request');
}

/**
 * Handle GET requests - Fetch leave requests
 */
function handleGetRequests($database, $params) {
    $current_user = Auth::getCurrentUser();
    
    // Check for specific leave request ID
    if (isset($params['id'])) {
        getLeaveRequestById($database, $params['id'], $current_user);
        return;
    }
    
    // Check for employee-specific requests
    if (isset($params['employee_id'])) {
        // Only allow users to see their own requests unless they're admin
        if ($params['employee_id'] !== $current_user['id'] && !Auth::isAdmin()) {
            ApiResponse::forbidden('You can only view your own leave requests');
        }
        getLeaveRequestsByEmployee($database, $params['employee_id']);
        return;
    }
    
    // Check for leave types request
    if (isset($params['leave_types'])) {
        getLeaveTypes($database);
        return;
    }
    
    // Check for dashboard stats
    if (isset($params['stats'])) {
        getLeaveStatistics($database, $current_user);
        return;
    }
    
    // Default: Get all requests (admin) or user's requests (employee)
    if (Auth::isAdmin()) {
        getAllLeaveRequests($database, $params);
    } else {
        getLeaveRequestsByEmployee($database, $current_user['id']);
    }
}

/**
 * Get specific leave request by ID
 */
function getLeaveRequestById($database, $id, $current_user) {
    $sql = "SELECT * FROM leave_request_details WHERE id = ?";
    $request = $database->fetchOne($sql, [$id]);
    
    if (!$request) {
        ApiResponse::notFound('Leave request not found');
    }
    
    // Check permissions
    if ($request['employee_id'] !== $current_user['id'] && !Auth::isAdmin()) {
        ApiResponse::forbidden('You can only view your own leave requests');
    }
    
    ApiResponse::success($request, 'Leave request retrieved successfully');
}

/**
 * Get leave requests by employee
 */
function getLeaveRequestsByEmployee($database, $employee_id) {
    $sql = "SELECT * FROM leave_request_details WHERE employee_id = ? ORDER BY applied_at DESC";
    $requests = $database->fetchAll($sql, [$employee_id]);
    
    ApiResponse::success($requests, 'Leave requests retrieved successfully');
}

/**
 * Get all leave requests (admin only)
 */
function getAllLeaveRequests($database, $params) {
    Auth::requireAdmin();
    
    $where_conditions = [];
    $where_params = [];
    
    // Filter by status
    if (isset($params['status']) && !empty($params['status'])) {
        $where_conditions[] = "status = ?";
        $where_params[] = $params['status'];
    }
    
    // Filter by department
    if (isset($params['department']) && !empty($params['department'])) {
        $where_conditions[] = "department = ?";
        $where_params[] = $params['department'];
    }
    
    // Filter by date range
    if (isset($params['start_date']) && !empty($params['start_date'])) {
        $where_conditions[] = "start_date >= ?";
        $where_params[] = $params['start_date'];
    }
    
    if (isset($params['end_date']) && !empty($params['end_date'])) {
        $where_conditions[] = "end_date <= ?";
        $where_params[] = $params['end_date'];
    }
    
    // Build query
    $sql = "SELECT * FROM leave_request_details";
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $sql .= " ORDER BY applied_at DESC";
    
    // Add pagination
    $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
    $limit = isset($params['limit']) ? min(100, max(1, intval($params['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    $sql .= " LIMIT ? OFFSET ?";
    $where_params[] = $limit;
    $where_params[] = $offset;
    
    $requests = $database->fetchAll($sql, $where_params);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM leave_request_details";
    if (!empty($where_conditions)) {
        $count_sql .= " WHERE " . implode(' AND ', $where_conditions);
        $total_count = $database->fetchOne($count_sql, array_slice($where_params, 0, -2))['total'];
    } else {
        $total_count = $database->fetchOne($count_sql)['total'];
    }
    
    ApiResponse::success([
        'requests' => $requests,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_count,
            'pages' => ceil($total_count / $limit)
        ]
    ], 'Leave requests retrieved successfully');
}

/**
 * Get leave types
 */
function getLeaveTypes($database) {
    $sql = "SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name";
    $leave_types = $database->fetchAll($sql);
    
    ApiResponse::success($leave_types, 'Leave types retrieved successfully');
}

/**
 * Get leave statistics
 */
function getLeaveStatistics($database, $current_user) {
    if (Auth::isAdmin()) {
        // Admin stats
        $stats = [
            'pending_requests' => $database->fetchOne("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'")['count'],
            'approved_requests' => $database->fetchOne("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'approved'")['count'],
            'rejected_requests' => $database->fetchOne("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'rejected'")['count'],
            'total_requests' => $database->fetchOne("SELECT COUNT(*) as count FROM leave_requests")['count'],
            'employees_on_leave' => $database->fetchOne("
                SELECT COUNT(DISTINCT employee_id) as count FROM leave_requests 
                WHERE status = 'approved' AND start_date <= DATE('now') AND end_date >= DATE('now')
            ")['count']
        ];
    } else {
        // Employee stats
        $stats = [
            'pending_requests' => $database->fetchOne(
                "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'pending'",
                [$current_user['id']]
            )['count'],
            'approved_requests' => $database->fetchOne(
                "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'approved'",
                [$current_user['id']]
            )['count'],
            'total_requests' => $database->fetchOne(
                "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ?",
                [$current_user['id']]
            )['count'],
            'days_used_this_year' => $database->fetchOne("
                SELECT COALESCE(SUM(total_days), 0) as days 
                FROM leave_requests 
                WHERE employee_id = ? AND status = 'approved' 
                AND strftime('%Y', start_date) = strftime('%Y', 'now')
            ", [$current_user['id']])['days']
        ];
    }
    
    ApiResponse::success($stats, 'Statistics retrieved successfully');
}

/**
 * Handle POST requests - Create new leave request
 */
function handlePostRequest($database, $input) {
    // Validate input
    $required_fields = ['leave_type_id', 'start_date', 'end_date', 'reason'];
    $validation_errors = Validator::required($input, $required_fields);
    
    if (!empty($validation_errors)) {
        ApiResponse::validationError($validation_errors);
    }
    
    // Sanitize input
    $input = Validator::sanitize($input);
    
    // Additional validation
    if (!Validator::isValidDate($input['start_date'])) {
        $validation_errors['start_date'] = 'Invalid start date format';
    }
    
    if (!Validator::isValidDate($input['end_date'])) {
        $validation_errors['end_date'] = 'Invalid end date format';
    }
    
    if (strtotime($input['end_date']) < strtotime($input['start_date'])) {
        $validation_errors['end_date'] = 'End date must be after start date';
    }
    
    if (!empty($validation_errors)) {
        ApiResponse::validationError($validation_errors);
    }
    
    // Validate leave type exists
    $leave_type = $database->fetchOne("SELECT * FROM leave_types WHERE id = ? AND is_active = 1", [$input['leave_type_id']]);
    if (!$leave_type) {
        ApiResponse::validationError(['leave_type_id' => 'Invalid leave type']);
    }
    
    // Calculate total days
    $start_date = new DateTime($input['start_date']);
    $end_date = new DateTime($input['end_date']);
    $total_days = $end_date->diff($start_date)->days + 1;
    
    // Check for overlapping requests
    $current_user = Auth::getCurrentUser();
    $overlapping = $database->fetchOne("
        SELECT COUNT(*) as count FROM leave_requests 
        WHERE employee_id = ? AND status IN ('pending', 'approved')
        AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) 
        OR (start_date <= ? AND end_date >= ?))
    ", [
        $current_user['id'],
        $input['start_date'], $input['end_date'],
        $input['start_date'], $input['end_date'],
        $input['start_date'], $input['end_date']
    ]);
    
    if ($overlapping['count'] > 0) {
        ApiResponse::validationError(['dates' => 'You already have a leave request for this period']);
    }
    
    // Create leave request
    try {
        $database->beginTransaction();
        
        $sql = "INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $database->execute($sql, [
            $current_user['id'],
            $input['leave_type_id'],
            $input['start_date'],
            $input['end_date'],
            $total_days,
            $input['reason']
        ]);
        
        $leave_request_id = $database->lastInsertId();
        
        // Get the created request with details
        $created_request = $database->fetchOne(
            "SELECT * FROM leave_request_details WHERE id = ?",
            [$leave_request_id]
        );
        
        $database->commit();
        
        ApiResponse::success($created_request, 'Leave request submitted successfully', 201);
        
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }
}

/**
 * Handle PUT requests - Update leave request (approve/reject)
 */
function handlePutRequest($database, $input, $params) {
    Auth::requireAdmin(); // Only admins can update requests
    
    if (!isset($params['id'])) {
        ApiResponse::error('Leave request ID is required');
    }
    
    $leave_request_id = $params['id'];
    
    // Validate input
    $required_fields = ['status'];
    $validation_errors = Validator::required($input, $required_fields);
    
    if (!empty($validation_errors)) {
        ApiResponse::validationError($validation_errors);
    }
    
    $input = Validator::sanitize($input);
    
    // Validate status
    if (!in_array($input['status'], ['approved', 'rejected'])) {
        ApiResponse::validationError(['status' => 'Status must be approved or rejected']);
    }
    
    // Check if leave request exists and is pending
    $leave_request = $database->fetchOne(
        "SELECT * FROM leave_requests WHERE id = ?",
        [$leave_request_id]
    );
    
    if (!$leave_request) {
        ApiResponse::notFound('Leave request not found');
    }
    
    if ($leave_request['status'] !== 'pending') {
        ApiResponse::error('Only pending requests can be updated');
    }
    
    try {
        $database->beginTransaction();
        
        $current_user = Auth::getCurrentUser();
        
        // Update leave request
        $sql = "UPDATE leave_requests 
                SET status = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ?, admin_comments = ?
                WHERE id = ?";
        
        $database->execute($sql, [
            $input['status'],
            $current_user['id'],
            $input['admin_comments'] ?? null,
            $leave_request_id
        ]);
        
        // Get updated request
        $updated_request = $database->fetchOne(
            "SELECT * FROM leave_request_details WHERE id = ?",
            [$leave_request_id]
        );
        
        $database->commit();
        
        $message = $input['status'] === 'approved' ? 'Leave request approved successfully' : 'Leave request rejected successfully';
        ApiResponse::success($updated_request, $message);
        
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }
}

/**
 * Handle DELETE requests - Cancel leave request
 */
function handleDeleteRequest($database, $params) {
    if (!isset($params['id'])) {
        ApiResponse::error('Leave request ID is required');
    }
    
    $leave_request_id = $params['id'];
    $current_user = Auth::getCurrentUser();
    
    // Check if leave request exists
    $leave_request = $database->fetchOne(
        "SELECT * FROM leave_requests WHERE id = ?",
        [$leave_request_id]
    );
    
    if (!$leave_request) {
        ApiResponse::notFound('Leave request not found');
    }
    
    // Check permissions (only own requests or admin)
    if ($leave_request['employee_id'] !== $current_user['id'] && !Auth::isAdmin()) {
        ApiResponse::forbidden('You can only cancel your own leave requests');
    }
    
    // Only allow cancellation of pending requests
    if ($leave_request['status'] !== 'pending') {
        ApiResponse::error('Only pending requests can be cancelled');
    }
    
    try {
        $database->execute("DELETE FROM leave_requests WHERE id = ?", [$leave_request_id]);
        ApiResponse::success(null, 'Leave request cancelled successfully');
    } catch (Exception $e) {
        throw $e;
    }
}
?>
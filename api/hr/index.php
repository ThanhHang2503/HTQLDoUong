<?php
/**
 * HR API Endpoints
 * RESTful API for HR Management System
 * 
 * Usage:
 * GET  /api/hr/employees
 * POST /api/hr/employees
 * GET  /api/hr/employees/{id}
 * PUT  /api/hr/employees/{id}
 * DELETE /api/hr/employees/{id}
 * POST /api/hr/employees/{id}/position
 * GET  /api/hr/salary/records
 * POST /api/hr/salary/calculate
 * GET  /api/hr/leave/pending
 * POST /api/hr/leave/request
 * POST /api/hr/leave/{id}/approve
 * POST /api/hr/leave/{id}/reject
 * POST /api/hr/resignation/request
 * POST /api/hr/resignation/{id}/approve
 * GET  /api/hr/stats/monthly
 */

header('Content-Type: application/json; charset=utf-8');

// Load configuration
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/Models.php';
require_once __DIR__ . '/Repositories.php';
require_once __DIR__ . '/Services.php';
require_once __DIR__ . '/Middleware.php';

use HR\Middleware\HRAuthMiddleware;
use HR\Services\{EmployeeService, SalaryService, LeaveService, ResignationService, HRStatisticsService};

// Enable error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check authentication
HRAuthMiddleware::requireHRAccess();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = array_filter(explode('/', $path));

// Remove /api/hr from path
$endpoint = implode('/', array_slice($path_parts, -2));

// Initialize services
$employeeService = new EmployeeService($conn);
$salaryService = new SalaryService($conn);
$leaveService = new LeaveService($conn);
$resignationService = new ResignationService($conn);
$statsService = new HRStatisticsService($conn);

// Parse request body
$input = json_decode(file_get_contents('php://input'), true) ?? $_GET;

// Route API endpoints
try {
    switch (true) {
        // ==================== EMPLOYEE ENDPOINTS ====================
        
        // GET /api/hr/employees - Lấy danh sách nhân viên
        case $method === 'GET' && preg_match('/^employees$/', $endpoint):
            $role_id = $input['role_id'] ?? null;
            $employees = $employeeService->getEmployees($role_id);
            exit(json_encode([
                'success' => true,
                'data' => $employees,
                'count' => count($employees)
            ]));
        
        // POST /api/hr/employees - Tạo nhân viên mới
        case $method === 'POST' && preg_match('/^employees$/', $endpoint):
            if (!$input['full_name'] || !$input['email']) {
                http_response_code(400);
                exit(json_encode([
                    'success' => false,
                    'message' => 'Vui lòng cung cấp full_name và email'
                ]));
            }
            $result = $employeeService->createEmployee($input);
            exit(json_encode($result));
        
        // GET /api/hr/employees/{id} - Lấy chi tiết nhân viên
        case $method === 'GET' && preg_match('/^employees\/(\d+)$/', $endpoint, $m):
            $employee = $employeeService->getEmployee((int)$m[1]);
            if (!$employee) {
                http_response_code(404);
                exit(json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']));
            }
            exit(json_encode(['success' => true, 'data' => $employee]));
        
        // PUT /api/hr/employees/{id} - Cập nhật nhân viên
        case $method === 'PUT' && preg_match('/^employees\/(\d+)$/', $endpoint, $m):
            $result = $employeeService->updateEmployee((int)$m[1], $input);
            exit(json_encode($result));
        
        // DELETE /api/hr/employees/{id} - Xóa nhân viên
        case $method === 'DELETE' && preg_match('/^employees\/(\d+)$/', $endpoint, $m):
            $result = $employeeService->deleteEmployee((int)$m[1]);
            exit(json_encode($result));
        
        // POST /api/hr/employees/{id}/position - Đổi chức vụ
        case $method === 'POST' && preg_match('/^employees\/(\d+)\/position$/', $endpoint, $m):
            if (!$input['position_id']) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Vui lòng cung cấp position_id']));
            }
            $result = $employeeService->changePosition((int)$m[1], (int)$input['position_id'], $input['reason'] ?? '');
            exit(json_encode($result));
        
        // GET /api/hr/employees/{id}/positions - Lịch sử chức vụ
        case $method === 'GET' && preg_match('/^employees\/(\d+)\/positions$/', $endpoint, $m):
            $history = $employeeService->getPositionHistory((int)$m[1]);
            exit(json_encode(['success' => true, 'data' => $history]));
        
        // ==================== SALARY ENDPOINTS ====================
        
        // GET /api/hr/salary/records - Lấy bản ghi lương
        case $method === 'GET' && preg_match('/^salary\/records$/', $endpoint):
            $year = (int)($input['year'] ?? date('Y'));
            $month = (int)($input['month'] ?? date('m'));
            $records = $salaryService->getSalaryRecords($year, $month);
            exit(json_encode([
                'success' => true,
                'data' => $records,
                'month' => $month,
                'year' => $year
            ]));
        
        // POST /api/hr/salary/calculate - Tính lương
        case $method === 'POST' && preg_match('/^salary\/calculate$/', $endpoint):
            if (!$input['account_id'] || !$input['month'] || !$input['year']) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Thiếu tham số']));
            }
            $result = $salaryService->calculateSalary(
                (int)$input['account_id'],
                (int)$input['month'],
                (int)$input['year'],
                (int)($input['allowance'] ?? 0),
                (int)($input['bonus'] ?? 0),
                (int)($input['deductions'] ?? 0)
            );
            exit(json_encode($result));
        
        // POST /api/hr/salary/save - Lưu bản ghi lương
        case $method === 'POST' && preg_match('/^salary\/save$/', $endpoint):
            if (!$input['account_id'] || !$input['month'] || !$input['year']) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Thiếu tham số']));
            }
            $result = $salaryService->saveSalary(
                (int)$input['account_id'],
                (int)$input['month'],
                (int)$input['year'],
                $input
            );
            exit(json_encode($result));
        
        // GET /api/hr/salary/stats - Thống kê lương
        case $method === 'GET' && preg_match('/^salary\/stats$/', $endpoint):
            $year = (int)($input['year'] ?? date('Y'));
            $month = (int)($input['month'] ?? date('m'));
            $stats = $salaryService->getSalaryStats($year, $month);
            exit(json_encode([
                'success' => true,
                'data' => $stats,
                'month' => $month,
                'year' => $year
            ]));
        
        // ==================== LEAVE ENDPOINTS ====================
        
        // GET /api/hr/leave/pending - Yêu cầu chưa duyệt
        case $method === 'GET' && preg_match('/^leave\/pending$/', $endpoint):
            $pending = $leaveService->getPendingLeaves();
            exit(json_encode(['success' => true, 'data' => $pending, 'count' => count($pending)]));
        
        // POST /api/hr/leave/request - Tạo yêu cầu nghỉ phép
        case $method === 'POST' && preg_match('/^leave\/request$/', $endpoint):
            if (!$input['from_date'] || !$input['to_date'] || !$input['reason']) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Thiếu tham số']));
            }
            $result = $leaveService->requestLeave(HRAuthMiddleware::getCurrentUserId(), $input);
            exit(json_encode($result));
        
        // GET /api/hr/leave/{id}/history - Lịch sử nghỉ phép
        case $method === 'GET' && preg_match('/^leave\/(\d+)\/history$/', $endpoint, $m):
            $history = $leaveService->getLeaveHistory((int)$m[1]);
            exit(json_encode(['success' => true, 'data' => $history]));
        
        // POST /api/hr/leave/{id}/approve - Duyệt yêu cầu
        case $method === 'POST' && preg_match('/^leave\/(\d+)\/approve$/', $endpoint, $m):
            $result = $leaveService->approveLeave((int)$m[1], HRAuthMiddleware::getCurrentUserId());
            exit(json_encode($result));
        
        // POST /api/hr/leave/{id}/reject - Từ chối yêu cầu
        case $method === 'POST' && preg_match('/^leave\/(\d+)\/reject$/', $endpoint, $m):
            $result = $leaveService->rejectLeave((int)$m[1], HRAuthMiddleware::getCurrentUserId(), $input['notes'] ?? '');
            exit(json_encode($result));
        
        // ==================== RESIGNATION ENDPOINTS ====================
        
        // GET /api/hr/resignation/pending - Yêu cầu chưa duyệt
        case $method === 'GET' && preg_match('/^resignation\/pending$/', $endpoint):
            $pending = $resignationService->getPendingResignations();
            exit(json_encode(['success' => true, 'data' => $pending, 'count' => count($pending)]));
        
        // POST /api/hr/resignation/request - Tạo yêu cầu nghỉ việc
        case $method === 'POST' && preg_match('/^resignation\/request$/', $endpoint):
            if (!$input['effective_date'] || !$input['reason']) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Thiếu tham số']));
            }
            $result = $resignationService->requestResignation(HRAuthMiddleware::getCurrentUserId(), $input);
            exit(json_encode($result));
        
        // POST /api/hr/resignation/{id}/approve - Duyệt yêu cầu
        case $method === 'POST' && preg_match('/^resignation\/(\d+)\/approve$/', $endpoint, $m):
            $result = $resignationService->approveResignation((int)$m[1], HRAuthMiddleware::getCurrentUserId());
            exit(json_encode($result));
        
        // POST /api/hr/resignation/{id}/reject - Từ chối yêu cầu
        case $method === 'POST' && preg_match('/^resignation\/(\d+)\/reject$/', $endpoint, $m):
            $result = $resignationService->rejectResignation((int)$m[1], HRAuthMiddleware::getCurrentUserId(), $input['notes'] ?? '');
            exit(json_encode($result));
        
        // ==================== STATISTICS ENDPOINTS ====================
        
        // GET /api/hr/stats/monthly - Thống kê hàng tháng
        case $method === 'GET' && preg_match('/^stats\/monthly$/', $endpoint):
            $year = (int)($input['year'] ?? date('Y'));
            $month = (int)($input['month'] ?? date('m'));
            $stats = $statsService->getMonthlyStats($year, $month);
            exit(json_encode(['success' => true, 'data' => $stats]));
        
        // GET /api/hr/stats/movement - Biến động nhân sự
        case $method === 'GET' && preg_match('/^stats\/movement$/', $endpoint):
            $year = (int)($input['year'] ?? date('Y'));
            $movement = $statsService->getEmployeeMovement($year);
            exit(json_encode(['success' => true, 'data' => $movement]));
        
        // Default 404
        default:
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Endpoint không tìm thấy']));
    }
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Lỗi server',
        'error' => $e->getMessage()
    ]));
}

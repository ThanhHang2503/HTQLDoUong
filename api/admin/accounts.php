<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';

require_once __DIR__ . '/../../src/hr/Models.php';
require_once __DIR__ . '/../../src/hr/Repositories.php';
require_once __DIR__ . '/../../src/hr/Services.php';

use HR\Services\EmployeeService;

// Bảo mật API, chỉ Admin mới được dùng
if (!isLoggedIn() || !can(AppPermission::MANAGE_ACCOUNTS)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
global $conn;

$employeeService = new EmployeeService($conn);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $sql = "SELECT a.account_id, a.full_name, a.email, a.role_id, a.position_id, r.name AS role_name, a.hr_status, a.system_status, a.created_at,
                       a.phone, a.address, a.birth_date, a.gender, a.hire_date
                FROM accounts a
                JOIN roles r ON r.id = a.role_id
                WHERE r.name != 'admin'
                ORDER BY a.account_id DESC";
        $result = mysqli_query($conn, $sql);
        $accounts = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        
        // Danh sách chức vụ cho phép chọn: loại bỏ chức vụ Quản trị viên (position_id=1)
        // Admin chỉ được tạo/quản lý trong trang admin riêng biệt
        $pos_result = mysqli_query($conn, "SELECT p.position_id, p.position_name, p.base_salary
                                           FROM positions p
                                           WHERE p.position_id != 1
                                             AND p.is_active = 1
                                           ORDER BY p.position_name ASC");
        $positions = $pos_result ? mysqli_fetch_all($pos_result, MYSQLI_ASSOC) : [];

        echo json_encode(['success' => true, 'data' => $accounts, 'positions' => $positions]);
        exit;
    }

    if ($method === 'POST') {
        // Cả Admin và Manager đều có quyền thêm nhân viên mới (Manager thêm yêu cầu, Admin duyệt)
        if (currentRole() !== AppRole::ADMIN && currentRole() !== AppRole::MANAGER) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm tài khoản mới']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        // Không cho phép thêm Admin qua giao diện nhân sự
        if (($input['position_id'] ?? 0) == 1) {
            echo json_encode(['success' => false, 'message' => 'Không được phép tạo tài khoản Quản trị viên tại đây']);
            exit;
        }

        // Quyết định trạng thái hệ thống: Manager thêm => pending, Admin thêm => active
        $input['system_status'] = (currentRole() === AppRole::ADMIN) ? 'active' : 'pending';

        // Gọi Service xử lý tập trung
        $result = $employeeService->createEmployee($input);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Thêm tài khoản thành công', 'id' => $result['account_id']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
        exit;
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $account_id = (int)($input['account_id'] ?? 0);

        if ($account_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tài khoản không hợp lệ']);
            exit;
        }

        // Chặn tự sửa chính mình qua API nhân sự
        if ($account_id === currentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không thể tự sửa thông tin phân quyền của chính mình qua chức năng này']);
            exit;
        }

        // Chặn cấp quyền Admin
        if (($input['position_id'] ?? 0) == 1) {
            echo json_encode(['success' => false, 'message' => 'Không được phép cấp quyền Quản trị viên tại đây']);
            exit;
        }

        // Gọi Service xử lý tập trung
        $result = $employeeService->updateEmployee($account_id, $input);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
        exit;
    }

    if ($method === 'PATCH') {
        $data = json_decode(file_get_contents('php://input'), true);
        $account_id = (int)($data['account_id'] ?? 0);
        $target = $data['target'] ?? ''; // 'hr' or 'system'
        $status = mysqli_real_escape_string($conn, $data['status'] ?? '');
        $current_role = currentRole();

        if ($account_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        if ($account_id === currentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không thể thay đổi trạng thái của chính mình']);
            exit;
        }

        // Fetch target account details
        $res = mysqli_query($conn, "SELECT role_id, (SELECT name FROM roles WHERE id = role_id LIMIT 1) as role_name FROM accounts WHERE account_id = $account_id");
        $targetRow = mysqli_fetch_assoc($res);
        if (!$targetRow) {
            echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại']);
            exit;
        }
        $target_role_name = $targetRow['role_name'];

        if ($target === 'hr') {
            // Only HR (Manager) can change working status. Admin is restricted by business rule.
            if ($current_role !== AppRole::MANAGER) {
                echo json_encode(['success' => false, 'message' => 'Chỉ Quản lý nhân sự mới được phép thay đổi trạng thái làm việc']);
                exit;
            }
            if (!in_array($status, ['active', 'resigned', 'on_leave'], true)) {
                echo json_encode(['success' => false, 'message' => 'Trạng thái nhân sự không hợp lệ']);
                exit;
            }

            // Auto-lock system access if HR sets someone to resigned (except if target is an Admin)
            $autoLockSystem = false;
            if ($status === 'resigned') {
                if ($target_role_name !== AppRole::ADMIN) {
                     $autoLockSystem = true;
                }
            }

            $sql = "UPDATE accounts SET hr_status = '$status'" . ($autoLockSystem ? ", system_status = 'locked'" : "") . " WHERE account_id = $account_id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái làm việc']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật trạng thái làm việc']);
            }
            exit;

        } elseif ($target === 'system') {
            // Only Admin can change System Status
            if ($current_role !== AppRole::ADMIN) {
                echo json_encode(['success' => false, 'message' => 'Chỉ Administrator mới có quyền thay đổi truy cập hệ thống']);
                exit;
            }
            if (!in_array($status, ['active', 'locked', 'disabled', 'pending'], true)) {
                echo json_encode(['success' => false, 'message' => 'Trạng thái hệ thống không hợp lệ']);
                exit;
            }

            // Prevent locking/disabling other admins
            if ($target_role_name === AppRole::ADMIN && $status !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Không được phép khóa truy cập của một quản trị viên khác']);
                exit;
            }

            $sql = "UPDATE accounts SET system_status = '$status' WHERE account_id = $account_id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái truy cập hệ thống']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật trạng thái hệ thống']);
            }
            exit;

        } else {
            echo json_encode(['success' => false, 'message' => 'Tham số target không hợp lệ']);
            exit;
        }
    }

    if ($method === 'DELETE') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Không hỗ trợ xóa tài khoản.']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

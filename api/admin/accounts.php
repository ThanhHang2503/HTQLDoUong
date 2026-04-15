<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';

// Bảo mật API, chỉ Admin mới được dùng
if (!isLoggedIn() || !can(AppPermission::MANAGE_ACCOUNTS)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
global $conn;

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
        $data = json_decode(file_get_contents('php://input'), true);
        $full_name = trim(mysqli_real_escape_string($conn, $data['full_name'] ?? ''));
        $email = trim(mysqli_real_escape_string($conn, $data['email'] ?? ''));
        $password = trim($data['password'] ?? '');
        $position_id = (int)($data['position_id'] ?? 0);
        $role_id = $position_id; // Thống nhất Vai trò = Chức vụ
        
        $phone = trim(mysqli_real_escape_string($conn, $data['phone'] ?? ''));
        $address = trim(mysqli_real_escape_string($conn, $data['address'] ?? ''));
        $birth_date = !empty($data['birth_date']) ? "'".mysqli_real_escape_string($conn, $data['birth_date'])."'" : "NULL";
        $gender = mysqli_real_escape_string($conn, $data['gender'] ?? 'nam');
        $hire_date = !empty($data['hire_date']) ? "'".mysqli_real_escape_string($conn, $data['hire_date'])."'" : "CURDATE()";
        $position_id = (int)($data['position_id'] ?? 0);

        if (!$full_name || !$email || !$password || !$position_id) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ thông tin']);
            exit;
        }

        // Không cho phép thêm Admin qua giao diện nhân sự
        if ($role_id == 1) {
            echo json_encode(['success' => false, 'message' => 'Không được phép tạo tài khoản Quản trị viên tại đây']);
            exit;
        }

        // Check trùng email
        $check = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại trong hệ thống']);
            exit;
        }

        $hashed_password = md5($password);
        $hr_status = 'active'; 
        
        $system_status = (currentRole() === AppRole::ADMIN) ? 'active' : 'pending';

        $sql = "INSERT INTO accounts (full_name, email, password, role_id, hr_status, system_status, phone, address, birth_date, gender, hire_date, position_id) 
                VALUES ('$full_name', '$email', '$hashed_password', $role_id, '$hr_status', '$system_status', '$phone', '$address', $birth_date, '$gender', $hire_date, " . ($position_id > 0 ? $position_id : "NULL") . ")";
        
        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            // Tự động tạo lịch sử chức vụ nếu có chọn chức vụ
            if ($position_id > 0) {
                $start_history = $hire_date;
                mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason, created_by)
                                      VALUES ($new_id, $position_id, $start_history, 'Khởi tạo nhân sự mới', " . currentUserId() . ")");
            }
            echo json_encode(['success' => true, 'message' => 'Thêm tài khoản thành công', 'id' => $new_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thêm tài khoản', 'error' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $account_id = (int)($data['account_id'] ?? 0);
        $full_name = trim(mysqli_real_escape_string($conn, $data['full_name'] ?? ''));
        $email = trim(mysqli_real_escape_string($conn, $data['email'] ?? ''));
        $password = trim($data['password'] ?? '');
        $position_id = (int)($data['position_id'] ?? 0);
        $role_id = $position_id; // Thống nhất Vai trò = Chức vụ
        
        $phone = trim(mysqli_real_escape_string($conn, $data['phone'] ?? ''));
        $address = trim(mysqli_real_escape_string($conn, $data['address'] ?? ''));
        $birth_date = !empty($data['birth_date']) ? "'".mysqli_real_escape_string($conn, $data['birth_date'])."'" : "NULL";
        $gender = mysqli_real_escape_string($conn, $data['gender'] ?? 'nam');
        $hire_date = !empty($data['hire_date']) ? "'".mysqli_real_escape_string($conn, $data['hire_date'])."'" : "CURDATE()";
        $position_id = (int)($data['position_id'] ?? 0);

        if ($account_id <= 0 || !$full_name || !$email || !$position_id) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Không cho phép chuyển sang Admin qua giao diện nhân sự
        if ($role_id == 1) {
            echo json_encode(['success' => false, 'message' => 'Không được phép cấp quyền Quản trị viên tại đây']);
            exit;
        }

        if ($account_id === currentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không thể tự sửa thông tin phân quyền của chính mình qua chức năng này']);
            exit;
        }

        // Check trùng email nhưng bỏ qua email của chính tài khoản này
        $check = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email = '$email' AND account_id != $account_id");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi tài khoản khác']);
            exit;
        }

        $password_sql = "";
        if ($password !== '') {
            $hashed_password = md5($password);
            $password_sql = ", password = '$hashed_password'";
        }

        // Phân quyền: Chỉ Admin mới được đổi role/position
        // Đồng bộ role_id = position_id để đảm bảo nhất quán (Plan 2)
        $role_sql = "";
        if (currentRole() === AppRole::ADMIN) {
            $role_sql = ", role_id = $role_id";
        }

        $sql = "UPDATE accounts 
                SET full_name = '$full_name', email = '$email', phone = '$phone', address = '$address', 
                    birth_date = $birth_date, gender = '$gender', hire_date = $hire_date,
                    position_id = " . ($position_id > 0 ? $position_id : "NULL") . "
                    $role_sql $password_sql 
                WHERE account_id = $account_id";
        
        // Kiểm tra xem position có thay đổi không để cập nhật history
        $oldRes = mysqli_query($conn, "SELECT position_id FROM accounts WHERE account_id = $account_id");
        $oldRow = mysqli_fetch_assoc($oldRes);
        $old_pos = (int)($oldRow['position_id'] ?? 0);

        if (mysqli_query($conn, $sql)) {
            if ($position_id > 0 && $position_id !== $old_pos) {
                // Đóng history cũ nếu có
                mysqli_query($conn, "UPDATE employee_positions_history SET end_date = CURDATE() WHERE account_id = $account_id AND end_date IS NULL");
                // Mở history mới
                mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason, created_by)
                                      VALUES ($account_id, $position_id, CURDATE(), 'Cập nhật từ hồ sơ nhân sự', " . currentUserId() . ")");
            }
            echo json_encode(['success' => true, 'message' => 'Cập nhật tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật', 'error' => mysqli_error($conn)]);
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

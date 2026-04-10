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
        $sql = "SELECT a.account_id, a.full_name, a.email, a.role_id, r.name AS role_name, a.status, a.created_at
                FROM accounts a
                JOIN roles r ON r.id = a.role_id
                ORDER BY a.account_id DESC";
        $result = mysqli_query($conn, $sql);
        $accounts = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        
        // Đính kèm danh sách roles để frontend render dropdown
        $roles_result = mysqli_query($conn, "SELECT id, name FROM roles ORDER BY id ASC");
        $roles = $roles_result ? mysqli_fetch_all($roles_result, MYSQLI_ASSOC) : [];

        echo json_encode(['success' => true, 'data' => $accounts, 'roles' => $roles]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $full_name = trim(mysqli_real_escape_string($conn, $data['full_name'] ?? ''));
        $email = trim(mysqli_real_escape_string($conn, $data['email'] ?? ''));
        $password = trim($data['password'] ?? '');
        $role_id = (int)($data['role_id'] ?? 0);

        if (!$full_name || !$email || !$password || !$role_id) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ thông tin']);
            exit;
        }

        // Check trùng email
        $check = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại trong hệ thống']);
            exit;
        }

        $hashed_password = md5($password);
        $status = 'active'; // Mặc định đang làm

        $sql = "INSERT INTO accounts (full_name, email, password, role_id, status) 
                VALUES ('$full_name', '$email', '$hashed_password', $role_id, '$status')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Thêm tài khoản thành công', 'id' => mysqli_insert_id($conn)]);
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
        $role_id = (int)($data['role_id'] ?? 0);

        if ($account_id <= 0 || !$full_name || !$email || !$role_id) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
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

        $sql = "UPDATE accounts 
                SET full_name = '$full_name', email = '$email', role_id = $role_id $password_sql 
                WHERE account_id = $account_id";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật', 'error' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($method === 'PATCH') {
        $data = json_decode(file_get_contents('php://input'), true);
        $account_id = (int)($data['account_id'] ?? 0);
        $status = mysqli_real_escape_string($conn, $data['status'] ?? '');

        if ($account_id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái hoặc ID không hợp lệ']);
            exit;
        }

        if ($account_id === currentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không thể thay đổi trạng thái của chính mình']);
            exit;
        }

        $sql = "UPDATE accounts SET status = '$status' WHERE account_id = $account_id";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái hoạt động']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật trạng thái']);
        }
        exit;
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

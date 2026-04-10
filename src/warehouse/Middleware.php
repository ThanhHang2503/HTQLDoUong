<?php
/**
 * Warehouse authorization middleware
 * Only role_id = 4 can access warehouse module.
 */

namespace Warehouse\Middleware;

class WarehouseAuthMiddleware {
    public static function requireWarehouseAccess(\mysqli $conn): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['account_id'])) {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]));
        }

        $account_id = (int)$_SESSION['account_id'];
        $sql = "SELECT role_id, status FROM accounts WHERE account_id = {$account_id} LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'message' => 'Tài khoản không hợp lệ'
            ]));
        }

        $user = mysqli_fetch_assoc($result);
        if (($user['status'] ?? 'inactive') !== 'active') {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa'
            ]));
        }

        if ((int)$user['role_id'] !== 4) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module kho. Chỉ role_id = 4 (WAREHOUSE_STAFF) được phép.'
            ]));
        }
    }

    public static function currentUserId(): int {
        return (int)($_SESSION['account_id'] ?? 0);
    }
}

function requireWarehouseAccess(\mysqli $conn): void {
    WarehouseAuthMiddleware::requireWarehouseAccess($conn);
}

function warehouseCurrentUserId(): int {
    return WarehouseAuthMiddleware::currentUserId();
}

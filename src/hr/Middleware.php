<?php
/**
 * HR Authorization Middleware
 * Kiểm tra quyền truy cập HR module
 */

namespace HR\Middleware;

/**
 * Middleware kiểm tra role_id = 2 (MANAGER)
 */
class HRAuthMiddleware {
    /**
     * Kiểm tra quyền truy cập HR module
     * Chỉ MANAGER (role_id = 2) được truy cập
     */
    public static function requireHRAccess(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['account_id']) || !isset($_SESSION['role_name'])) {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]));
        }

        // Check if user is manager (role_id = 2)
        $role_name = $_SESSION['role_name'] ?? '';
        
        if ($role_name !== 'manager' && $role_name !== 'admin') {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module nhân sự. Chỉ MANAGER hoặc ADMIN có thể truy cập.'
            ]));
        }
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId(): int {
        return (int)($_SESSION['account_id'] ?? 0);
    }

    /**
     * Get current user role
     */
    public static function getCurrentRole(): string {
        return $_SESSION['role_name'] ?? '';
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool {
        return ($_SESSION['role_name'] ?? '') === 'admin';
    }

    /**
     * Check if user is manager
     */
    public static function isManager(): bool {
        return ($_SESSION['role_name'] ?? '') === 'manager';
    }
}

/**
 * Guard functions for API usage
 */

/**
 * Require HR access (manager or admin)
 */
function requireHRAccess() {
    HRAuthMiddleware::requireHRAccess();
}

/**
 * Get current logged-in user ID
 */
function hrCurrentUserId(): int {
    return HRAuthMiddleware::getCurrentUserId();
}

/**
 * Get current user role
 */
function hrCurrentRole(): string {
    return HRAuthMiddleware::getCurrentRole();
}

/**
 * Check if current user is admin
 */
function hrIsAdmin(): bool {
    return HRAuthMiddleware::isAdmin();
}

/**
 * Check if current user is manager
 */
function hrIsManager(): bool {
    return HRAuthMiddleware::isManager();
}

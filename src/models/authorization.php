<?php

final class AppRole
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const SALES = 'sales';
    public const WAREHOUSE = 'warehouse';
}

final class AppPermission
{
    public const VIEW_DASHBOARD = 'view_dashboard';
    public const MANAGE_CATALOG = 'manage_catalog';
    public const MANAGE_WAREHOUSE = 'manage_warehouse'; // Quản lý kho
    public const PROCESS_ORDERS = 'process_orders';
    public const MANAGE_CUSTOMERS = 'manage_customers';
    public const VIEW_REPORTS = 'view_reports';
    public const MANAGE_STAFF = 'manage_staff';
    public const MANAGE_ACCOUNTS = 'manage_accounts'; // Tạo, sửa, đóng lỗi accounts
}

function getRolePermissions(): array
{
    return [
        AppRole::ADMIN => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
            AppPermission::VIEW_REPORTS,
            AppPermission::MANAGE_STAFF,
            AppPermission::MANAGE_ACCOUNTS,
        ],
        AppRole::MANAGER => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
            AppPermission::MANAGE_WAREHOUSE,
            AppPermission::PROCESS_ORDERS,
            AppPermission::MANAGE_CUSTOMERS,
            AppPermission::VIEW_REPORTS,
            AppPermission::MANAGE_STAFF,
            AppPermission::MANAGE_ACCOUNTS,
        ],
        AppRole::SALES => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::PROCESS_ORDERS,
            AppPermission::MANAGE_CUSTOMERS,
        ],
        AppRole::WAREHOUSE => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
            AppPermission::MANAGE_WAREHOUSE,
        ],
    ];
}

function roleLabel(string $roleName): string
{
    $labels = [
        AppRole::ADMIN     => 'Quản trị viên',
        AppRole::MANAGER   => 'Quản lý nhân sự',
        AppRole::SALES     => 'Nhân viên bán hàng',
        AppRole::WAREHOUSE => 'Nhân viên kho',
    ];

    return $labels[$roleName] ?? 'Không xác định';
}

function currentRole(): string
{
    return $_SESSION['role_name'] ?? '';
}

function currentUserId(): int
{
    return (int) ($_SESSION['account_id'] ?? 0);
}

function isLoggedIn(): bool
{
    return currentUserId() > 0 && currentRole() !== '';
}

function can(string $permission): bool
{
    $role = currentRole();
    if ($role === '') {
        return false;
    }

    $permissions = getRolePermissions();
    if (!isset($permissions[$role])) {
        return false;
    }

    return in_array($permission, $permissions[$role], true);
}

function getBaseUrl(): string
{
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $projectRoot = str_replace('\\', '/', dirname(__DIR__, 2));
    // Fallback if the project is outside DocumentRoot or some alias config is used
    if (strpos($projectRoot, $docRoot) === 0) {
        return str_replace($docRoot, '', $projectRoot);
    }
    // Simple fallback to getting the first folder from SCRIPT_NAME if it doesn't match
    $parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    if (count($parts) > 1) {
        return '/' . $parts[0];
    }
    return '';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $portal = (session_name() === 'SESSION_ADMIN') ? 'admin' : 'user';
        $login_url = getBaseUrl() . "/login_form.php?portal=$portal";
        if (headers_sent()) {
            echo '<script>window.location.href="' . $login_url . '";</script>';
        } else {
            header('location:' . $login_url);
        }
        exit;
    }
}

function requirePermission(string $permission): void
{
    requireLogin();
    if (!can($permission)) {
        $redirect_url = getBaseUrl() . "/user_page.php?home&error=forbidden";
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect_url . '";</script>';
        } else {
            header('location:' . $redirect_url);
        }
        exit;
    }
}

function requireRole(string $roleName): void
{
    requireLogin();
    if (currentRole() !== $roleName) {
        $redirect_url = getBaseUrl() . "/user_page.php?home&error=forbidden";
        header('location:' . $redirect_url);
        exit;
    }
}

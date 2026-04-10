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
    public const PROCESS_ORDERS = 'process_orders';
    public const MANAGE_CUSTOMERS = 'manage_customers';
    public const VIEW_REPORTS = 'view_reports';
    public const MANAGE_STAFF = 'manage_staff';
}

function getRolePermissions(): array
{
    return [
        AppRole::ADMIN => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
            AppPermission::PROCESS_ORDERS,
            AppPermission::MANAGE_CUSTOMERS,
            AppPermission::VIEW_REPORTS,
            AppPermission::MANAGE_STAFF,
        ],
        AppRole::MANAGER => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
            AppPermission::PROCESS_ORDERS,
            AppPermission::MANAGE_CUSTOMERS,
            AppPermission::VIEW_REPORTS,
        ],
        AppRole::SALES => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::PROCESS_ORDERS,
            AppPermission::MANAGE_CUSTOMERS,
        ],
        AppRole::WAREHOUSE => [
            AppPermission::VIEW_DASHBOARD,
            AppPermission::MANAGE_CATALOG,
        ],
    ];
}

function roleLabel(string $roleName): string
{
    $labels = [
        AppRole::ADMIN => 'Admin',
        AppRole::MANAGER => 'Quan ly',
        AppRole::SALES => 'Nhan vien ban hang',
        AppRole::WAREHOUSE => 'Nhan vien kho',
    ];

    return $labels[$roleName] ?? 'Khong xac dinh';
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

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('location:login_form.php');
        exit;
    }
}

function requirePermission(string $permission): void
{
    requireLogin();
    if (!can($permission)) {
        header('location:user_page.php?home&error=forbidden');
        exit;
    }
}

function requireRole(string $roleName): void
{
    requireLogin();
    if (currentRole() !== $roleName) {
        header('location:user_page.php?home&error=forbidden');
        exit;
    }
}
